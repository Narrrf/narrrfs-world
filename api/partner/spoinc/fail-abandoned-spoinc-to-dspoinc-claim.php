<?php
/**
 * SPOINC Bridge API — Fail an abandoned SPOINC_TO_DSPOINC claim.
 *
 * This endpoint closes only a claim which is proven not to have broadcast:
 * the owner, pending payload mode, pending Gensuki record, and a clean Solana
 * `not_found` result must all agree. It never credits, debits, or settles.
 */

require_once __DIR__ . '/bridge-helpers.php';
require_once __DIR__ . '/bridge-tester-helpers.php';

spoinc_bridge_boot_json_api(['POST', 'OPTIONS']);

const SPOINC_ABANDONED_CLAIM_ROUTE = 'SPOINC_TO_DSPOINC';
const SPOINC_ABANDONED_CLAIM_GET_PATH = '/api/custom-token-presale/getTransaction';
const SPOINC_ABANDONED_CLAIM_CONFIRM_PATH = '/api/custom-token-presale/confirm';
const SPOINC_ABANDONED_CLAIM_PENDING_STATUSES = [
    'pending', 'claim_pending', 'claim_payload_ready', 'created',
    'awaiting_signature', 'awaiting_user_signature', 'processing'
];

function spoinc_abandoned_claim_safety(bool $called = false): array
{
    return [
        'gensuki_confirm_called' => $called,
        'gensuki_status_sent' => $called ? 'failed' : null,
        'dspoinc_credit_performed' => false,
        'dspoinc_debit_performed' => false,
        'local_bridge_settlement_performed' => false,
        'spoinc_to_dspoinc_settlement_performed' => false
    ];
}

function spoinc_abandoned_claim_api_key(): string
{
    foreach (['GENSUKI_SPOINC_API_KEY', 'GENSUKI_OUTBOUND_API_KEY', 'SPOINC_GENSUKI_API_KEY', 'GENSUKI_API_KEY'] as $name) {
        $value = trim((string)getenv($name));
        if ($value !== '') {
            return $value;
        }
    }

    $localConfigPath = __DIR__ . '/../../config/gensuki-outbound-local.php';
    if (spoinc_bridge_is_localhost() && is_file($localConfigPath)) {
        $GENSUKI_OUTBOUND_API_KEY = '';
        require $localConfigPath;
        return trim((string)$GENSUKI_OUTBOUND_API_KEY);
    }

    return '';
}

function spoinc_abandoned_claim_endpoint(array $config, string $path): string
{
    $baseUrl = rtrim(trim((string)($config['api_base_url'] ?? 'https://app.gensuki.xyz')), '/');
    if (preg_match('#/api/custom-token-presale$#', $baseUrl)) {
        return $baseUrl . '/' . ltrim(str_replace('/api/custom-token-presale', '', $path), '/');
    }
    if (preg_match('#/api$#', $baseUrl)) {
        return $baseUrl . '/custom-token-presale/' . ltrim(str_replace('/api/custom-token-presale', '', $path), '/');
    }
    return $baseUrl . $path;
}

