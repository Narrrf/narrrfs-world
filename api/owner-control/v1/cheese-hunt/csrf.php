<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/admin-authorization.php';

narrrfs_require_admin_request('owner_control.cheese_hunt.csrf.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

try {
    $in = json_decode((string)file_get_contents('php://input'), true, 4, JSON_THROW_ON_ERROR);
    if (!is_array($in)) {
        throw new InvalidArgumentException('Invalid payload.');
    }

    $action = $in['action'] ?? '';
    $map = [
        'mission' => [
            'scope' => 'owner_control.cheese_hunt.mission.v1',
            'gate' => 'NARRRFS_OWNER_CHEESE_HUNT_WRITE_ENABLED',
        ],
        'review' => [
            'scope' => 'owner_control.cheese_hunt.review.v1',
            'gate' => 'NARRRFS_OWNER_CHEESE_HUNT_CLAIM_REVIEW_ENABLED',
        ],
    ];

    if (!is_string($action) || !isset($map[$action])) {
        throw new InvalidArgumentException('Unsupported action.');
    }

    if (getenv($map[$action]['gate']) !== '1') {
        narrrfs_admin_fail_closed(404, 'admin_route_unavailable');
    }

    $token = narrrfs_issue_admin_csrf_token($_SESSION, null, null, $map[$action]['scope']);
    session_write_close();
    echo json_encode(['success' => true, 'csrf_token' => $token], JSON_UNESCAPED_SLASHES);
} catch (Throwable) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'csrf_unavailable']);
}
