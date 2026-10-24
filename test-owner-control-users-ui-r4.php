<?php
declare(strict_types=1);
// R4.1 presentation source contract: no HTTP or application DB access.
function r4_ui_assert(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}
$registryPath = __DIR__ . '/api/config/admin-route-policies.php';
$key = 'owner_control.users_ui.v1';
putenv('NARRRFS_OWNER_USERS_UI_ENABLED');
putenv('NARRRFS_OWNER_USER_SEARCH_ENABLED');
$disabled = require $registryPath;
r4_ui_assert(($disabled['routes'][$key]['enabled'] ?? null) === false, 'default off');
putenv('NARRRFS_OWNER_USERS_UI_ENABLED=1');
$enabled = require $registryPath;
$expected = ['enabled' => true, 'ownerOnly' => true, 'mutating' => false,
    'methods' => ['GET', 'HEAD'], 'csrf' => false, 'contentTypes' => [],
    'origins' => [], 'allowRefererFallback' => false];
r4_ui_assert(($enabled['routes'][$key] ?? null) === $expected, 'exact policy');
r4_ui_assert(($enabled['routes']['owner_control.user_search.v1']['enabled'] ?? null) === false, 'independent gate');
putenv('NARRRFS_OWNER_USERS_UI_ENABLED');
$page = file_get_contents(__DIR__ . '/owner-control/v1/users.php');
$home = file_get_contents(__DIR__ . '/owner-control/v1/index.php');
r4_ui_assert(is_string($page) && is_string($home), 'files readable');
foreach (["define('NARRRFS_SESSION_READ_ONLY', true)", "'owner_control.users_ui.v1'",
    '/api/owner-control/v1/user-search.php', "credentials: 'same-origin'",
    "cache: 'no-store'", 'AbortController', 'textContent', 'replaceChildren',
    'URLSearchParams', 'Not recorded', 'pageSize = 25'] as $marker) {
    r4_ui_assert(str_contains($page, $marker), 'page marker: ' . $marker);
}
foreach (['/api/admin/', 'innerHTML', 'insertAdjacentHTML', 'document.write',
    'localStorage', 'sessionStorage', 'new SQLite3', 'new PDO', 'discord.com/api'] as $forbidden) {
    r4_ui_assert(stripos($page, $forbidden) === false, 'forbidden: ' . $forbidden);
}
foreach (['$userManagementAvailable', 'NARRRFS_OWNER_USERS_UI_ENABLED',
    'NARRRFS_OWNER_USER_SEARCH_ENABLED', "'name' => 'User Management'",
    "'href' => \$userManagementAvailable"] as $marker) {
    r4_ui_assert(str_contains($home, $marker), 'home marker: ' . $marker);
}
echo "PASS: Owner Control Users UI R4 contract\n";