function spoinc_abandoned_claim_http_json(string $url, string $method = 'GET', ?array $payload = null, string $apiKey = ''): array
{
    if (!function_exists('curl_init')) {
        return ['success' => false, 'http_code' => 0, 'json' => null, 'error' => 'PHP cURL extension is not available.'];
    }

    $headers = ['Accept: application/json'];
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($apiKey !== '') {
        $headers[] = 'x-api-key: ' . $apiKey;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $rawBody = (string)curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($rawBody, true);
    return [
        'success' => $error === '' && $httpCode >= 200 && $httpCode < 300 && is_array($json),
        'http_code' => $httpCode,
        'json' => is_array($json) ? $json : null,
        'error' => $error
    ];
}

function spoinc_abandoned_claim_find($value, array $keys): string
{
    if (!is_array($value)) {
        return '';
    }
    foreach ($keys as $key) {
        if (array_key_exists($key, $value) && trim((string)$value[$key]) !== '') {
            return trim((string)$value[$key]);
        }
    }
    foreach ($value as $nested) {
        $found = spoinc_abandoned_claim_find($nested, $keys);
        if ($found !== '') {
            return $found;
        }
    }
    return '';
}

function spoinc_abandoned_claim_valid_signature(string $signature): bool
{
    return (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{64,128}$/', trim($signature));
}

function spoinc_abandoned_claim_solana_not_found(string $signature): array
{
    if (!spoinc_abandoned_claim_valid_signature($signature)) {
        return ['allowed' => false, 'state' => 'missing_or_invalid_signature'];
    }
    $rpcUrl = trim((string)getenv('SOLANA_RPC_URL')) ?: trim((string)getenv('HELIUS_RPC_URL'));
    $rpcUrl = $rpcUrl ?: 'https://api.mainnet-beta.solana.com';
    $result = spoinc_abandoned_claim_http_json($rpcUrl, 'POST', [
        'jsonrpc' => '2.0',
        'id' => 'spoinc-abandoned-claim-signature-status',
        'method' => 'getSignatureStatuses',
        'params' => [[$signature], ['searchTransactionHistory' => true]]
    ]);
    $json = $result['json'] ?? null;
    if (empty($result['success']) || !is_array($json) || isset($json['error'])
        || !isset($json['result']) || !is_array($json['result']) || !array_key_exists('value', $json['result'])) {
        return ['allowed' => false, 'state' => 'rpc_error'];
    }
    return ($json['result']['value'][0] ?? null) === null
        ? ['allowed' => true, 'state' => 'not_found']
        : ['allowed' => false, 'state' => 'found_or_pending'];
}

function spoinc_abandoned_claim_block(string $error, int $status = 409): void
{
    spoinc_bridge_json_response(['success' => false, 'error' => $error, 'safety' => spoinc_abandoned_claim_safety()], $status);
}

try {
    $request = spoinc_bridge_get_request_data();
    $intentId = (int)($request['intent_id'] ?? 0);
    $reason = trim((string)($request['reason'] ?? 'pre_broadcast_wallet_failure'));
    if ($intentId <= 0) {
        spoinc_abandoned_claim_block('intent_id is required.', 400);
    }
    $userId = spoinc_bridge_resolve_session_user_id($request);
    if ($userId === '') {
        spoinc_abandoned_claim_block('Discord session is required for abandoned claim cleanup.', 401);
    }

    $pdo = spoinc_bridge_open_database();
    $intentStmt = $pdo->prepare("SELECT * FROM tbl_spoinc_bridge_intents WHERE intent_id = ? AND discord_id = ? AND route_key = ? AND status = 'claim_payload_ready' AND narrrfs_status = 'awaiting_user_signature' LIMIT 1");
    $intentStmt->execute([$intentId, $userId, SPOINC_ABANDONED_CLAIM_ROUTE]);
    $intent = $intentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$intent) {
        spoinc_abandoned_claim_block('No owned pending payload-mode SPOINC_TO_DSPOINC claim was found.', 404);
    }

    $movementStmt = $pdo->prepare('SELECT (SELECT COUNT(*) FROM tbl_spoinc_bridge_transactions WHERE intent_id = ?) + (SELECT COUNT(*) FROM tbl_spoinc_bridge_ledger_audit WHERE intent_id = ?) AS movement_count');
    $movementStmt->execute([$intentId, $intentId]);
    if ((int)($movementStmt->fetchColumn() ?: 0) !== 0) {
        spoinc_abandoned_claim_block('Claim cleanup is blocked because local movement evidence exists.');
    }
    $idempotencyId = trim((string)($intent['idempotency_id'] ?? ''));
    if ($idempotencyId === '') {
        spoinc_abandoned_claim_block('Local claim is missing idempotency_id.');
    }
    $config = spoinc_bridge_load_config($pdo);
    $apiKey = spoinc_abandoned_claim_api_key();
    if (!$config || $apiKey === '') {
        spoinc_abandoned_claim_block('Bridge configuration is unavailable.', 500);
    }

    $lookupUrl = spoinc_abandoned_claim_endpoint($config, SPOINC_ABANDONED_CLAIM_GET_PATH)
        . '?projectId=' . rawurlencode((string)$config['project_id']) . '&idempotencyId=' . rawurlencode($idempotencyId);
    $lookup = spoinc_abandoned_claim_http_json($lookupUrl, 'GET', null, $apiKey);
    $transaction = ($lookup['json']['data'] ?? ($lookup['json']['transaction'] ?? ($lookup['json'] ?? null)));
    if (is_array($transaction) && isset($transaction['transactions']) && is_array($transaction['transactions'])) {
        $transaction = $transaction['transactions'][0] ?? null;
    }
    if (empty($lookup['success']) || !is_array($transaction)) {
        spoinc_abandoned_claim_block('Gensuki claim lookup failed. No failed confirm was sent.', 502);
    }
    $hash = spoinc_abandoned_claim_find($transaction, ['transactionHash', 'transaction_hash', 'signature', 'hash']);
    $gensukiStatus = strtolower(spoinc_abandoned_claim_find($transaction, ['status', 'transactionStatus', 'transaction_status']));
    if (!spoinc_abandoned_claim_valid_signature($hash) || $gensukiStatus === '' || !in_array($gensukiStatus, SPOINC_ABANDONED_CLAIM_PENDING_STATUSES, true)) {
        spoinc_abandoned_claim_block('Gensuki claim is not a pending transaction with a valid Solana hash.');
    }
    $solana = spoinc_abandoned_claim_solana_not_found($hash);
    if (empty($solana['allowed']) || ($solana['state'] ?? '') !== 'not_found') {
        spoinc_abandoned_claim_block('Solana recheck did not prove this claim was not broadcast.');
    }

    $confirm = spoinc_abandoned_claim_http_json(
        spoinc_abandoned_claim_endpoint($config, SPOINC_ABANDONED_CLAIM_CONFIRM_PATH),
        'POST',
        ['projectId' => (string)$config['project_id'], 'transactionHash' => $hash, 'status' => 'failed'],
        $apiKey
    );
    if (empty($confirm['success']) || empty($confirm['json']['success'])) {
        spoinc_bridge_json_response(['success' => false, 'error' => 'Gensuki failed-confirm request failed; local claim remains pending.', 'safety' => spoinc_abandoned_claim_safety(true)], 502);
    }

    $safeResponse = json_encode(['abandoned_claim_cleanup' => [
        'reason' => $reason, 'gensuki_lookup_http_code' => $lookup['http_code'],
        'gensuki_confirm_http_code' => $confirm['http_code'], 'solana_status' => $solana,
        'ledger_movement' => false
    ]]);
    $pdo->beginTransaction();
    $update = $pdo->prepare("UPDATE tbl_spoinc_bridge_intents SET status = 'failed', gensuki_status = 'abandoned_claim_failed_before_broadcast', narrrfs_status = 'abandoned_claim_failed_no_ledger_movement', failed_at = CURRENT_TIMESTAMP, error_message = :error, raw_response_json = :response, updated_at = CURRENT_TIMESTAMP WHERE intent_id = :intent_id AND discord_id = :discord_id AND route_key = :route_key AND status = 'claim_payload_ready' AND narrrfs_status = 'awaiting_user_signature'");
    $update->execute([':error' => 'Wallet transaction was not broadcast. Gensuki pending claim was safely marked failed.', ':response' => $safeResponse, ':intent_id' => $intentId, ':discord_id' => $userId, ':route_key' => SPOINC_ABANDONED_CLAIM_ROUTE]);
    if ($update->rowCount() !== 1) {
        $pdo->rollBack();
        spoinc_abandoned_claim_block('Claim changed during recheck; local update was not applied.');
    }
    $pdo->commit();
    spoinc_bridge_json_response(['success' => true, 'data' => [
        'action' => 'fail_abandoned_spoinc_to_dspoinc_claim', 'intent_id' => $intentId,
        'route_key' => SPOINC_ABANDONED_CLAIM_ROUTE, 'transaction_hash' => $hash,
        'gensuki_status_before' => $gensukiStatus, 'confirmed_status' => 'failed',
        'message' => 'Pending Gensuki claim was safely closed as failed.',
        'safety' => spoinc_abandoned_claim_safety(true)
    ]]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SPOINC abandoned claim cleanup error: ' . $error->getMessage());
    spoinc_bridge_json_response(['success' => false, 'error' => 'Failed to clean abandoned SPOINC claim.', 'details' => spoinc_bridge_is_localhost() ? $error->getMessage() : null, 'safety' => spoinc_abandoned_claim_safety()], 500);
}
