<?php
declare(strict_types=1);

/** Changes only an existing offer's three commercial fields and version. */
final class NarrrfsOwnerLabStoreOfferCommercialException extends RuntimeException
{
    public function __construct(private readonly int $status, private readonly string $codeValue) { parent::__construct($codeValue); }
    public function status(): int { return $this->status; }
    public function publicCode(): string { return $this->codeValue; }
}

/** Serializes owner edits with purchases and keeps the audit and receipt atomic. */
function narrrfs_owner_lab_store_offer_commercial(SQLite3 $db, array $input, string $actor): array
{
    $fields = ['offer_id', 'expected_version', 'lp_price', 'dspoinc_price', 'stock', 'idempotency_key'];
    $keys = array_keys($input); sort($keys); $expectedKeys = $fields; sort($expectedKeys);
    if ($keys !== $expectedKeys || preg_match('/^\d{17,20}$/', $actor) !== 1) throw new NarrrfsOwnerLabStoreOfferCommercialException(400, 'invalid_request');
    $id=$input['offer_id']; $version=$input['expected_version']; $lp=$input['lp_price']; $dsp=$input['dspoinc_price']; $stock=$input['stock']; $key=$input['idempotency_key'];
    if (!is_int($id) || $id<1 || !is_int($version) || $version<1 || !is_int($stock) || $stock<0 ||
        !($lp===null || (is_int($lp) && $lp>0)) || !($dsp===null || (is_int($dsp) && $dsp>0)) ||
        ($lp===null && $dsp===null) || !is_string($key) || preg_match('/^[A-Za-z0-9_-]{24,128}$/', $key)!==1) {
        throw new NarrrfsOwnerLabStoreOfferCommercialException(400, 'invalid_request');
    }
    $hash=hash('sha256', json_encode(['offer_id'=>$id,'expected_version'=>$version,'lp_price'=>$lp,'dspoinc_price'=>$dsp,'stock'=>$stock], JSON_THROW_ON_ERROR));
    $keyHash=hash('sha256', $actor."\n".$key);
    $db->enableExceptions(true); $db->busyTimeout(5000);
    try { $db->exec('BEGIN IMMEDIATE'); }
    catch (Throwable $e) {
        if (in_array($db->lastErrorCode() & 0xff, [5,6], true)) throw new NarrrfsOwnerLabStoreOfferCommercialException(503, 'database_busy');
        throw $e;
    }
    try {
        $q=$db->prepare('SELECT request_hash,offer_id,resulting_version,expires_at FROM tbl_owner_control_lab_store_offer_commercial_receipts WHERE actor_subject=:actor AND idempotency_key_hash=:key');
        $q->bindValue(':actor',$actor,SQLITE3_TEXT); $q->bindValue(':key',$keyHash,SQLITE3_TEXT); $r=$q->execute(); $receipt=$r->fetchArray(SQLITE3_ASSOC); $r->finalize();
        if (is_array($receipt)) {
            $expiry=strtotime((string)$receipt['expires_at'].' UTC');
            if ($expiry===false || $expiry<=time()) throw new NarrrfsOwnerLabStoreOfferCommercialException(409,'idempotency_key_expired');
            if (!hash_equals((string)$receipt['request_hash'],$hash)) throw new NarrrfsOwnerLabStoreOfferCommercialException(409,'idempotency_key_reused');
            $db->exec('COMMIT'); return ['replayed'=>true,'offer_id'=>(int)$receipt['offer_id'],'version'=>(int)$receipt['resulting_version']];
        }
        $q=$db->prepare('SELECT lp_price,dspoinc_price,stock,owner_control_version FROM tbl_mouse_lp_store_offers WHERE offer_id=:id');
        $q->bindValue(':id',$id,SQLITE3_INTEGER); $r=$q->execute(); $prior=$r->fetchArray(SQLITE3_ASSOC); $r->finalize();
        if (!is_array($prior)) throw new NarrrfsOwnerLabStoreOfferCommercialException(404,'offer_not_found');
        if ((int)$prior['owner_control_version']!==$version) throw new NarrrfsOwnerLabStoreOfferCommercialException(409,'version_conflict');
        $q=$db->prepare('UPDATE tbl_mouse_lp_store_offers SET lp_price=:lp,dspoinc_price=:dsp,stock=:stock,owner_control_version=owner_control_version+1 WHERE offer_id=:id AND owner_control_version=:version');
        foreach ([':lp'=>$lp,':dsp'=>$dsp,':stock'=>$stock,':id'=>$id,':version'=>$version] as $n=>$v) $q->bindValue($n,$v,$v===null?SQLITE3_NULL:SQLITE3_INTEGER);
        $r=$q->execute(); $r->finalize(); if ($db->changes()!==1) throw new NarrrfsOwnerLabStoreOfferCommercialException(409,'version_conflict');
        $q=$db->prepare('INSERT INTO tbl_owner_control_lab_store_offer_commercial_audit (offer_id,actor_subject,prior_version,resulting_version,prior_lp_price,resulting_lp_price,prior_dspoinc_price,resulting_dspoinc_price,prior_stock,resulting_stock,request_hash) VALUES (:id,:actor,:prior,:version,:plp,:lp,:pdsp,:dsp,:ps,:stock,:hash)');
        $values=[':id'=>$id,':actor'=>$actor,':prior'=>$version,':version'=>$version+1,':plp'=>$prior['lp_price'],':lp'=>$lp,':pdsp'=>$prior['dspoinc_price'],':dsp'=>$dsp,':ps'=>$prior['stock'],':stock'=>$stock,':hash'=>$hash];
        foreach($values as $n=>$v) $q->bindValue($n,$v,$v===null?SQLITE3_NULL:(is_string($v)?SQLITE3_TEXT:SQLITE3_INTEGER)); $r=$q->execute(); $r->finalize();
        $q=$db->prepare('INSERT INTO tbl_owner_control_lab_store_offer_commercial_receipts (actor_subject,idempotency_key_hash,request_hash,offer_id,resulting_version,expires_at) VALUES (:actor,:key,:hash,:id,:version,:expires)');
        $values=[':actor'=>$actor,':key'=>$keyHash,':hash'=>$hash,':id'=>$id,':version'=>$version+1,':expires'=>gmdate('Y-m-d H:i:s',time()+900)];
        foreach($values as $n=>$v) $q->bindValue($n,$v,is_int($v)?SQLITE3_INTEGER:SQLITE3_TEXT); $r=$q->execute(); $r->finalize();
        $db->exec('COMMIT'); return ['replayed'=>false,'offer_id'=>$id,'version'=>$version+1];
    } catch (Throwable $e) { $db->exec('ROLLBACK'); throw $e; }
}
