<?php
/** Scoped one-time CSRF issuer for Christmas policy stage / approve actions. */
declare(strict_types=1);

require_once __DIR__ . '/../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.christmas_policy.csrf.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

try {
    $in = json_decode((string)file_get_contents('php://input'), true, 4, JSON_THROW_ON_ERROR);
    if (!is_array($in)) {
        throw new InvalidArgumentException('invalid_request');
    }
    $action = $in['action'] ?? '';
    $map = [
        'stage' => [
            'scope' => 'owner_control.christmas_policy.stage.v1',
            'gate' => 'NARRRFS_OWNER_CHRISTMAS_POLICY_WRITE_ENABLED',
        ],
        'approve' => [
            'scope' => 'owner_control.christmas_policy.approve.v1',
            'gate' => 'NARRRFS_OWNER_CHRISTMAS_POLICY_APPROVE_ENABLED',
        ],
    ];
    if (!is_string($action) || !isset($map[$action])) {
        throw new InvalidArgumentException('unsupported_action');
    }
    if (getenv($map[$action]['gate']) !== '1') {
        narrrfs_admin_fail_closed(404, 'admin_route_unavailable');
    }
    $token = narrrfs_issue_admin_csrf_token($_SESSION, null, null, $map[$action]['scope']);
    session_write_close();
    echo json_encode(['success' => true, 'csrf_token' => $token], JSON_UNESCAPED_SLASHES);
} catch (Throwable) {
    http_response_code(400);
    echo '{"success":false,"error":"csrf_unavailable"}';
}
