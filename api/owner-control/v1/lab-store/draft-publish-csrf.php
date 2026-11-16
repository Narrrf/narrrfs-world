<?php
declare(strict_types=1);
require_once __DIR__.'/../../../config/admin-authorization.php'; narrrfs_require_admin_request('owner_control.lab_store_publish.csrf.v1');
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, max-age=0');
try { $body=json_decode((string)file_get_contents('php://input'),true,4,JSON_THROW_ON_ERROR); if($body!==['action'=>'publish']) throw new InvalidArgumentException(); }
catch(JsonException|InvalidArgumentException){if(session_status()===PHP_SESSION_ACTIVE)session_write_close();http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_action']);exit;}
try { $token=narrrfs_issue_admin_csrf_token($_SESSION,null,null,'owner_control.lab_store_publish.create.v1'); if(session_status()===PHP_SESSION_ACTIVE)session_write_close();echo json_encode(['success'=>true,'csrf_token'=>$token,'expires_in'=>NARRRFS_ADMIN_CSRF_TTL_SECONDS]); }
catch(Throwable){if(session_status()===PHP_SESSION_ACTIVE)session_write_close();http_response_code(500);echo json_encode(['success'=>false,'error'=>'csrf_unavailable']);}
