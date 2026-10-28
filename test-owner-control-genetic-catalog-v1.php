<?php
declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-authorization.php';

function genetic_catalog_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$policyPath = __DIR__ . '/api/config/admin-route-policies.php';
$priorGate = getenv('NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED');
putenv('NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED=1');
$policy = (require $policyPath)['routes']['owner_control.genetic_catalog.read.v1'] ?? null;
genetic_catalog_test_assert(is_array($policy), 'Genetic Catalog policy exists');
genetic_catalog_test_assert(narrrfs_validate_admin_route_policy($policy) !== null, 'Genetic Catalog policy is schema-valid');
genetic_catalog_test_assert($policy['methods'] === ['GET', 'HEAD'], 'policy permits exactly GET and HEAD');
genetic_catalog_test_assert($policy['mutating'] === false && $policy['csrf'] === false, 'policy is non-mutating without CSRF');

$now = 2000000000;
$owner = '111111111111111111';
$session = [
    'discord_id' => $owner,
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => $now,
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => $now,
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => $now + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS,
];
foreach (['GET', 'HEAD'] as $method) {
    $decision = narrrfs_admin_authorization_decision($policy, $session, ['method' => $method], [$owner], $now);
    genetic_catalog_test_assert($decision['allowed'] === true && $decision['consumeCsrf'] === false, $method . ' permits only a trusted owner without CSRF/session mutation');
}
foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
    $decision = narrrfs_admin_authorization_decision($policy, $session, ['method' => $method], [$owner], $now);
    genetic_catalog_test_assert($decision['allowed'] === false && $decision['status'] === 405, $method . ' is denied before route access');
}
$normal = $session;
$normal['discord_id'] = '222222222222222222';
genetic_catalog_test_assert(narrrfs_admin_authorization_decision($policy, [], ['method' => 'GET'], [$owner], $now)['status'] === 401, 'unauthenticated request is denied');
genetic_catalog_test_assert(narrrfs_admin_authorization_decision($policy, $normal, ['method' => 'GET'], [$owner], $now)['status'] === 403, 'normal user is denied');
putenv('NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED');
$disabledPolicy = (require $policyPath)['routes']['owner_control.genetic_catalog.read.v1'] ?? null;
genetic_catalog_test_assert(narrrfs_admin_authorization_decision($disabledPolicy, $session, ['method' => 'GET'], [$owner], $now)['status'] === 404, 'disabled policy denies before route access');
$priorGate === false ? putenv('NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED') : putenv('NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED=' . $priorGate);

$route = file_get_contents(__DIR__ . '/api/owner-control/v1/genetic/catalog.php');
$containment = file_get_contents(__DIR__ . '/deploy/apache/narrrfs-emergency-containment.conf');
genetic_catalog_test_assert(is_string($route) && is_string($containment), 'route and containment sources are readable');
foreach (["define('NARRRFS_SESSION_READ_ONLY', true)", "narrrfs_require_admin_request('owner_control.genetic_catalog.read.v1')", 'SQLITE3_OPEN_READONLY', "PRAGMA query_only = ON", 'FROM tbl_genetic_trait_catalog', 'Cache-Control: no-store', "'writes' => false"] as $required) {
    genetic_catalog_test_assert(str_contains($route, $required), 'route contains required read-only boundary: ' . $required);
}
foreach (['SELECT *', 'tbl_user_genetic_items', 'tbl_genetic_market_listings', 'tbl_user_scores', 'tbl_score_adjustments', 'INSERT ', 'UPDATE ', 'DELETE ', 'REPLACE ', 'BEGIN', 'COMMIT', 'ROLLBACK', 'CREATE ', 'ALTER ', 'DROP ', 'ATTACH ', 'VACUUM'] as $forbidden) {
    genetic_catalog_test_assert(!str_contains($route, $forbidden), 'route excludes protected/write SQL: ' . $forbidden);
}
foreach (['effect_metadata_json', 'base_price_dspoinc', 'rarity_count', 'image_path', 'source_origin'] as $excluded) {
    genetic_catalog_test_assert(!preg_match("/'" . preg_quote($excluded, '/') . "'\\s*=>/", $route), 'response omits excluded field: ' . $excluded);
}
genetic_catalog_test_assert(str_contains($containment, '/api/owner-control/v1/genetic/catalog\\.php'), 'containment exception names only the exact catalog path');
echo "PASS: owner-control Genetic Catalog read contract V1\n";
