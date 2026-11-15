<?php
declare(strict_types=1);

/** M2A domain service: changes only tbl_mouse_lp_store_offers.is_active. */
final class NarrrfsOwnerLabStoreOfferStateException extends RuntimeException
{
    public function __construct(private readonly int $status, private readonly string $publicCodeValue) { parent::__construct($publicCodeValue); }
    public function status(): int { return $this->status; }
    public function publicCode(): string { return $this->publicCodeValue; }
}

const NARRRFS_LAB_STORE_OFFER_STATE_RECEIPT_TTL_SECONDS = 900;
const NARRRFS_LAB_STORE_OFFER_STATE_BUSY_TIMEOUT_MS = 5000;

function narrrfs_owner_lab_store_offer_state(SQLite3 $db, array $input, string $actor): array
{
    $allowed = ['offer_id' => true, 'expected_version' => true, 'is_active' => true, 'idempotency_key' => true];
    foreach ($input as $key => $_) if (!isset($allowed[$key])) throw new NarrrfsOwnerLabStoreOfferStateException(400, 'invalid_request');
    $offerId = $input['offer_id'] ?? null; $expected = $input['expected_version'] ?? null; $desired = $input['is_active'] ?? null; $key = $input['idempotency_key'] ?? null;
    if (!is_int($offerId) || $offerId < 1 || !is_int($expected) || $expected < 1 || !is_bool($desired) || !is_string($key) || preg_match('/^[A-Za-z0-9_-]{24,128}$/', $key) !== 1) throw new NarrrfsOwnerLabStoreOfferStateException(400, 'invalid_request');
    if (preg_match('/^\d{17,20}$/', $actor) !== 1) throw new NarrrfsOwnerLabStoreOfferStateException(400, 'invalid_request');
    $requestHash = hash('sha256', json_encode(['offer_id'=>$offerId,'expected_version'=>$expected,'is_active'=>$desired], JSON_THROW_ON_ERROR));
    $keyHash = hash('sha256', $actor . "\n" . $key);
    $db->enableExceptions(true);
    $db->busyTimeout(NARRRFS_LAB_STORE_OFFER_STATE_BUSY_TIMEOUT_MS);
    try {
        if (!$db->exec('BEGIN IMMEDIATE')) throw new RuntimeException('Offer transaction unavailable.');
    } catch (Throwable) {
        // No transaction exists yet: return a retryable failure, never a false stale-write result.
        throw new NarrrfsOwnerLabStoreOfferStateException(503, 'database_busy');
    }
    $open = true;
    try {
        $receipt = $db->prepare('SELECT request_hash,offer_id,resulting_version,resulting_is_active,expires_at FROM tbl_owner_control_lab_store_offer_state_receipts WHERE actor_subject=:actor AND idempotency_key_hash=:key LIMIT 1');
        $receipt->bindValue(':actor',$actor,SQLITE3_TEXT); $receipt->bindValue(':key',$keyHash,SQLITE3_TEXT); $result=$receipt->execute(); $priorReceipt=$result instanceof SQLite3Result ? $result->fetchArray(SQLITE3_ASSOC) : false; if($result instanceof SQLite3Result)$result->finalize();
        if (is_array($priorReceipt)) {
            $expires = strtotime((string)$priorReceipt['expires_at'].' UTC');
            if ($expires === false || $expires <= time()) throw new NarrrfsOwnerLabStoreOfferStateException(409,'idempotency_key_expired');
            if (!hash_equals((string)$priorReceipt['request_hash'],$requestHash)) throw new NarrrfsOwnerLabStoreOfferStateException(409,'idempotency_key_reused');
            if (!$db->exec('COMMIT')) throw new RuntimeException('Offer replay commit failed.'); $open=false;
            return ['replayed'=>true,'offer_id'=>(int)$priorReceipt['offer_id'],'version'=>(int)$priorReceipt['resulting_version'],'is_active'=>(bool)$priorReceipt['resulting_is_active']];
        }
        $lookup=$db->prepare('SELECT is_active,owner_control_version FROM tbl_mouse_lp_store_offers WHERE offer_id=:id LIMIT 1'); $lookup->bindValue(':id',$offerId,SQLITE3_INTEGER); $result=$lookup->execute(); $prior=$result instanceof SQLite3Result?$result->fetchArray(SQLITE3_ASSOC):false; if($result instanceof SQLite3Result)$result->finalize();
        if (!is_array($prior)) throw new NarrrfsOwnerLabStoreOfferStateException(404,'offer_not_found');
        if ((int)$prior['owner_control_version'] !== $expected) throw new NarrrfsOwnerLabStoreOfferStateException(409,'version_conflict');
        if ((int)$prior['is_active'] === ($desired ? 1 : 0)) throw new NarrrfsOwnerLabStoreOfferStateException(409,'state_already_current');
        $update=$db->prepare('UPDATE tbl_mouse_lp_store_offers SET is_active=:active, owner_control_version=owner_control_version+1, updated_by=:actor, updated_at=CURRENT_TIMESTAMP WHERE offer_id=:id AND owner_control_version=:expected');
        $update->bindValue(':active',$desired?1:0,SQLITE3_INTEGER); $update->bindValue(':actor',$actor,SQLITE3_TEXT); $update->bindValue(':id',$offerId,SQLITE3_INTEGER); $update->bindValue(':expected',$expected,SQLITE3_INTEGER); $r=$update->execute(); if($r instanceof SQLite3Result)$r->finalize(); if($db->changes()!==1) throw new NarrrfsOwnerLabStoreOfferStateException(409,'version_conflict');
        $version=$expected+1; $priorActive=(int)$prior['is_active']; $active=$desired?1:0;
        $audit=$db->prepare('INSERT INTO tbl_owner_control_lab_store_offer_state_audit (offer_id,actor_subject,prior_version,resulting_version,prior_is_active,resulting_is_active,request_hash) VALUES (:id,:actor,:prior,:version,:prior_active,:active,:hash)');
        foreach([':id'=>[$offerId,SQLITE3_INTEGER],':actor'=>[$actor,SQLITE3_TEXT],':prior'=>[$expected,SQLITE3_INTEGER],':version'=>[$version,SQLITE3_INTEGER],':prior_active'=>[$priorActive,SQLITE3_INTEGER],':active'=>[$active,SQLITE3_INTEGER],':hash'=>[$requestHash,SQLITE3_TEXT]] as $n=>$v)$audit->bindValue($n,$v[0],$v[1]); $r=$audit->execute();if($r===false)throw new RuntimeException('Offer audit insert failed.');if($r instanceof SQLite3Result)$r->finalize();
        $expires=gmdate('Y-m-d H:i:s',time()+NARRRFS_LAB_STORE_OFFER_STATE_RECEIPT_TTL_SECONDS); $save=$db->prepare('INSERT INTO tbl_owner_control_lab_store_offer_state_receipts (actor_subject,idempotency_key_hash,request_hash,offer_id,resulting_version,resulting_is_active,expires_at) VALUES (:actor,:key,:hash,:id,:version,:active,:expires)');
        foreach([':actor'=>[$actor,SQLITE3_TEXT],':key'=>[$keyHash,SQLITE3_TEXT],':hash'=>[$requestHash,SQLITE3_TEXT],':id'=>[$offerId,SQLITE3_INTEGER],':version'=>[$version,SQLITE3_INTEGER],':active'=>[$active,SQLITE3_INTEGER],':expires'=>[$expires,SQLITE3_TEXT]] as $n=>$v)$save->bindValue($n,$v[0],$v[1]);$r=$save->execute();if($r===false)throw new RuntimeException('Offer receipt insert failed.');if($r instanceof SQLite3Result)$r->finalize();
        if(!$db->exec('COMMIT'))throw new RuntimeException('Offer commit failed.');$open=false;return ['replayed'=>false,'offer_id'=>$offerId,'version'=>$version,'is_active'=>$desired];
    } catch(Throwable $e) { if($open)$db->exec('ROLLBACK'); throw $e; }
}
