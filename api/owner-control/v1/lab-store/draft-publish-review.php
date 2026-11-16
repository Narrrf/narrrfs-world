<?php
declare(strict_types=1);
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__.'/../../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.lab_store_publish.review.v1');
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, max-age=0');
if (strtoupper((string)($_SERVER['REQUEST_METHOD']??''))==='HEAD') exit;
$id=filter_input(INPUT_GET,'draft_id',FILTER_VALIDATE_INT); if(!$id||$id<1){http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_draft_id']);exit;}
require_once __DIR__.'/../../../config/database.php'; require_once __DIR__.'/../../../lib/owner-lab-store-publish-v1.php';
try { $db=new SQLite3(getDatabasePath(),SQLITE3_OPEN_READONLY); $db->enableExceptions(true); $db->exec('PRAGMA query_only=ON'); $snapshot=narrrfs_lab_publish_snapshot($db,$id); $q=$db->prepare('SELECT 1 FROM tbl_owner_control_lab_store_publish_audit WHERE draft_id=:id'); $q->bindValue(':id',$id,SQLITE3_INTEGER); $res=$q->execute(); $already=$res->fetchArray(SQLITE3_NUM); $res->finalize(); if($already)throw new NarrrfsLabPublishException(409,'draft_already_published'); $q=$db->prepare('SELECT 1 FROM tbl_mouse_lp_store_offers WHERE source_type=:type AND source_item_id=:id'); $q->bindValue(':type',$snapshot['review']['source']['type'],SQLITE3_TEXT); $q->bindValue(':id',$snapshot['review']['source']['id'],SQLITE3_INTEGER); $res=$q->execute(); $already=$res->fetchArray(SQLITE3_NUM); $res->finalize(); if($already)throw new NarrrfsLabPublishException(409,'offer_already_exists'); echo json_encode(['success'=>true]+$snapshot,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE); $db->close(); }
catch(NarrrfsLabPublishException $e){http_response_code($e->status);echo json_encode(['success'=>false,'error'=>$e->publicCode]);}
catch(Throwable){http_response_code(503);echo json_encode(['success'=>false,'error'=>'review_unavailable']);}
