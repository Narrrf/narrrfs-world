<?php
declare(strict_types=1);

// 🔐 Read trusted owner identity without refreshing or rewriting session state.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../config/admin-authorization.php';

/**
 * Build a boolean-only snapshot of how the current PHP web request resolves
 * the owner allowlist.
 *
 * Plain language:
 * This temporary diagnostic never returns the owner ID, allowlist contents,
 * session ID, OAuth tokens, or diagnostic token. It exists only to determine
 * whether Apache/mod_php resolves the server-owned allowlist differently from
 * the process environment during the Stage 0 owner-canary investigation.
 *
 * @return array<string, mixed>
 */
function narrrfs_owner_diagnostic_snapshot(string $subject): array
{
    $describe = static function (mixed $rawValue) use ($subject): array {
        $raw = is_string($rawValue) ? $rawValue : null;
        $present = $raw !== null && trim($raw) !== '';
        $ownerIds = narrrfs_parse_admin_owner_ids($raw);

        return [
            'present' => $present,
            'valid_nonempty' => $ownerIds !== [],
            'subject_match' => narrrfs_admin_subject_is_owner($subject, $ownerIds),
        ];
    };

    $defaultValue = getenv(NARRRFS_ADMIN_OWNER_ENV);
    $localOnlyValue = getenv(NARRRFS_ADMIN_OWNER_ENV, true);

    $apacheCurrentValue = false;
    $apacheTopValue = false;
    if (function_exists('apache_getenv')) {
        $apacheCurrentValue = apache_getenv(NARRRFS_ADMIN_OWNER_ENV, false);
        $apacheTopValue = apache_getenv(NARRRFS_ADMIN_OWNER_ENV, true);
    }

    $same = static function (mixed $left, mixed $right): bool {
        return is_string($left)
            && is_string($right)
            && hash_equals($left, $right);
    };

    return [
        'default' => $describe($defaultValue),
        'local_only' => $describe($localOnlyValue),
        'apache_api_available' => function_exists('apache_getenv'),
        'apache_current' => $describe($apacheCurrentValue),
        'apache_top' => $describe($apacheTopValue),
        'default_equals_local_only' => $same($defaultValue, $localOnlyValue),
        'default_equals_apache_current' => $same($defaultValue, $apacheCurrentValue),
        'default_equals_apache_top' => $same($defaultValue, $apacheTopValue),
    ];
}

$diagnosticRequested =
    isset($_GET['diagnostic'])
    && is_string($_GET['diagnostic'])
    && $_GET['diagnostic'] === '1';

if ($diagnosticRequested) {
    $diagnosticToken = getenv('NARRRFS_OWNER_DIAGNOSTIC_TOKEN', true);
    $requestDiagnosticToken = $_SERVER['HTTP_X_OWNER_DIAGNOSTIC_TOKEN'] ?? '';

    if (
        !is_string($diagnosticToken)
        || !preg_match('/^[a-f0-9]{64}$/', $diagnosticToken)
        || !is_string($requestDiagnosticToken)
        || !hash_equals($diagnosticToken, $requestDiagnosticToken)
    ) {
        narrrfs_admin_fail_closed(404, 'admin_route_unavailable');
    }

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        narrrfs_admin_fail_closed(405, 'method_not_allowed', ['GET', 'HEAD']);
    }

    $subject = $_SESSION['discord_id'] ?? null;
    $provenance = $_SESSION[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY] ?? null;

    $subjectValid =
        is_string($subject)
        && preg_match('/^\d{17,20}$/', $subject) === 1;

    $provenanceValid =
        is_string($provenance)
        && hash_equals(NARRRFS_ADMIN_AUTH_PROVENANCE, $provenance);

    $authorityCurrent = narrrfs_admin_session_is_current($_SESSION);

    if (!$subjectValid || !$provenanceValid || !$authorityCurrent) {
        narrrfs_admin_fail_closed(401, 'authentication_required');
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');

    if ($method === 'HEAD') {
        exit;
    }

    echo json_encode([
        'success' => true,
        'capability' => 'owner_control_environment_diagnostic',
        'version' => 1,
        'mode' => 'read_only',
        'writes' => false,
        'session' => [
            'subject_format_valid' => $subjectValid,
            'trusted_provenance' => $provenanceValid,
            'authority_current' => $authorityCurrent,
        ],
        'owner_environment' => narrrfs_owner_diagnostic_snapshot($subject),
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

narrrfs_require_admin_request('owner_control.canary.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
    exit;
}

echo json_encode([
    'success' => true,
    'capability' => 'owner_control_canary',
    'version' => 1,
    'mode' => 'read_only',
    'writes' => false,
], JSON_UNESCAPED_SLASHES);
