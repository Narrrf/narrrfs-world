<?php
declare(strict_types=1);
define('NARRRFS_SESSION_READ_ONLY',true);
require_once __DIR__.'/../../../config/admin-authorization.php'; narrrfs_require_admin_request('owner_control.lab_store_publish.history.v1');
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, max-age=0');
if(strtoupper((string)($_SERVER['REQUEST_METHOD']??''))==='HEAD')exit;
require_once __DIR__.'/../../../config/database.php';
try{$db=new SQLite3(getDatabasePath(),SQLITE3_OPEN_READONLY);$db->enableExceptions(true);$db->exec('PRAGMA query_only=ON');$r=$db->query("SELECT 'published' AS action,offer_id,draft_id,created_at FROM tbl_owner_control_lab_store_publish_audit UNION ALL SELECT CASE WHEN resulting_is_active=1 THEN 'activated' ELSE 'disabled' END,offer_id,NULL,created_at FROM tbl_owner_control_lab_store_offer_state_audit UNION ALL SELECT 'price_stock_changed',offer_id,NULL,created_at FROM tbl_owner_control_lab_store_offer_commercial_audit ORDER BY created_at DESC LIMIT 50");$rows=[];while($row=$r->fetchArray(SQLITE3_ASSOC))$rows[]=$row;$db->close();echo json_encode(['success'=>true,'mode'=>'read_only','events'=>$rows],JSON_UNESCAPED_SLASHES);}
catch(Throwable){http_response_code(503);echo json_encode(['success'=>false,'error'=>'history_unavailable']);}
