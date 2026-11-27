<?php
declare(strict_types=1);

// Focused regression test for:
// 1. Public Discord 24h (86400s) PHP session floor (session.php + Dockerfile)
// 2. Owner Control 12h (43200s) idle & absolute window on read-only pages
// 3. Silent Discord OAuth prompt=none with state-gated prompt=consent fallback

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-session.php';

function session_oauth_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

// 1. Public 24h session bootstrap contract
session_oauth_test_assert(
    defined('NARRRFS_PUBLIC_SESSION_LIFETIME_SECONDS') && NARRRFS_PUBLIC_SESSION_LIFETIME_SECONDS === 86400,
    'public Discord session lifetime constant is 86400 seconds (24h)'
);
session_oauth_test_assert(
    ini_get('session.gc_maxlifetime') === '86400',
    'session.gc_maxlifetime is set to 86400 seconds'
);
session_oauth_test_assert(
    ini_get('session.cookie_lifetime') === '86400',
    'session.cookie_lifetime is set to 86400 seconds'
);
$cookieParams = session_get_cookie_params();
session_oauth_test_assert(
    ($cookieParams['lifetime'] ?? null) === 86400,
    'session cookie params configure an 86400-second lifetime'
);

// Verify narrrfs_touch_session updates last_seen without errors in CLI
narrrfs_touch_session();
session_oauth_test_assert(
    is_int($_SESSION['narrrfs_last_seen_at'] ?? null),
    'narrrfs_touch_session records integer last_seen timestamp'
);

// 2. Owner Control 12h read-only session behavior (e.g. bug-tracker.php)
session_oauth_test_assert(
    NARRRFS_ADMIN_AUTH_IDLE_SECONDS === 43200,
    'owner idle timeout is 43200 seconds (12h)'
);
session_oauth_test_assert(
    NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS === 43200,
    'owner absolute timeout is 43200 seconds (12h)'
);
session_oauth_test_assert(
    NARRRFS_ADMIN_CSRF_TTL_SECONDS === 300,
    'owner one-time CSRF token remains strict at 300 seconds (5m)'
);

$t = 2000000000;
$readOnlyOwnerSession = [];
$established = narrrfs_establish_discord_oauth_session(
    $readOnlyOwnerSession,
    ['id' => '111111111111111111', 'username' => 'owner'],
    static fn(): bool => true,
    $t
);
session_oauth_test_assert($established, 'owner session established cleanly');

// Simulate read-only browsing (no narrrfs_touch_admin_authority calls) across 30m, 2h, 11h59m59s, and 12h
session_oauth_test_assert(
    narrrfs_admin_session_is_current($readOnlyOwnerSession, $t + 1800),
    'read-only owner session remains valid after 30 minutes without writes'
);
session_oauth_test_assert(
    narrrfs_admin_session_is_current($readOnlyOwnerSession, $t + 7200),
    'read-only owner session remains valid after 2 hours without writes'
);
session_oauth_test_assert(
    narrrfs_admin_session_is_current($readOnlyOwnerSession, $t + 43199),
    'read-only owner session remains valid right before 12h expiry'
);
session_oauth_test_assert(
    !narrrfs_admin_session_is_current($readOnlyOwnerSession, $t + 43200),
    'read-only owner session fails closed at exact 12h (43200s) boundary'
);

// 3. Dockerfile global PHP session floor contract
$dockerfile = file_get_contents(__DIR__ . '/Dockerfile');
session_oauth_test_assert(is_string($dockerfile), 'Dockerfile is readable');
foreach ([
    "'session.upload_progress.enabled=0'",
    "'session.gc_maxlifetime=86400'",
    "'session.cookie_lifetime=86400'",
    "'session.use_strict_mode=1'",
    "'session.use_only_cookies=1'",
    "'session.cookie_httponly=1'",
    "'session.cookie_samesite=Lax'",
    'narrrfs-security.ini',
    'ini_get("session.gc_maxlifetime") !== "86400"',
    'ini_get("session.cookie_lifetime") !== "86400"',
] as $dockerMarker) {
    session_oauth_test_assert(
        str_contains($dockerfile, $dockerMarker),
        'Dockerfile configures container PHP security/session floor: ' . $dockerMarker
    );
}
session_oauth_test_assert(
    !str_contains($dockerfile, "'session.cookie_secure=1'"),
    'Dockerfile does not hardcode global cookie_secure=1 so localhost HTTP stays compatible'
);

// 4. Silent prompt=none OAuth initializer and state-gated callback fallback
$loginSource = file_get_contents(__DIR__ . '/api/discord-login.php');
$callbackSource = file_get_contents(__DIR__ . '/api/auth/callback.php');
session_oauth_test_assert(is_string($loginSource) && is_string($callbackSource), 'OAuth sources are readable');

session_oauth_test_assert(
    str_contains($loginSource, "\$_GET['prompt'] === 'consent'") &&
        str_contains($loginSource, ": 'none';") &&
        str_contains($loginSource, "'prompt' => \$promptMode"),
    'discord-login.php defaults to prompt=none and honors prompt=consent retry'
);

$consumePos = strpos($callbackSource, 'narrrfs_consume_oauth_state($_SESSION, $receivedState)');
$errorPos = strpos($callbackSource, "\$oauthError = isset(\$_GET['error'])");
$tokenPos = strpos($callbackSource, 'CURLOPT_URL');
session_oauth_test_assert(
    $consumePos !== false && $errorPos !== false && $tokenPos !== false && $consumePos < $errorPos && $errorPos < $tokenPos,
    'callback handles prompt=none error fallbacks strictly after state consumption and before token exchange'
);
foreach ([
    "'consent_required'",
    "'interaction_required'",
    "'login_required'",
    '/api/discord-login.php?prompt=consent&redirect=',
    "'access_denied'",
    'rawurlencode($retryRedirect)',
] as $callbackMarker) {
    session_oauth_test_assert(
        str_contains($callbackSource, $callbackMarker),
        'callback handles fallback/cancel marker cleanly: ' . $callbackMarker
    );
}

echo "PASS: 24h public session, 12h admin session, and silent prompt=none OAuth contract\n";
