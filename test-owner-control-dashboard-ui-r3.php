<?php
declare(strict_types=1);

// Narrrfs World Owner Control R3 — Dashboard UI contract.
//
// DEVS FOR DECADES:
// Verify the owner-only dashboard presentation and independent feature gate.
// This test reads source files only and never executes the browser UI or APIs.

function dashboard_ui_r3_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$registryPath = __DIR__ . '/api/config/admin-route-policies.php';
$pagePath = __DIR__ . '/owner-control/v1/dashboard.php';
$homePath = __DIR__ . '/owner-control/v1/index.php';

foreach ([$registryPath, $pagePath, $homePath] as $path) {
    dashboard_ui_r3_assert(is_file($path), "required file exists: {$path}");
}

$routeKey = 'owner_control.dashboard.ui.v1';
$flag = 'NARRRFS_OWNER_DASHBOARD_UI_ENABLED';

// Disabled-by-default contract.
putenv($flag);
$disabledRegistry = require $registryPath;

dashboard_ui_r3_assert(
    ($disabledRegistry['routes'][$routeKey]['enabled'] ?? null) === false,
    'dashboard UI disabled by default'
);

// Enabling only this UI policy must not enable the API policy.
putenv('NARRRFS_OWNER_DASHBOARD_SUMMARY_ENABLED');
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

dashboard_ui_r3_assert(
    ($enabledRegistry['routes'][$routeKey] ?? null) === $expectedPolicy,
    'exact dashboard UI policy'
);

dashboard_ui_r3_assert(
    (
        $enabledRegistry['routes']['owner_control.dashboard.summary.v1']['enabled']
        ?? null
    ) === false,
    'API gate independent from UI gate'
);

putenv($flag);

$page = file_get_contents($pagePath);
$home = file_get_contents($homePath);

dashboard_ui_r3_assert(is_string($page), 'dashboard UI readable');
dashboard_ui_r3_assert(is_string($home), 'Owner Control home readable');

$requiredPageMarkers = [
    "define('NARRRFS_SESSION_READ_ONLY', true)",
    "'owner_control.dashboard.ui.v1'",
    'Cache-Control: no-store',
    "=== 'HEAD'",
    '/api/owner-control/v1/dashboard-summary.php',
    "credentials: 'same-origin'",
    "cache: 'no-store'",
    'textContent',
    'replaceChildren',
    'Number.isSafeInteger',
    'totalUsers',
    'totalScores',
    'totalItems',
    'activeLabOffers',
    'publicLabOffers',
    'activeQuests',
];

foreach ($requiredPageMarkers as $marker) {
    dashboard_ui_r3_assert(
        str_contains($page, $marker),
        "required UI marker: {$marker}"
    );
}

$forbiddenPageMarkers = [
    '/api/admin/',
    'innerHTML',
    'insertAdjacentHTML',
    'document.write',
    'document.cookie',
    'localStorage',
    'sessionStorage',
    'new SQLite3',
    'new PDO',
    'discord.com/api',
];

foreach ($forbiddenPageMarkers as $marker) {
    dashboard_ui_r3_assert(
        stripos($page, $marker) === false,
        "forbidden UI marker absent: {$marker}"
    );
}

$requiredHomeMarkers = [
    "'status' => \$dashboardAvailable ? 'AVAILABLE' : 'REVIEW'",
    "'href' => \$dashboardAvailable",
    "'/owner-control/v1/dashboard.php'",
    "NARRRFS_OWNER_DASHBOARD_UI_ENABLED",
    "NARRRFS_OWNER_DASHBOARD_SUMMARY_ENABLED",
];

foreach ($requiredHomeMarkers as $marker) {
    dashboard_ui_r3_assert(
        str_contains($home, $marker),
        "dashboard card marker: {$marker}"
    );
}

echo "PASS: Owner Control Dashboard UI R3 contract\n";
echo "OWNER_ONLY=true\n";
echo "DEFAULT_OFF=true\n";
echo "API_GATE_INDEPENDENT=true\n";
echo "METHODS=GET,HEAD\n";
echo "DOM_RENDERING=textContent\n";
echo "METRICS=6\n";
echo "LEGACY_ADMIN_API=none\n";
echo "DB_ACCESS_FROM_UI=none\n";
echo "PRODUCTION_ACCESS=NO\n";
