<?php
declare(strict_types=1);
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.lab_store_drafts.read.v1');
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, max-age=0'); header('X-Content-Type-Options: nosniff');
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') exit;
require_once __DIR__ . '/../../../config/database.php'; require_once __DIR__ . '/../../../lib/owner-lab-store-drafts-v1.php';
try { $path=getDatabasePath(); if(!is_file($path)||!is_readable($path)) throw new RuntimeException(); $db=new SQLite3($path,SQLITE3_OPEN_READONLY); try { if(!$db->exec('PRAGMA query_only = ON')) throw new RuntimeException(); $result=$db->query('SELECT draft_id,title,category,description,media_reference,state,version,created_at,updated_at FROM tbl_owner_control_lab_store_drafts WHERE state=\'draft\' ORDER BY updated_at DESC,draft_id DESC LIMIT 100'); if(!$result instanceof SQLite3Result) throw new RuntimeException(); $drafts=[]; while(($row=$result->fetchArray(SQLITE3_ASSOC))!==false)$drafts[]=narrrfs_lab_store_draft_public($row); $result->finalize(); echo json_encode(['success'=>true,'capability'=>'owner_control_lab_store_drafts_read','version'=>1,'mode'=>'draft_only','writes'=>false,'drafts'=>$drafts],JSON_UNESCAPED_SLASHES); } finally {$db->close();} } catch(Throwable $e){error_log('[OWNER CONTROL] Lab Store drafts read unavailable.');http_response_code(503);echo json_encode(['success'=>false,'error'=>'lab_store_drafts_unavailable']);}
