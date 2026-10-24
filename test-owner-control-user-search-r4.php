<?php
declare(strict_types=1);
// R4.1 source-level contract: no API or real database access.
function r4_api_assert(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}
$path = __DIR__ . '/api/config/admin-route-policies.php';
$key = 'owner_control.user_search.v1';
putenv('NARRRFS_OWNER_USER_SEARCH_ENABLED');
$disabled = require $path;
r4_api_assert(($disabled['default'] ?? '') === 'deny', 'default deny');
r4_api_assert(($disabled['routes'][$key]['enabled'] ?? null) === false, 'default off');
putenv('NARRRFS_OWNER_USER_SEARCH_ENABLED=1');
$enabled = require $path;
$expected = ['enabled' => true, 'ownerOnly' => true, 'mutating' => false,
    'methods' => ['GET', 'HEAD'], 'csrf' => false, 'contentTypes' => [],
    'origins' => [], 'allowRefererFallback' => false];
r4_api_assert(($enabled['routes'][$key] ?? null) === $expected, 'exact route policy');
putenv('NARRRFS_OWNER_USER_SEARCH_ENABLED');
$source = file_get_contents(__DIR__ . '/api/owner-control/v1/user-search.php');
r4_api_assert(is_string($source), 'source readable');
foreach (["define('NARRRFS_SESSION_READ_ONLY', true)", 'narrrfs_require_admin_request',
    "'owner_control.user_search.v1'", 'SQLITE3_OPEN_READONLY', 'PRAGMA query_only = ON',
    'getDatabasePath()', 'SELECT discord_id, username, created_at FROM tbl_users',
    'LIMIT :limit OFFSET :offset', 'SQLITE3_TEXT', 'SQLITE3_INTEGER',
    "'writes' => false", 'has_more', "'created_at'", "'invalid_query'",
    "'invalid_pagination'", "'search_unavailable'"] as $marker) {
    r4_api_assert(str_contains($source, $marker), 'required: ' . $marker);
}
foreach (['/api/admin/', 'INSERT INTO', 'UPDATE tbl_', 'DELETE FROM', 'REPLACE INTO',
    'SQLITE3_OPEN_READWRITE', 'ATTACH DATABASE', 'discord.com/api', 'avatar_url',
    'twitter_username', 'tbl_user_scores', 'error_log($query'] as $forbidden) {
    r4_api_assert(stripos($source, $forbidden) === false, 'forbidden: ' . $forbidden);
}
echo "PASS: Owner Control User Search R4 contract\n";
