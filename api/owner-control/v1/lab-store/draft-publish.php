<?php
declare(strict_types=1);
require_once __DIR__.'/../../../config/admin-authorization.php'; $actor=narrrfs_require_admin_request('owner_control.lab_store_publish.create.v1');
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, max-age=0');
require_once __DIR__.'/../../../config/database.php'; require_once __DIR__.'/../../../lib/owner-lab-store-publish-v1.php';
try { $input=json_decode((string)file_get_contents('php://input'),true,8,JSON_THROW_ON_ERROR); if(!is_array($input)||array_is_list($input))throw new JsonException(); $db=new SQLite3(getDatabasePath(),SQLITE3_OPEN_READWRITE); $result=narrrfs_lab_publish($db,$input,$actor); $db->close(); echo json_encode(['success'=>true,'offer_id'=>$result['offer_id'],'idempotent_replay'=>$result['replayed']]); }
catch(JsonException){http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_request']);}
catch(NarrrfsLabPublishException $e){if(isset($db))$db->close();http_response_code($e->status);echo json_encode(['success'=>false,'error'=>$e->publicCode]);}
catch(Throwable){if(isset($db))$db->close();http_response_code(500);echo json_encode(['success'=>false,'error'=>'publish_unavailable']);}
