<?php
declare(strict_types=1);
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.lab_store_drafts.read.v1');
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, max-age=0'); header('X-Content-Type-Options: nosniff');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') exit;
$id=filter_input(INPUT_GET,'draft_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]); if($id===false||$id===null){http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_draft_id']);exit;}
require_once __DIR__ . '/../../../config/database.php'; require_once __DIR__ . '/../../../lib/owner-lab-store-drafts-v1.php';
try{$path=getDatabasePath();if(!is_file($path)||!is_readable($path))throw new RuntimeException();$db=new SQLite3($path,SQLITE3_OPEN_READONLY);try{if(!$db->exec('PRAGMA query_only = ON'))throw new RuntimeException();$s=$db->prepare('SELECT draft_id,title,category,description,media_reference,state,version,created_at,updated_at FROM tbl_owner_control_lab_store_drafts WHERE draft_id=:id AND state=\'draft\' LIMIT 1');$s->bindValue(':id',$id,SQLITE3_INTEGER);$row=narrrfs_lab_store_draft_fetch_one($s);if($row===null){http_response_code(404);echo json_encode(['success'=>false,'error'=>'draft_not_found']);}else echo json_encode(['success'=>true,'capability'=>'owner_control_lab_store_draft_details','version'=>1,'mode'=>'draft_only','writes'=>false,'draft'=>narrrfs_lab_store_draft_public($row)],JSON_UNESCAPED_SLASHES);}finally{$db->close();}}catch(Throwable $e){error_log('[OWNER CONTROL] Lab Store draft details unavailable.');http_response_code(503);echo json_encode(['success'=>false,'error'=>'lab_store_drafts_unavailable']);}
