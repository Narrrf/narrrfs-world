<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.player_adjustment.csrf.v1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
try {
    $data=json_decode((string)file_get_contents('php://input'),true,4,JSON_THROW_ON_ERROR);
    if (!is_array($data) || count($data)!==1 || !isset($data['action']) || !in_array($data['action'],['points','inventory'],true)) throw new InvalidArgumentException();
} catch (JsonException|InvalidArgumentException) {
    if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
    http_response_code(400); echo json_encode(['success'=>false,'error'=>'invalid_action']); exit;
}
try {
    $scope='owner_control.player_adjustment.'.$data['action'].'.v1';
    $policies=narrrfs_load_admin_route_policies();
    if (!is_array(narrrfs_validate_admin_route_policy($policies['routes'][$scope] ?? null))) {
        if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
        http_response_code(404); echo json_encode(['success'=>false,'error'=>'admin_route_unavailable']); exit;
    }
    $token=narrrfs_issue_admin_csrf_token($_SESSION,null,null,$scope);
    if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
    echo json_encode(['success'=>true,'csrf_token'=>$token,'expires_in'=>NARRRFS_ADMIN_CSRF_TTL_SECONDS]);
} catch (Throwable) {
    if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
    http_response_code(500); echo json_encode(['success'=>false,'error'=>'csrf_unavailable']);
}
