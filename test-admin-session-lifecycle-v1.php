<?php
declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-session.php';

function admin_session_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$now = 2000000000;
$session = [
    'normal_user_preference' => 'preserved',
    'discord_id' => '111111111111111111',
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => 'legacy',
    NARRRFS_ADMIN_CSRF_SESSION_KEY => str_repeat('a', 64),
];
$rotations = 0;
$established = narrrfs_establish_discord_oauth_session(
    $session,
    ['id' => '222222222222222222', 'username' => 'server-derived'],
    static function () use (&$rotations): bool {
        $rotations++;
        return true;
    },
    $now
);
admin_session_test_assert($established, 'validated server identity establishes the session');
admin_session_test_assert($rotations === 1, 'session ID rotates exactly once after identity validation');
admin_session_test_assert($session['discord_id'] === '222222222222222222', 'server identity replaces prior client/session identity');
admin_session_test_assert($session[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY] === 'discord_oauth_v1', 'provenance value is exact');
admin_session_test_assert($session[NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY] === $now, 'issued-at is exact integer completion time');
admin_session_test_assert($session[NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY] === $now, 'last-seen starts at issued-at');
admin_session_test_assert($session[NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY] === $now + 43200, 'absolute expiry is exact');
admin_session_test_assert($session['normal_user_preference'] === 'preserved', 'unrelated normal-user session data remains');
admin_session_test_assert(!isset($session[NARRRFS_ADMIN_CSRF_SESSION_KEY]), 'OAuth rotation clears prior CSRF state');
admin_session_test_assert(narrrfs_admin_session_is_current($session, $now + 1799), 'owner authority is current before idle timeout');
admin_session_test_assert(!narrrfs_admin_session_is_current($session, $now + 1800), 'owner authority expires at idle timeout');

$touched = $session;
narrrfs_touch_admin_authority($touched, $now + 1200);
admin_session_test_assert($touched[NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY] === $now + 1200, 'successful authorization may advance last-seen');
admin_session_test_assert($touched[NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY] === $now + 43200, 'touch never extends absolute expiry');
admin_session_test_assert(!narrrfs_admin_session_is_current($touched, $now + 43200), 'absolute expiry is final');

$invalidIdentity = [];
$invalidRotations = 0;
admin_session_test_assert(!narrrfs_establish_discord_oauth_session(
    $invalidIdentity,
    ['id' => 'client-value'],
    static function () use (&$invalidRotations): bool {
        $invalidRotations++;
        return true;
    },
    $now
), 'invalid server identity is denied');
admin_session_test_assert($invalidRotations === 0, 'invalid identity cannot rotate or create trusted fields');

$rotationFailure = [
    'discord_id' => '111111111111111111',
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
];
admin_session_test_assert(!narrrfs_establish_discord_oauth_session(
    $rotationFailure,
    ['id' => '222222222222222222'],
    static fn(): bool => false,
    $now
), 'rotation failure denies establishment');
admin_session_test_assert(!isset($rotationFailure['discord_id'], $rotationFailure[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY]), 'rotation failure leaves no trusted authority');

foreach ([
    [NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY, 'not-an-int'],
    [NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY, $now - 1],
    [NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY, $now + 43199],
] as [$key, $value]) {
    $malformed = $session;
    $malformed[$key] = $value;
    admin_session_test_assert(!narrrfs_admin_session_is_current($malformed, $now), 'malformed timestamp fails closed: ' . $key);
}

$csrfSession = [
    NARRRFS_ADMIN_CSRF_SESSION_KEY => str_repeat('a', 64),
    NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY => $now,
];
$validLogoutRequest = [
    'method' => 'POST',
    'contentType' => 'application/json',
    'origin' => 'https://narrrfs.world',
    'csrfToken' => str_repeat('a', 64),
];
admin_session_test_assert(narrrfs_logout_decision($csrfSession, $validLogoutRequest, 'https://narrrfs.world', $now)['allowed'], 'valid POST logout foundation is accepted');
admin_session_test_assert(narrrfs_logout_decision($csrfSession, ['method' => 'GET'], 'https://narrrfs.world', $now)['status'] === 405, 'logout rejects GET');
$missingToken = $validLogoutRequest;
unset($missingToken['csrfToken']);
admin_session_test_assert(narrrfs_logout_decision($csrfSession, $missingToken, 'https://narrrfs.world', $now)['status'] === 403, 'logout rejects missing CSRF');
$invalidToken = $validLogoutRequest;
$invalidToken['csrfToken'] = str_repeat('b', 64);
admin_session_test_assert(narrrfs_logout_decision($csrfSession, $invalidToken, 'https://narrrfs.world', $now)['status'] === 403, 'logout rejects invalid CSRF');
narrrfs_consume_admin_csrf_session($csrfSession);
admin_session_test_assert(narrrfs_logout_decision($csrfSession, $validLogoutRequest, 'https://narrrfs.world', $now)['status'] === 403, 'logout rejects replay after token consumption');

$_SESSION['normal_session_regression'] = true;
narrrfs_touch_session();
admin_session_test_assert($_SESSION['normal_session_regression'] === true, 'normal session data survives shared-session touch');
admin_session_test_assert(is_int($_SESSION['narrrfs_last_seen_at'] ?? null), 'normal session touch remains available');

echo "PASS: admin session and logout lifecycle B1\n";
