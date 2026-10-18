<?php
declare(strict_types=1);

function oauth_inventory_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$root = __DIR__;
$loginSource = file_get_contents($root . '/api/discord-login.php');
$callbackSource = file_get_contents($root . '/api/auth/callback.php');
$sessionSource = file_get_contents($root . '/api/config/admin-session.php');
oauth_inventory_test_assert(is_string($loginSource) && is_string($callbackSource) && is_string($sessionSource), 'B1 sources are readable');

oauth_inventory_test_assert(str_contains($loginSource, "'state' => \$state"), 'canonical initializer always submits state');
oauth_inventory_test_assert(str_contains($callbackSource, "\$stateDecision['status'] === 'missing'"), 'callback distinguishes direct callers without state');
oauth_inventory_test_assert(str_contains($callbackSource, 'redirect=%2Fpublic%2Fprofile.html'), 'direct callers restart through fixed canonical initializer');
oauth_inventory_test_assert(strpos($callbackSource, 'narrrfs_consume_oauth_state') < strpos($callbackSource, 'CURLOPT_URL'), 'callback state gate precedes token exchange');

$directAuthorizationFiles = [];
foreach ([$root . '/api', $root . '/public'] as $scanRoot) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getSize() > 8 * 1024 * 1024) {
            continue;
        }
        $extension = strtolower($file->getExtension());
        if (!in_array($extension, ['php', 'html', 'js'], true)) {
            continue;
        }
        $contents = file_get_contents($file->getPathname());
        if (!is_string($contents) || !str_contains($contents, 'discord.com/oauth2/authorize')) {
            continue;
        }
        $directAuthorizationFiles[] = $file->getPathname();
        if (realpath($file->getPathname()) === realpath($root . '/api/discord-login.php')) {
            oauth_inventory_test_assert(str_contains($contents, "\$oauth['redirect_uri']"), 'canonical initializer uses reviewed server configuration');
            continue;
        }
        oauth_inventory_test_assert(
            str_contains($contents, '/api/auth/callback.php') ||
            str_contains($contents, '%2Fapi%2Fauth%2Fcallback.php') ||
            str_contains($contents, 'redirectUri'),
            'direct OAuth source uses the reviewed callback: ' . $file->getPathname()
        );
    }
}
oauth_inventory_test_assert($directAuthorizationFiles !== [], 'legacy direct OAuth callers remain explicitly inventoried');

oauth_inventory_test_assert(!preg_match('/\b(?:PDO|SQLite3)\b|sqlite:|\/db\//i', $sessionSource), 'trusted session foundation has no database coupling');
oauth_inventory_test_assert(!preg_match('/\b(?:curl_|file_get_contents\s*\(\s*[\'\"]https?:)/i', $sessionSource), 'trusted session foundation has no network coupling');
oauth_inventory_test_assert(!str_contains($sessionSource, 'NARRRFS_ADMIN_DISCORD_IDS'), 'session provenance does not embed or consult owner allowlist values');

echo 'PASS: OAuth entrypoint inventory B1 (' . count($directAuthorizationFiles) . " legacy direct sources use fail-closed restart)\n";
