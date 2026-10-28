<?php
declare(strict_types=1);

$root = __DIR__;
$policyGates = [
    'NARRRFS_OWNER_CANARY_ENABLED',
    'NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED',
    'NARRRFS_OWNER_GENETIC_CATALOG_UI_ENABLED',
];
$priorGates = [];
foreach ($policyGates as $gate) {
    $priorGates[$gate] = getenv($gate);
    putenv($gate);
}
$policies = require $root . '/api/config/admin-route-policies.php';

function policy_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

policy_test_assert(is_array($policies), 'policy registry is an array');
policy_test_assert(($policies['version'] ?? null) === 1, 'policy schema version is exact');
policy_test_assert(($policies['default'] ?? null) === 'deny', 'policy registry defaults to deny');
foreach ([
    'owner_control.canary.v1',
    'owner_control.genetic_catalog.read.v1',
    'owner_control.genetic_catalog.ui.v1',
] as $key) {
    $policy = $policies['routes'][$key] ?? null;
    policy_test_assert(is_array($policy), $key . ' policy is present');
    policy_test_assert(($policy['enabled'] ?? null) === false, $key . ' defaults disabled without its exact environment gate');
}

putenv('NARRRFS_OWNER_CANARY_ENABLED=1');
putenv('NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED=1');
$enabledPolicies = require $root . '/api/config/admin-route-policies.php';
$enabledCanary = $enabledPolicies['routes']['owner_control.canary.v1'] ?? null;
policy_test_assert(is_array($enabledCanary) && ($enabledCanary['enabled'] ?? null) === true, 'exact environment value enables only the canary policy');
$enabledCatalog = $enabledPolicies['routes']['owner_control.genetic_catalog.read.v1'] ?? null;
policy_test_assert(is_array($enabledCatalog) && ($enabledCatalog['enabled'] ?? null) === true, 'exact environment value enables only the Genetic Catalog read policy');
foreach ($priorGates as $gate => $value) {
    $value === false ? putenv($gate) : putenv($gate . '=' . $value);
}

$containmentPath = $root . '/deploy/apache/narrrfs-emergency-containment.conf';
$dockerfilePath = $root . '/Dockerfile';
$saveScorePath = $root . '/api/dev/save-score.php';

$containment = file_get_contents($containmentPath);
$dockerfile = file_get_contents($dockerfilePath);
$saveScore = file_get_contents($saveScorePath);

policy_test_assert(is_string($containment), 'containment source is readable');
policy_test_assert(is_string($dockerfile), 'Dockerfile is readable');
policy_test_assert(is_string($saveScore), 'save-score source is readable');

policy_test_assert(str_contains($containment, '^/admin-interface\\.html'), 'legacy admin page remains denied');
policy_test_assert(str_contains($containment, '^/api/admin'), 'legacy admin API remains denied');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/canary\.php'), 'owner-control containment retains the exact canary exception');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/genetic/catalog\.php'), 'owner-control containment names the exact Genetic Catalog route');
policy_test_assert(str_contains($containment, '^/db'), 'database URL remains denied');
policy_test_assert(str_contains($containment, '/var/www/html/db'), 'database filesystem path remains denied');
policy_test_assert(str_contains($dockerfile, 'a2enconf narrrfs-emergency-containment'), 'Docker image enables containment');
policy_test_assert(str_contains($dockerfile, 'apache2ctl configtest'), 'Docker image validates Apache configuration');

policy_test_assert(
    str_contains($saveScore, 'SECURITY RECOVERY V2 — authenticated score + audited DSPOINC persistence'),
    'save-score is explicitly scoped to guarded score/economy recovery'
);

policy_test_assert(
    str_contains($saveScore, 'INSERT INTO tbl_tetris_scores') &&
    str_contains($saveScore, 'INSERT INTO tbl_glyph_memory_scores') &&
    str_contains($saveScore, 'INSERT INTO tbl_user_scores') &&
    str_contains($saveScore, 'INSERT INTO tbl_score_adjustments'),
    'save-score uses only the approved leaderboard and DSPOINC audit targets'
);

policy_test_assert(
    !str_contains($saveScore, 'tbl_wl_role_grants') &&
    !preg_match('/INSERT\s+INTO\s+tbl_season_settings/i', $saveScore) &&
    !preg_match('/\b(?:UPDATE|DELETE|REPLACE)\b\s+/i', $saveScore),
    'save-score does not restore WL, season-setting, update, delete, or replace mutations'
);

policy_test_assert(
    str_contains($saveScore, "'leaderboard_only' => true") &&
    str_contains($saveScore, "'leaderboard_only' => false") &&
    str_contains($saveScore, "'rewards_credited' => false") &&
    str_contains($saveScore, "'rewards_credited' => true"),
    'save-score distinguishes Glyph leaderboard-only from classic rewarded results'
);

$trackedCallers = [];
$guardPath = realpath($root . '/api/config/admin-authorization.php');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/api', FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    if (realpath($file->getPathname()) === $guardPath) {
        continue;
    }
    $contents = file_get_contents($file->getPathname());
    if (is_string($contents) && str_contains($contents, 'narrrfs_require_admin_request')) {
        $trackedCallers[] = $file->getPathname();
    }
}
policy_test_assert(
    in_array(realpath($root . '/api/owner-control/v1/genetic/catalog.php'), array_map('realpath', $trackedCallers), true),
    'Genetic Catalog route calls the exact owner guard'
);

echo "PASS: admin route policy inventory Stage B2\n";
