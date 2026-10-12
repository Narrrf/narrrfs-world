<?php
// Protected operation receipt foundation.
// This file supplies session, registry, fingerprint, receipt, and rollback primitives only.
// No action is enabled here; endpoint adoption needs separate protected-system approval.

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

final class ProtectedOperationException extends RuntimeException
{
    public int $httpStatus;
    public string $errorCode;

    public function __construct(int $httpStatus, string $errorCode, string $message)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->errorCode = $errorCode;
    }
}

/** Return the sole authoritative actor for a future protected operation. */
function protected_operation_require_session_actor(): string
{
    $actorUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    if ($actorUserId === '') {
        throw new ProtectedOperationException(401, 'authentication_required', 'Discord authentication is required.');
    }

    return $actorUserId;
}

/** Finite V1 operation registry. Empty action lists deliberately disable every action. */
function protected_operation_action_registry(): array
{
    return [
        'v1' => [
            'trait_unlock' => ['enabled' => false, 'actions' => []],
            'riddle_reward' => ['enabled' => false, 'actions' => []],
            'chest_reward' => ['enabled' => false, 'actions' => []],
        ],
    ];
}

/** Resolve only an explicitly enabled action. V1 always rejects before persistence. */
function protected_operation_resolve_action(string $contractVersion, string $operationKind, string $actionKey): array
{
    $registry = protected_operation_action_registry();
    $contract = $registry[$contractVersion] ?? null;
    $operation = is_array($contract) ? ($contract[$operationKind] ?? null) : null;

    if (!is_array($operation) || trim($actionKey) === '' || ($operation['enabled'] ?? false) !== true) {
        throw new ProtectedOperationException(409, 'action_disabled', 'This protected action is not enabled.');
    }

    $action = $operation['actions'][$actionKey] ?? null;
    if (!is_array($action) || ($action['enabled'] ?? false) !== true) {
        throw new ProtectedOperationException(409, 'action_disabled', 'This protected action is not enabled.');
    }

    return $action;
}

/** Encode a UTF-8 value without delimiter ambiguity. */
function protected_operation_length_prefix(string $value): string
{
    return (string)strlen($value) . ':' . $value;
}

/** Normalize caller-approved context only after the endpoint allow-lists it. */
function protected_operation_normalize_context(array $context): string
{
    $normalized = [];
    foreach ($context as $key => $value) {
        if (!is_string($key) || trim($key) === '' || !is_scalar($value)) {
            throw new InvalidArgumentException('Protected operation context is invalid.');
        }
        $normalized[$key] = (string)$value;
    }

    ksort($normalized, SORT_STRING);
    $encoded = '';
    foreach ($normalized as $key => $value) {
        $encoded .= protected_operation_length_prefix($key);
        $encoded .= protected_operation_length_prefix($value);
    }

    return $encoded;
}

/** Build the actor-bound SHA-256 replay fingerprint defined by P2-I1D. */
function protected_operation_request_fingerprint(
    string $actorUserId,
    string $contractVersion,
    string $operationKind,
    string $actionKey,
    string $policyVersion,
    array $normalizedContext = []
): string {
    $parts = [
        $actorUserId,
        $contractVersion,
        $operationKind,
        $actionKey,
        $policyVersion,
        protected_operation_normalize_context($normalizedContext),
    ];

    $encoded = '';
    foreach ($parts as $part) {
        $encoded .= protected_operation_length_prefix($part);
    }

    return hash('sha256', $encoded);
}

/** Validate the client replay token before any receipt lookup or mutation. */
function protected_operation_validate_idempotency_key(string $idempotencyKey): string
{
    $idempotencyKey = trim($idempotencyKey);
    $length = strlen($idempotencyKey);
    if ($length < 16 || $length > 200) {
        throw new ProtectedOperationException(400, 'invalid_idempotency_key', 'Idempotency key length is invalid.');
    }

    return $idempotencyKey;
}

/** Read a prior actor-bound receipt for a same-operation retry. */
function protected_operation_find_receipt(PDO $pdo, string $actorUserId, string $operationKind, string $idempotencyKey): ?array
{
    $statement = $pdo->prepare(
        'SELECT receipt_id, actor_user_id, contract_version, operation_kind, action_key, claim_key, policy_version, idempotency_key, request_fingerprint, status, outcome_code, outcome_json, completed_at
         FROM tbl_protected_operation_receipts
         WHERE actor_user_id = ? AND operation_kind = ? AND idempotency_key = ?
         LIMIT 1'
    );
    $statement->execute([$actorUserId, $operationKind, $idempotencyKey]);
    $receipt = $statement->fetch(PDO::FETCH_ASSOC);

    return is_array($receipt) ? $receipt : null;
}

/** Return the immutable stored result only for a matching replay fingerprint. */
function protected_operation_replay_result(array $receipt, string $requestFingerprint): array
{
    if (!hash_equals((string)$receipt['request_fingerprint'], $requestFingerprint)) {
        throw new ProtectedOperationException(409, 'idempotency_mismatch', 'Idempotency key was reused with different input.');
    }

    $outcome = json_decode((string)$receipt['outcome_json'], true);
    if (!is_array($outcome)) {
        throw new RuntimeException('Protected operation receipt outcome is malformed.');
    }

    return [
        'replayed' => true,
        'receipt_id' => (int)$receipt['receipt_id'],
        'outcome_code' => (string)$receipt['outcome_code'],
        'outcome' => $outcome,
        'completed_at' => (string)$receipt['completed_at'],
    ];
}

/** Persist a completed receipt inside the caller-owned transaction only. */
function protected_operation_store_completed_receipt(PDO $pdo, array $receipt): int
{
    $statement = $pdo->prepare(
        'INSERT INTO tbl_protected_operation_receipts (
            actor_user_id, contract_version, operation_kind, action_key, claim_key,
            policy_version, idempotency_key, request_fingerprint, status, outcome_code,
            outcome_json, audit_json
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'completed\', ?, ?, ?)'
    );
    $statement->execute([
        $receipt['actor_user_id'],
        $receipt['contract_version'],
        $receipt['operation_kind'],
        $receipt['action_key'],
        $receipt['claim_key'],
        $receipt['policy_version'],
        $receipt['idempotency_key'],
        $receipt['request_fingerprint'],
        $receipt['outcome_code'],
        json_encode($receipt['outcome'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        json_encode($receipt['audit'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);

    return (int)$pdo->lastInsertId();
}

/** Safely roll back a caller-owned protected-operation transaction. */
function protected_operation_rollback(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}