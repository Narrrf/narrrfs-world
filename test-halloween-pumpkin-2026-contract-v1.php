<?php
/**
 * Halloween Pumpkin HTTP/session and Profile client contract checks.
 * This source-only test never opens an application, local, or production DB.
 */
declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-session.php';

function halloween26_contract_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

$trusted = [
    'discord_id' => '111111111111111111',
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
];
halloween26_contract_assert(
    narrrfs_session_has_trusted_discord_oauth_provenance($trusted),
    'trusted OAuth provenance accepts a valid server-derived Discord identity'
);
halloween26_contract_assert(
    !narrrfs_session_has_trusted_discord_oauth_provenance(['discord_id' => '111111111111111111']),
    'numeric session identity without OAuth provenance is denied'
);
halloween26_contract_assert(
    !narrrfs_session_has_trusted_discord_oauth_provenance([
        'discord_id' => '111111111111111111',
        NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => 'forged_or_legacy',
    ]),
    'forged or legacy provenance is denied'
);
halloween26_contract_assert(
    !narrrfs_session_has_trusted_discord_oauth_provenance([
        'discord_id' => 'not-a-discord-id',
        NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
    ]),
    'malformed identity is denied even with the canonical provenance marker'
);

$endpoint = file_get_contents(__DIR__ . '/api/user/halloween-pumpkin-2026.php');
$client = file_get_contents(__DIR__ . '/public/js/halloween-pumpkin-2026.js');
$profile = file_get_contents(__DIR__ . '/public/profile.html');
halloween26_contract_assert(is_string($endpoint) && is_string($client) && is_string($profile), 'Pumpkin endpoint and Profile sources are readable');
halloween26_contract_assert(str_contains($endpoint, "require_once __DIR__ . '/../config/admin-session.php';"), 'Pumpkin endpoint uses the trusted-session foundation');
$provenanceCheck = strpos($endpoint, 'narrrfs_session_has_trusted_discord_oauth_provenance($_SESSION)');
$databaseOpen = strpos($endpoint, "new PDO('sqlite:'");
halloween26_contract_assert($provenanceCheck !== false && $databaseOpen !== false && $provenanceCheck < $databaseOpen, 'untrusted sessions fail before database dispatch');
halloween26_contract_assert(str_contains($endpoint, "HTTP_ORIGIN") && str_contains($endpoint, "halloween26_csrf") && str_contains($endpoint, "unset(\$_SESSION['halloween26_csrf'])"), 'POST retains origin validation and one-time CSRF consumption');
halloween26_contract_assert(str_contains($client, "if (busy || claimed || !csrfToken) return;") && str_contains($client, 'csrfToken = null;'), 'Profile client disables duplicate submit before dispatch');
halloween26_contract_assert(str_contains($client, 'await refreshStatus();') && str_contains($client, 'body: JSON.stringify({event: eventKey})'), 'failed claim recovery re-reads server state and submits no browser-selected identity or reward value');
halloween26_contract_assert(str_contains($profile, 'js/halloween-pumpkin-2026.js'), 'Profile loads the dedicated Pumpkin client contract');

echo "PASS: Halloween Pumpkin session and Profile contract\n";
