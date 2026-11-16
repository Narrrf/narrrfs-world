<?php
declare(strict_types=1);

/** A safe public error for the guarded catalog-backed publish operation. */
final class NarrrfsLabPublishException extends RuntimeException {
    public function __construct(public readonly int $status, public readonly string $publicCode) { parent::__construct($publicCode); }
}

/** Loads exactly the catalog fields that govern visible presentation and delivery. */
function narrrfs_lab_publish_source(SQLite3 $db, string $type, int $id): array {
    if ($type === 'normal_store') {
        $sql = 'SELECT item_id AS id,item_name AS title,image_url AS image,is_active,NULL AS is_visible,NULL AS trait_type,NULL AS trait_value,NULL AS image_type,NULL AS image_path FROM tbl_store_items WHERE item_id=:id AND COALESCE(is_active,0)=1';
    } elseif ($type === 'genetic_item') {
        $sql = 'SELECT catalog_id AS id,display_title AS title,preview_path AS image,is_active,is_visible,trait_type,trait_value,image_type,image_path FROM tbl_genetic_trait_catalog WHERE catalog_id=:id AND is_active=1 AND is_visible=1';
    } else { throw new NarrrfsLabPublishException(409, 'source_unavailable'); }
    $q=$db->prepare($sql); $q->bindValue(':id',$id,SQLITE3_INTEGER); $r=$q->execute(); $row=$r->fetchArray(SQLITE3_ASSOC); $r->finalize();
    if (!is_array($row)) throw new NarrrfsLabPublishException(409, 'source_unavailable');
    return ['type'=>$type,'id'=>(int)$row['id'],'title'=>(string)$row['title'],'image'=>$row['image'],'image_type'=>$row['image_type'],'image_path'=>$row['image_path'],'is_active'=>(int)$row['is_active'],'is_visible'=>$row['is_visible']===null?null:(int)$row['is_visible'],'trait_type'=>$row['trait_type'],'trait_value'=>$row['trait_value']];
}

/** Reads a draft and rejects proposals that cannot be expressed as an offer. */
function narrrfs_lab_publish_snapshot(SQLite3 $db, int $id): array {
    $q=$db->prepare("SELECT draft_id,version,state,title,category,description,media_reference,proposed_source_type,proposed_source_item_id,proposed_lp_price,proposed_dspoinc_price,proposed_stock FROM tbl_owner_control_lab_store_drafts WHERE draft_id=:id AND state='draft'");
    $q->bindValue(':id',$id,SQLITE3_INTEGER); $r=$q->execute(); $d=$r->fetchArray(SQLITE3_ASSOC); $r->finalize();
    if (!is_array($d)) throw new NarrrfsLabPublishException(404,'draft_not_found');
    $type=$d['proposed_source_type']; $source=(int)$d['proposed_source_item_id']; $lp=$d['proposed_lp_price']; $dsp=$d['proposed_dspoinc_price']; $stock=$d['proposed_stock'];
    if (!is_string($type) || $source<1 || $stock===null || (int)$stock<0 || ($lp===null && $dsp===null) || ($lp!==null && (int)$lp<1) || ($dsp!==null && (int)$dsp<1)) throw new NarrrfsLabPublishException(409,'draft_incomplete');
    $catalog=narrrfs_lab_publish_source($db,$type,$source);
    $review=['draft_id'=>(int)$d['draft_id'],'version'=>(int)$d['version'],'source'=>$catalog,'lp_price'=>$lp===null?null:(int)$lp,'dspoinc_price'=>$dsp===null?null:(int)$dsp,'stock'=>(int)$stock];
    return ['draft'=>$d,'review'=>$review,'review_hash'=>hash('sha256',json_encode($review,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE))];
}

