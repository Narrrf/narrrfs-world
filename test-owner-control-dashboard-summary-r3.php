<?php
declare(strict_types=1);

// Narrrfs World Owner Control R3 — Dashboard Summary contract.
//
// DEVS FOR DECADES:
// Verify the dedicated, default-off, read-only Owner Control dashboard
// without executing HTTP handlers or accessing the application database.

function dashboard_r3_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$registryPath = __DIR__ . '/api/config/admin-route-policies.php';
$endpointPath = __DIR__ . '/api/owner-control/v1/dashboard-summary.php';

dashboard_r3_assert(is_file($registryPath), 'policy registry exists');
dashboard_r3_assert(is_file($endpointPath), 'dashboard API exists');

$routeKey = 'owner_control.dashboard.summary.v1';
$flag = 'NARRRFS_OWNER_DASHBOARD_SUMMARY_ENABLED';

putenv($flag);
$disabledRegistry = require $registryPath;

dashboard_r3_assert(
    ($disabledRegistry['default'] ?? null) === 'deny',
    'default route policy denies'
);

dashboard_r3_assert(
    ($disabledRegistry['routes'][$routeKey]['enabled'] ?? null) === false,
    'dashboard API disabled by default'
);

putenv($flag . '=1');
$enabledRegistry = require $registryPath;

$expectedPolicy = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => false,
    'methods' => ['GET', 'HEAD'],
    'csrf' => false,
    'contentTypes' => [],
    'origins' => [],
    'allowRefererFallback' => false,
];

dashboard_r3_assert(
    ($enabledRegistry['routes'][$routeKey] ?? null) === $expectedPolicy,
    'exact enabled dashboard API policy'
);

putenv($flag);

$source = file_get_contents($endpointPath);
dashboard_r3_assert(is_string($source), 'API source readable');

$requiredMarkers = [
    "define('NARRRFS_SESSION_READ_ONLY', true)",
    'narrrfs_require_admin_request(',
    "'owner_control.dashboard.summary.v1'",
    'SQLITE3_OPEN_READONLY',
    'PRAGMA query_only = ON',
    'Cache-Control: no-store',
    'REQUEST_METHOD',
    "=== 'HEAD'",
    "'writes' => false",
    "'totalUsers'",
    "'totalScores'",
    "'totalItems'",
    "'activeLabOffers'",
    "'publicLabOffers'",
    "'activeQuests'",
    'FROM tbl_users',
    'FROM tbl_user_scores',
    'FROM tbl_store_items WHERE is_active = 1',
    'FROM tbl_mouse_lp_store_offers WHERE is_active = 1',
    'FROM tbl_quests WHERE is_active = 1',
    'o.stock > 0',
    'g.is_visible = 1',
];

foreach ($requiredMarkers as $marker) {
    dashboard_r3_assert(
        str_contains($source, $marker),
        "required source marker: {$marker}"
    );
}

$forbiddenMarkers = [
    '/api/admin/',
    'SQLITE3_OPEN_READWRITE',
    'INSERT INTO',
    'UPDATE tbl_',
    'DELETE FROM',
    'REPLACE INTO',
    'ATTACH DATABASE',
    'curl_',
    'discord.com/api',
    'db-access.php',
];

foreach ($forbiddenMarkers as $marker) {
    dashboard_r3_assert(
        stripos($source, $marker) === false,
        "forbidden source marker absent: {$marker}"
    );
}

echo "PASS: Owner Control Dashboard Summary R3 contract\n";
echo "DEFAULT_POLICY=deny\n";
echo "OWNER_ONLY=true\n";
echo "DEFAULT_OFF=true\n";
echo "METHODS=GET,HEAD\n";
echo "SQLITE_OPEN_MODE=READONLY\n";
echo "SQLITE_QUERY_ONLY=true\n";
echo "METRICS=6\n";
echo "LEGACY_ADMIN_API=none\n";
echo "REAL_DB_OPENED=NO\n";
echo "PRODUCTION_ACCESS=NO\n";
