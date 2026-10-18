<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/oauth.php';
require_once __DIR__ . '/../config/admin-session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
if ($method !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

try {
    $oauth = narrrfs_oauth_configuration();
} catch (Throwable) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'logout_unavailable']);
    exit;
}

$decision = narrrfs_logout_decision(
    $_SESSION,
    [
        'method' => $method,
        'contentType' => $_SERVER['CONTENT_TYPE'] ?? '',
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? '',
        'csrfToken' => $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null,
    ],
    (string)$oauth['base_url']
);
if (!$decision['allowed']) {
    http_response_code($decision['status']);
    if ($decision['status'] === 405) {
        header('Allow: POST');
    }
    echo json_encode(['success' => false, 'error' => $decision['code']]);
    exit;
}

narrrfs_consume_admin_csrf_session($_SESSION);
$_SESSION = [];
narrrfs_expire_session_cookie();

if (!session_destroy()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'logout_failed']);
    exit;
}

echo json_encode(['success' => true]);
