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
$logoutSource = file_get_contents($root . '/api/auth/logout.php');
$logoutCsrfSource = file_get_contents($root . '/api/auth/logout-csrf.php');
$logoutCallerSource = file_get_contents($root . '/public/admin/manage-scores.html');
oauth_inventory_test_assert(
    is_string($loginSource) && is_string($callbackSource) && is_string($sessionSource) &&
    is_string($logoutSource) && is_string($logoutCsrfSource) && is_string($logoutCallerSource),
    'B1 sources are readable'
);

oauth_inventory_test_assert(str_contains($loginSource, "'state' => \$state"), 'canonical initializer always submits state');
oauth_inventory_test_assert(str_contains($callbackSource, "\$stateDecision['status'] === 'missing'"), 'callback distinguishes direct callers without state');
oauth_inventory_test_assert(str_contains($callbackSource, 'redirect=%2Fpublic%2Fprofile.html'), 'direct callers restart through fixed canonical initializer');
oauth_inventory_test_assert(strpos($callbackSource, 'narrrfs_consume_oauth_state') < strpos($callbackSource, 'CURLOPT_URL'), 'callback state gate precedes token exchange');

$canonicalPlayerLoginFiles = [
    $root . '/public/js/cheese-auth-indicator.js',
    $root . '/public/profile.html',
    $root . '/public/lab.html',
    $root . '/public/stake-lab.html',
    $root . '/public/Bingo.html',
    $root . '/public/labyrinth-blast/assets/index-Cy5G_4hB.js',
];

foreach ($canonicalPlayerLoginFiles as $playerLoginFile) {
    $playerLoginSource = file_get_contents($playerLoginFile);

    oauth_inventory_test_assert(
        is_string($playerLoginSource),
        'canonical player login source is readable: ' . $playerLoginFile
    );

    oauth_inventory_test_assert(
        !str_contains($playerLoginSource, 'discord.com/oauth2/authorize'),
        'player login source does not bypass canonical OAuth issuer: ' . $playerLoginFile
    );

    oauth_inventory_test_assert(
        str_contains($playerLoginSource, '/api/discord-login.php'),
        'player login source uses canonical OAuth issuer: ' . $playerLoginFile
    );
}

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
oauth_inventory_test_assert(str_contains($logoutCallerSource, "fetch('/api/auth/logout-csrf.php'"), 'legacy logout caller obtains same-origin CSRF first');
oauth_inventory_test_assert(str_contains($logoutCallerSource, "method: 'POST'"), 'legacy logout caller uses POST');
oauth_inventory_test_assert(str_contains($logoutCallerSource, "'X-CSRF-Token': tokenResult.csrf_token"), 'legacy logout caller sends issued CSRF token');
oauth_inventory_test_assert(str_contains($logoutSource, 'narrrfs_consume_logout_csrf_session'), 'logout consumes only logout CSRF state');
oauth_inventory_test_assert(!str_contains($logoutSource, 'narrrfs_oauth_configuration()'), 'logout does not require OAuth credentials');
oauth_inventory_test_assert(!str_contains($logoutCsrfSource, 'narrrfs_oauth_configuration()'), 'logout CSRF issuer does not require OAuth credentials');
oauth_inventory_test_assert(!preg_match('/\b(?:PDO|SQLite3)\b|sqlite:|\/db\//i', $logoutCsrfSource), 'logout CSRF issuer has no database coupling');

echo 'PASS: OAuth entrypoint inventory B1 (' . count($directAuthorizationFiles) . " legacy direct sources use fail-closed restart)\n";
