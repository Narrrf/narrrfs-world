<?php
declare(strict_types=1);

// 🔐 Narrrfs World Owner Control V2 — mutation CSRF issuer.
//
// DEVS FOR DECADES:
// This endpoint creates only the one-time CSRF token required by a later
// Owner Control mutation. It does not access SQLite, call Discord, or grant
// owner authority. Authorization must succeed before any token is created.
//
// Do NOT define NARRRFS_SESSION_READ_ONLY here. Token issuance intentionally
// writes the CSRF token and timestamp into the trusted server-side PHP session.

require_once __DIR__ . '/../../config/admin-authorization.php';

narrrfs_require_admin_request('owner_control.csrf.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$method = strtoupper(
    (string)($_SERVER['REQUEST_METHOD'] ?? '')
);

if ($method === 'HEAD') {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    exit;
}

try {
    $csrfToken = narrrfs_issue_admin_csrf_token($_SESSION);

    // Persist the new one-time token and release the PHP session lock before
    // returning it to the same authenticated Owner Control browser.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    echo json_encode(
        [
            'success' => true,
            'capability' => 'owner_control_csrf',
            'version' => 1,
            'csrf_token' => $csrfToken,
            'expires_in' => NARRRFS_ADMIN_CSRF_TTL_SECONDS,
        ],
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $error) {
    error_log(
        '[OWNER CONTROL] CSRF issuance failed: '
        . $error->getMessage()
    );

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'error' => 'csrf_unavailable',
        ],
        JSON_UNESCAPED_SLASHES
    );
}