/** Creates one catalog-backed active offer and immutable audit/receipt atomically. */
function narrrfs_lab_publish(SQLite3 $db, array $input, string $actor): array {
    $keys=array_keys($input); sort($keys); $expected=['draft_id','expected_version','idempotency_key','review_hash']; sort($expected);
    if ($keys!==$expected || preg_match('/^\d{17,20}$/',$actor)!==1 || !is_int($input['draft_id']) || $input['draft_id']<1 || !is_int($input['expected_version']) || $input['expected_version']<1 || !is_string($input['review_hash']) || preg_match('/^[a-f0-9]{64}$/',$input['review_hash'])!==1 || !is_string($input['idempotency_key']) || preg_match('/^[A-Za-z0-9_-]{24,128}$/',$input['idempotency_key'])!==1) throw new NarrrfsLabPublishException(400,'invalid_request');
    $requestHash=hash('sha256',json_encode([$input['draft_id'],$input['expected_version'],$input['review_hash']],JSON_THROW_ON_ERROR));
    $keyHash=hash('sha256',$actor."\n".$input['idempotency_key']); $db->enableExceptions(true); $db->busyTimeout(5000);
    try { $db->exec('BEGIN IMMEDIATE'); } catch (Throwable $e) { if(in_array($db->lastErrorCode() & 255,[5,6],true)) throw new NarrrfsLabPublishException(503,'database_busy'); throw $e; }
    try {
        $q=$db->prepare('SELECT request_hash,offer_id,expires_at FROM tbl_owner_control_lab_store_publish_receipts WHERE actor_subject=:actor AND idempotency_key_hash=:key'); $q->bindValue(':actor',$actor,SQLITE3_TEXT); $q->bindValue(':key',$keyHash,SQLITE3_TEXT); $r=$q->execute(); $receipt=$r->fetchArray(SQLITE3_ASSOC); $r->finalize();
        if (is_array($receipt)) { $expiry=strtotime($receipt['expires_at'].' UTC'); if($expiry===false||$expiry<=time()) throw new NarrrfsLabPublishException(409,'idempotency_key_expired'); if(!hash_equals($receipt['request_hash'],$requestHash)) throw new NarrrfsLabPublishException(409,'idempotency_key_reused'); $db->exec('COMMIT'); return ['offer_id'=>(int)$receipt['offer_id'],'replayed'=>true]; }
        $snap=narrrfs_lab_publish_snapshot($db,$input['draft_id']); $review=$snap['review'];
        if ($review['version']!==$input['expected_version']) throw new NarrrfsLabPublishException(409,'version_conflict');
        if (!hash_equals($snap['review_hash'],$input['review_hash'])) throw new NarrrfsLabPublishException(409,'review_changed');
        $q=$db->prepare('SELECT 1 FROM tbl_owner_control_lab_store_publish_audit WHERE draft_id=:id'); $q->bindValue(':id',$input['draft_id'],SQLITE3_INTEGER); $r=$q->execute(); $published=$r->fetchArray(SQLITE3_NUM); $r->finalize(); if($published) throw new NarrrfsLabPublishException(409,'draft_already_published');
        $q=$db->prepare('SELECT 1 FROM tbl_mouse_lp_store_offers WHERE source_type=:type AND source_item_id=:id'); $q->bindValue(':type',$review['source']['type'],SQLITE3_TEXT); $q->bindValue(':id',$review['source']['id'],SQLITE3_INTEGER); $r=$q->execute(); $exists=$r->fetchArray(SQLITE3_NUM); $r->finalize(); if($exists) throw new NarrrfsLabPublishException(409,'offer_already_exists');
        $q=$db->prepare('INSERT INTO tbl_mouse_lp_store_offers (source_type,source_item_id,lp_price,dspoinc_price,stock,is_active,created_by,updated_by) VALUES (:type,:source,:lp,:dsp,:stock,1,:actor,:actor)');
        foreach([':type'=>$review['source']['type'],':source'=>$review['source']['id'],':lp'=>$review['lp_price'],':dsp'=>$review['dspoinc_price'],':stock'=>$review['stock'],':actor'=>$actor] as $k=>$v) $q->bindValue($k,$v,$v===null?SQLITE3_NULL:(is_int($v)?SQLITE3_INTEGER:SQLITE3_TEXT)); $q->execute()->finalize(); $offer=(int)$db->lastInsertRowID();
        $q=$db->prepare('INSERT INTO tbl_owner_control_lab_store_publish_audit (draft_id,draft_version,offer_id,actor_subject,source_type,source_item_id,request_hash) VALUES (:draft,:version,:offer,:actor,:type,:source,:hash)'); foreach([':draft'=>$input['draft_id'],':version'=>$input['expected_version'],':offer'=>$offer,':actor'=>$actor,':type'=>$review['source']['type'],':source'=>$review['source']['id'],':hash'=>$requestHash] as $k=>$v) $q->bindValue($k,$v,is_int($v)?SQLITE3_INTEGER:SQLITE3_TEXT); $q->execute()->finalize();
        $q=$db->prepare('INSERT INTO tbl_owner_control_lab_store_publish_receipts (actor_subject,idempotency_key_hash,request_hash,draft_id,offer_id,expires_at) VALUES (:actor,:key,:hash,:draft,:offer,:expires)'); foreach([':actor'=>$actor,':key'=>$keyHash,':hash'=>$requestHash,':draft'=>$input['draft_id'],':offer'=>$offer,':expires'=>gmdate('Y-m-d H:i:s',time()+900)] as $k=>$v) $q->bindValue($k,$v,is_int($v)?SQLITE3_INTEGER:SQLITE3_TEXT); $q->execute()->finalize();
        $db->exec('COMMIT'); return ['offer_id'=>$offer,'replayed'=>false];
    } catch (Throwable $e) { $db->exec('ROLLBACK'); throw $e; }
}
