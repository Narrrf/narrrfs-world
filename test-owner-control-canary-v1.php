<?php
declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-authorization.php';

function canary_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$policyPath = __DIR__ . '/api/config/admin-route-policies.php';
$priorCanaryEnabled = getenv('NARRRFS_OWNER_CANARY_ENABLED');

foreach ([null, '', 'true', '01', ' 1', '1 '] as $disabledValue) {
    if ($disabledValue === null || $disabledValue === '') {
        putenv('NARRRFS_OWNER_CANARY_ENABLED');
    } else {
        putenv('NARRRFS_OWNER_CANARY_ENABLED=' . $disabledValue);
    }
    $registry = require $policyPath;
    canary_test_assert(($registry['routes']['owner_control.canary.v1']['enabled'] ?? null) === false, 'non-exact enable value fails closed');
}

putenv('NARRRFS_OWNER_CANARY_ENABLED=1');
$registry = require $policyPath;
$policy = $registry['routes']['owner_control.canary.v1'] ?? null;
canary_test_assert(is_array($policy), 'enabled canary policy exists');
canary_test_assert(narrrfs_validate_admin_route_policy($policy) !== null, 'enabled canary policy is schema-valid');
canary_test_assert($policy['methods'] === ['GET', 'HEAD'], 'canary methods are exactly GET and HEAD');
canary_test_assert($policy['mutating'] === false && $policy['csrf'] === false, 'canary policy is non-mutating');

$now = 2000000000;
$owner = '111111111111111111';
$normalUser = '222222222222222222';
$ownerSession = [
    'discord_id' => $owner,
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => $now,
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => $now,
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => $now + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS,
];

foreach (['GET', 'HEAD'] as $method) {
    $decision = narrrfs_admin_authorization_decision($policy, $ownerSession, ['method' => $method], [$owner], $now);
    canary_test_assert($decision['allowed'] === true, $method . ' is allowed for the exact trusted owner');
    canary_test_assert($decision['consumeCsrf'] === false, $method . ' performs no CSRF/session transition');
}

$postDecision = narrrfs_admin_authorization_decision($policy, $ownerSession, ['method' => 'POST'], [$owner], $now);
canary_test_assert($postDecision['allowed'] === false && $postDecision['status'] === 405, 'POST is denied');
canary_test_assert($postDecision['allow'] === ['GET', 'HEAD'], 'method denial reports the exact allowed methods');

$missingSession = narrrfs_admin_authorization_decision($policy, [], ['method' => 'GET'], [$owner], $now);
canary_test_assert($missingSession['status'] === 401, 'missing trusted session is denied');

$normalSession = $ownerSession;
$normalSession['discord_id'] = $normalUser;
$normalDecision = narrrfs_admin_authorization_decision($policy, $normalSession, ['method' => 'GET'], [$owner], $now);
canary_test_assert($normalDecision['status'] === 403, 'normal user is denied');

foreach ([null, '', 'malformed', $owner . ',malformed'] as $rawAllowlist) {
    $ownerIds = narrrfs_parse_admin_owner_ids($rawAllowlist);
    $decision = narrrfs_admin_authorization_decision($policy, $ownerSession, ['method' => 'GET'], $ownerIds, $now);
    canary_test_assert($decision['allowed'] === false && $decision['status'] === 503, 'missing or malformed owner allowlist denies');
}

putenv('NARRRFS_OWNER_CANARY_ENABLED');
$disabledRegistry = require $policyPath;
$disabledDecision = narrrfs_admin_authorization_decision(
    $disabledRegistry['routes']['owner_control.canary.v1'] ?? null,
    $ownerSession,
    ['method' => 'GET'],
    [$owner],
    $now
);
canary_test_assert($disabledDecision['status'] === 404, 'missing enable gate denies before route logic');

if ($priorCanaryEnabled === false) {
    putenv('NARRRFS_OWNER_CANARY_ENABLED');
} else {
    putenv('NARRRFS_OWNER_CANARY_ENABLED=' . $priorCanaryEnabled);
}

$routePath = __DIR__ . '/api/owner-control/v1/canary.php';
$routeSource = file_get_contents($routePath);
$guardSource = file_get_contents(__DIR__ . '/api/config/admin-authorization.php');
$sessionSource = file_get_contents(__DIR__ . '/api/config/session.php');
$containmentSource = file_get_contents(__DIR__ . '/deploy/apache/narrrfs-emergency-containment.conf');
canary_test_assert(is_string($routeSource) && is_string($guardSource) && is_string($sessionSource) && is_string($containmentSource), 'canary and guard sources are readable');

canary_test_assert(strpos($routeSource, "define('NARRRFS_SESSION_READ_ONLY', true)") < strpos($routeSource, "require_once"), 'read-only session mode is selected before guard bootstrap');
canary_test_assert(str_contains($sessionSource, "session_start(['read_and_close' => true])"), 'canary session is opened read-only');
canary_test_assert(str_contains($guardSource, 'if (!$readOnlySession)'), 'guard skips authority transitions in read-only mode');
canary_test_assert(str_contains($routeSource, "narrrfs_require_admin_request('owner_control.canary.v1')"), 'route requires the exact owner policy');
canary_test_assert(str_contains($routeSource, "Cache-Control: no-store"), 'success response is no-store');
canary_test_assert(str_contains($routeSource, "'writes' => false"), 'success schema explicitly reports no writes');

$forbiddenRoutePatterns = '/\b(?:PDO|SQLite3|INSERT|UPDATE|DELETE|REPLACE|file_put_contents|fwrite|fopen|unlink|rename|copy|exec|shell_exec|system|passthru|proc_open|curl_)\b|https?:\/\//i';
canary_test_assert(!preg_match($forbiddenRoutePatterns, $routeSource), 'canary has no DB, filesystem, process, network, or mutation capability');
canary_test_assert(!preg_match('/\$_SESSION\s*\[.*\]\s*=|narrrfs_touch_admin_authority|narrrfs_consume_admin_csrf/i', $routeSource), 'canary route performs no session-authority write');

$ownerControlDeny = '~^(?!/api/owner-control/v1/canary\.php$)(?i:/api/owner-control)(?:/|$)~';
canary_test_assert(preg_match($ownerControlDeny, '/api/owner-control/v1/canary.php') === 0, 'exact canary path is the sole namespace exception');
foreach ([
    '/api/owner-control',
    '/api/owner-control/',
    '/api/owner-control/v1/other.php',
    '/api/owner-control/v1/canary.php/extra',
    '/api/owner-control/v1/Canary.php',
    '/API/OWNER-CONTROL/v1/canary.php',
] as $deniedPath) {
    canary_test_assert(preg_match($ownerControlDeny, $deniedPath) === 1, 'owner-control path remains denied: ' . $deniedPath);
}
canary_test_assert(str_contains($containmentSource, '^(?!/api/owner-control/v1/canary\.php$)(?i:/api/owner-control)'), 'Apache uses the reviewed exact exception rule');
canary_test_assert(str_contains($containmentSource, '(?i)^/admin-interface\.html'), 'legacy admin interface remains denied');
canary_test_assert(str_contains($containmentSource, '(?i)^/api/admin'), 'legacy admin API remains denied');
canary_test_assert(str_contains($containmentSource, '(?i)^/db'), 'database URL remains denied');

echo "PASS: owner-control read-only canary Stage B2\n";
