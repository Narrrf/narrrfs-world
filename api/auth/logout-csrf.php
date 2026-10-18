<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/admin-session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$allowedOrigin = narrrfs_logout_allowed_origin_for_host((string)($_SERVER['HTTP_HOST'] ?? ''));
if ($allowedOrigin === null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'logout_unavailable']);
    exit;
}

$decision = narrrfs_logout_csrf_issue_decision(
    $_SESSION,
    [
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? '',
        'referer' => $_SERVER['HTTP_REFERER'] ?? '',
        'fetchSite' => $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '',
    ],
    $allowedOrigin
);
if (!$decision['allowed']) {
    http_response_code($decision['status']);
    if ($decision['status'] === 405) {
        header('Allow: GET');
    }
    echo json_encode(['success' => false, 'error' => $decision['code']]);
    exit;
}

try {
    $csrfToken = narrrfs_issue_logout_csrf_token($_SESSION);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'logout_unavailable']);
    exit;
}

echo json_encode([
    'success' => true,
    'csrf_token' => $csrfToken,
    'expires_in' => NARRRFS_LOGOUT_CSRF_TTL_SECONDS,
]);
