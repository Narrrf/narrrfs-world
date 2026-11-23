<?php
declare(strict_types=1);

// Owner adjustments affect normal Store inventory or DSPOINC ledger entries only.
// Permanent ownership, Genetic inventory and staking rows are never changed.
final class OwnerPlayerAdjustmentException extends RuntimeException {
    public function __construct(public int $status, public string $publicCode) { parent::__construct($publicCode); }
}

/** Atomically adjust a single player's ledger or normal Store inventory. */
function owner_player_adjust(SQLite3 $db, array $input, string $actor, string $kind): array {
    $expected = $kind === 'points' ? ['action','amount','idempotency_key','reason','user_id'] : ['action','amount','idempotency_key','item_id','reason','user_id'];
    $keys = array_keys($input); sort($keys); sort($expected);
    if ($keys !== $expected || !in_array($kind, ['points','inventory'], true)) throw new OwnerPlayerAdjustmentException(400, 'invalid_request');
    $action = $input['action']; $amount = $input['amount']; $user = $input['user_id']; $reason = $input['reason']; $key = $input['idempotency_key'];
    if (!is_string($actor) || !preg_match('/^\d{17,20}$/D', $actor) || !is_string($user) || !preg_match('/^\d{17,20}$/D', $user)
        || !in_array($action, ['add','remove'], true) || !is_int($amount) || $amount < 1 || $amount > 1000000000
        || !is_string($reason) || strlen(trim($reason)) < 4 || strlen($reason) > 256 || preg_match('/[\x00-\x1F\x7F]/', $reason)
        || !is_string($key) || !preg_match('/^[A-Za-z0-9_-]{16,128}$/D', $key)
        || ($kind === 'inventory' && (!is_int($input['item_id']) || $input['item_id'] < 1))) throw new OwnerPlayerAdjustmentException(400, 'invalid_request');
    $item = $kind === 'inventory' ? $input['item_id'] : null;
    $signed = $action === 'add' ? $amount : -$amount;
    $hash = hash('sha256', json_encode([$kind,$action,$user,$amount,$item,trim($reason)], JSON_THROW_ON_ERROR));
    $keyHash = hash('sha256', $key);
    $db->enableExceptions(true); $db->busyTimeout(5000); $open = false;
    try {
        try { $db->exec('BEGIN IMMEDIATE'); $open = true; }
        catch (Throwable $e) { if (in_array($db->lastErrorCode(), [5,6], true)) throw new OwnerPlayerAdjustmentException(503, 'database_busy'); throw $e; }
        $q=$db->prepare('SELECT r.request_hash,a.audit_id,a.before_value,a.after_value FROM tbl_owner_player_adjustment_receipts r JOIN tbl_owner_player_adjustment_audit a ON a.audit_id=r.audit_id WHERE r.actor_subject=:actor AND r.idempotency_key_hash=:key');
        $q->bindValue(':actor',$actor,SQLITE3_TEXT); $q->bindValue(':key',$keyHash,SQLITE3_TEXT); $existing=$q->execute()->fetchArray(SQLITE3_ASSOC);
        if (is_array($existing)) {
            if (!hash_equals((string)$existing['request_hash'],$hash)) throw new OwnerPlayerAdjustmentException(409,'idempotency_key_reused');
            $db->exec('COMMIT'); $open=false;
            return ['replayed'=>true,'audit_id'=>(int)$existing['audit_id'],'before'=>(int)$existing['before_value'],'after'=>(int)$existing['after_value']];
        }
        $q=$db->prepare('SELECT 1 FROM tbl_users WHERE discord_id=:id LIMIT 1'); $q->bindValue(':id',$user,SQLITE3_TEXT);
        if (!$q->execute()->fetchArray(SQLITE3_NUM)) throw new OwnerPlayerAdjustmentException(404,'user_not_found');
        if ($kind === 'points') {
            $q=$db->prepare("SELECT COALESCE(SUM(score),0) AS total FROM tbl_user_scores WHERE user_id=:id"); $q->bindValue(':id',$user,SQLITE3_TEXT); $total=(int)$q->execute()->fetchArray(SQLITE3_ASSOC)['total'];
            $q=$db->prepare("SELECT COALESCE(SUM(amount),0) AS frozen FROM tbl_dspoinc_stakes WHERE user_id=:id AND status='active'"); $q->bindValue(':id',$user,SQLITE3_TEXT); $frozen=(int)$q->execute()->fetchArray(SQLITE3_ASSOC)['frozen'];
            if ($signed < 0 && $amount > max(0,$total-$frozen)) throw new OwnerPlayerAdjustmentException(409,'insufficient_available_balance');
            if ($signed > 0 && $total > PHP_INT_MAX-$amount) throw new OwnerPlayerAdjustmentException(409,'amount_overflow');
            $before=$total; $after=$total+$signed;
            $q=$db->prepare("INSERT INTO tbl_user_scores(user_id,score,game,source,timestamp) VALUES(:id,:amount,'owner_control','owner_adjustment',CURRENT_TIMESTAMP)");
            $q->bindValue(':id',$user,SQLITE3_TEXT); $q->bindValue(':amount',$signed,SQLITE3_INTEGER); $q->execute();
            $q=$db->prepare('INSERT INTO tbl_score_adjustments(user_id,admin_id,amount,action,reason,timestamp) VALUES(:user,:actor,:amount,:action,:reason,CURRENT_TIMESTAMP)');
            $q->bindValue(':user',$user,SQLITE3_TEXT); $q->bindValue(':actor',$actor,SQLITE3_TEXT); $q->bindValue(':amount',$signed,SQLITE3_INTEGER); $q->bindValue(':action',$action,SQLITE3_TEXT); $q->bindValue(':reason',trim($reason),SQLITE3_TEXT); $q->execute();
        } else {
            // Initially limit inventory mutations to the three verified Lab
            // elixirs; other Store items can have unique or manual fulfilment.
            if (!in_array($item, [33,34,35], true)) throw new OwnerPlayerAdjustmentException(403,'item_not_supported');
            $q=$db->prepare('SELECT item_name FROM tbl_store_items WHERE item_id=:item AND COALESCE(is_active,0)=1 LIMIT 1'); $q->bindValue(':item',$item,SQLITE3_INTEGER);
            $catalog=$q->execute()->fetchArray(SQLITE3_ASSOC); $names=[33=>'Green Elixir',34=>'Blue Elixir',35=>'Red Elixir'];
            if (!is_array($catalog) || ($catalog['item_name'] ?? null)!==$names[$item]) throw new OwnerPlayerAdjustmentException(404,'item_not_found');
            $q=$db->prepare('SELECT quantity FROM tbl_user_inventory WHERE user_id=:user AND item_id=:item LIMIT 1'); $q->bindValue(':user',$user,SQLITE3_TEXT); $q->bindValue(':item',$item,SQLITE3_INTEGER); $row=$q->execute()->fetchArray(SQLITE3_ASSOC);
            if (is_array($row) && !is_int($row['quantity'])) throw new OwnerPlayerAdjustmentException(409,'invalid_inventory_quantity');
            $before=is_array($row)?$row['quantity']:0;
            if ($before < 0 || ($signed < 0 && $amount > $before) || ($signed > 0 && $before > PHP_INT_MAX-$amount)) throw new OwnerPlayerAdjustmentException(409,'invalid_inventory_quantity');
            $after=$before+$signed;
            if (is_array($row)) { $q=$db->prepare('UPDATE tbl_user_inventory SET quantity=:quantity WHERE user_id=:user AND item_id=:item'); }
            else { $q=$db->prepare('INSERT INTO tbl_user_inventory(user_id,item_id,quantity,acquired_at) VALUES(:user,:item,:quantity,CURRENT_TIMESTAMP)'); }
            $q->bindValue(':quantity',$after,SQLITE3_INTEGER); $q->bindValue(':user',$user,SQLITE3_TEXT); $q->bindValue(':item',$item,SQLITE3_INTEGER); $q->execute();
        }
        $q=$db->prepare('INSERT INTO tbl_owner_player_adjustment_audit(actor_subject,target_user_id,adjustment_kind,item_id,signed_amount,before_value,after_value,reason) VALUES(:actor,:user,:kind,:item,:amount,:before,:after,:reason)');
        $q->bindValue(':actor',$actor,SQLITE3_TEXT); $q->bindValue(':user',$user,SQLITE3_TEXT); $q->bindValue(':kind',$kind,SQLITE3_TEXT); $q->bindValue(':item',$item,$item===null?SQLITE3_NULL:SQLITE3_INTEGER); $q->bindValue(':amount',$signed,SQLITE3_INTEGER); $q->bindValue(':before',$before,SQLITE3_INTEGER); $q->bindValue(':after',$after,SQLITE3_INTEGER); $q->bindValue(':reason',trim($reason),SQLITE3_TEXT); $q->execute(); $audit=(int)$db->lastInsertRowID();
        $q=$db->prepare('INSERT INTO tbl_owner_player_adjustment_receipts(actor_subject,idempotency_key_hash,request_hash,audit_id) VALUES(:actor,:key,:hash,:audit)'); $q->bindValue(':actor',$actor,SQLITE3_TEXT); $q->bindValue(':key',$keyHash,SQLITE3_TEXT); $q->bindValue(':hash',$hash,SQLITE3_TEXT); $q->bindValue(':audit',$audit,SQLITE3_INTEGER); $q->execute();
        $db->exec('COMMIT'); $open=false;
        return ['replayed'=>false,'audit_id'=>$audit,'before'=>$before,'after'=>$after];
    } catch (Throwable $e) { if ($open) { try { $db->exec('ROLLBACK'); } catch (Throwable) { error_log('[OWNER ADJUSTMENT] rollback failed'); } } throw $e; }
}
