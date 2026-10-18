<?php
declare(strict_types=1);

$root = __DIR__;
$priorCanaryEnabled = getenv('NARRRFS_OWNER_CANARY_ENABLED');
putenv('NARRRFS_OWNER_CANARY_ENABLED');
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
policy_test_assert(array_keys($policies['routes'] ?? []) === ['owner_control.canary.v1'], 'registry contains only the Stage B2 canary');
$disabledCanary = $policies['routes']['owner_control.canary.v1'] ?? null;
policy_test_assert(is_array($disabledCanary), 'canary policy is present');
policy_test_assert(($disabledCanary['enabled'] ?? null) === false, 'canary defaults disabled without the exact environment gate');

putenv('NARRRFS_OWNER_CANARY_ENABLED=1');
$enabledPolicies = require $root . '/api/config/admin-route-policies.php';
$enabledCanary = $enabledPolicies['routes']['owner_control.canary.v1'] ?? null;
policy_test_assert(is_array($enabledCanary) && ($enabledCanary['enabled'] ?? null) === true, 'exact environment value enables only the canary policy');
if ($priorCanaryEnabled === false) {
    putenv('NARRRFS_OWNER_CANARY_ENABLED');
} else {
    putenv('NARRRFS_OWNER_CANARY_ENABLED=' . $priorCanaryEnabled);
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
policy_test_assert(str_contains($containment, '^(?!/api/owner-control/v1/canary\.php$)(?i:/api/owner-control)'), 'owner-control namespace has one exact canary exception');
policy_test_assert(str_contains($containment, '^/db'), 'database URL remains denied');
policy_test_assert(str_contains($containment, '/var/www/html/db'), 'database filesystem path remains denied');
policy_test_assert(str_contains($dockerfile, 'a2enconf narrrfs-emergency-containment'), 'Docker image enables containment');
policy_test_assert(str_contains($dockerfile, 'apache2ctl configtest'), 'Docker image validates Apache configuration');

policy_test_assert(!preg_match('/\b(?:PDO|SQLite3)\b|sqlite:/i', $saveScore), 'save-score has no database connector');
policy_test_assert(!preg_match('/\b(?:INSERT|UPDATE|DELETE|REPLACE)\b/i', $saveScore), 'save-score has no SQL mutation');
policy_test_assert(str_contains($saveScore, 'score_submission_temporarily_disabled'), 'save-score remains terminally disabled');
policy_test_assert(!preg_match('/[\'\"]success[\'\"]\s*=>\s*true/i', $saveScore), 'save-score cannot report success');

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
policy_test_assert(count($trackedCallers) === 1, 'exactly one route calls the owner guard');
policy_test_assert(realpath($trackedCallers[0]) === realpath($root . '/api/owner-control/v1/canary.php'), 'only the Stage B2 canary calls the owner guard');

echo "PASS: admin route policy inventory Stage B2\n";
