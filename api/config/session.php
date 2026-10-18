<?php
// 🔐 Narrrfs World Shared Session Bootstrap
// DEVS FOR DECADES:
// Use this file before every session_start() for Discord-authenticated pages/APIs.
// This keeps Discord login stable across profile, lab, games, and user APIs.
// It does not authenticate the user by itself; it only configures the PHP session.

if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}

$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

$sessionLifetimeSeconds = 60 * 60 * 24 * 30; // 30 days

ini_set('session.gc_maxlifetime', (string)$sessionLifetimeSeconds);
ini_set('session.cookie_lifetime', (string)$sessionLifetimeSeconds);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');

session_set_cookie_params([
    'lifetime' => $sessionLifetimeSeconds,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

$readOnlySession = defined('NARRRFS_SESSION_READ_ONLY') && NARRRFS_SESSION_READ_ONLY === true;
if ($readOnlySession) {
    // Owner canary reads trusted session identity without refreshing or rewriting it.
    session_start(['read_and_close' => true]);
} else {
    session_start();
}

/**
 * Refresh the active session cookie lifetime.
 * Plain language:
 * Every successful session check pushes the login cookie forward again.
 */
function narrrfs_touch_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION['narrrfs_last_seen_at'] = time();
}

/** Expire the active PHP session cookie with the same attributes used at creation. */
function narrrfs_expire_session_cookie(): void {
    if (!filter_var(ini_get('session.use_cookies'), FILTER_VALIDATE_BOOLEAN)) {
        return;
    }

    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'] ?? '/',
        'domain' => $cookie['domain'] ?? '',
        'secure' => (bool)($cookie['secure'] ?? false),
        'httponly' => (bool)($cookie['httponly'] ?? true),
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}
