<?php
declare(strict_types=1);

/** Draft-only Lab Store M1 domain service. It must never query or write live offers. */
final class NarrrfsOwnerLabStoreDraftException extends RuntimeException
{
    public function __construct(private readonly int $status, private readonly string $publicCode)
    {
        parent::__construct($publicCode);
    }
    public function status(): int { return $this->status; }
    public function publicCode(): string { return $this->publicCode; }
}

const NARRRFS_LAB_STORE_DRAFT_RECEIPT_TTL_SECONDS = 900;

function narrrfs_lab_store_draft_text(array $input, string $key, int $maximum, bool $nullable = false): ?string
{
    if (!array_key_exists($key, $input) || $input[$key] === null) {
        if ($nullable) return null;
        throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_draft');
    }
    if (!is_string($input[$key])) throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_draft');
    $value = trim($input[$key]);
    if ($value === '') {
        if ($nullable) return null;
        throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_draft');
    }
    if (strlen($value) > $maximum) throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_draft');
    return $value;
}

/** Accept a stored reference only in the same public namespaces already used by R0. */
function narrrfs_lab_store_draft_media_reference(?string $value): ?string
{
    if ($value === null) return null;
    if (str_contains($value, '\\') || str_contains($value, '..') || str_contains($value, '%') || str_contains($value, '?') || str_contains($value, '#') || str_contains($value, '//')) {
        throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_media_reference');
    }
    if (preg_match('#^img/#i', $value)) $value = '/public/' . $value;
    if (!preg_match('#^/public/(?:img/|store-items/|three\.js/public/genetic/previews/)#', $value)) {
        throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_media_reference');
    }
    if (!preg_match('/\.(?:png|jpe?g|webp|gif)$/i', $value)) {
        throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_media_reference');
    }
    return $value;
}

function narrrfs_lab_store_draft_idempotency(array $input): string
{
    $key = $input['idempotency_key'] ?? null;
    if (!is_string($key) || !preg_match('/^[A-Za-z0-9_-]{24,128}$/', $key)) {
        throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_idempotency_key');
    }
    return $key;
}

function narrrfs_lab_store_draft_expected_version(array $input): int
{
    $value = $input['expected_version'] ?? null;
    if (!is_int($value) || $value < 1) throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_expected_version');
    return $value;
}

function narrrfs_lab_store_draft_fetch_one(SQLite3Stmt $statement): ?array
{
    $result = $statement->execute();
    if (!$result instanceof SQLite3Result) throw new RuntimeException('Draft query failed.');
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $result->finalize();
    return is_array($row) ? $row : null;
}

function narrrfs_lab_store_draft_public(array $row): array
{
    return [
        'draft_id' => (int)$row['draft_id'], 'title' => (string)$row['title'],
        'category' => (string)$row['category'], 'description' => (string)$row['description'],
        'media_reference' => $row['media_reference'] === null ? null : (string)$row['media_reference'],
        'proposed_source_type' => $row['proposed_source_type'] ?? null,
        'proposed_source_item_id' => ($row['proposed_source_item_id'] ?? null) === null ? null : (int)$row['proposed_source_item_id'],
        'proposed_lp_price' => ($row['proposed_lp_price'] ?? null) === null ? null : (int)$row['proposed_lp_price'],
        'proposed_dspoinc_price' => ($row['proposed_dspoinc_price'] ?? null) === null ? null : (int)$row['proposed_dspoinc_price'],
        'proposed_stock' => ($row['proposed_stock'] ?? null) === null ? null : (int)$row['proposed_stock'],
        'state' => 'draft', 'version' => (int)$row['version'],
        'created_at' => (string)$row['created_at'], 'updated_at' => (string)$row['updated_at'],
    ];
}

function narrrfs_lab_store_draft_payload(array $input): array
{
    $title = narrrfs_lab_store_draft_text($input, 'title', 200);
    $category = narrrfs_lab_store_draft_text($input, 'category', 80);
    $description = narrrfs_lab_store_draft_text($input, 'description', 4000);
    $media = narrrfs_lab_store_draft_media_reference(narrrfs_lab_store_draft_text($input, 'media_reference', 500, true));
    $type = $input['proposed_source_type'] ?? null;
    if ($type !== null && !in_array($type, ['normal_store', 'genetic_item'], true)) throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_proposed_source_type');
    $item = $input['proposed_source_item_id'] ?? null; if ($item !== null && (!is_int($item) || $item < 1)) throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_proposed_source_item');
    $lp = $input['proposed_lp_price'] ?? null; $dsp = $input['proposed_dspoinc_price'] ?? null; $stock = $input['proposed_stock'] ?? null;
    foreach ([$lp, $dsp, $stock] as $value) if ($value !== null && (!is_int($value) || $value < 0)) throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_proposed_value');
    return compact('title', 'category', 'description', 'media', 'type', 'item', 'lp', 'dsp', 'stock');
}

function narrrfs_lab_store_draft_write(SQLite3 $db, array $input, string $actor, bool $update): array
{
    if (!preg_match('/^\d{17,20}$/', $actor)) throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_draft');
    $payload = narrrfs_lab_store_draft_payload($input);
    $key = narrrfs_lab_store_draft_idempotency($input);
    $draftId = $update ? ($input['draft_id'] ?? null) : null;
    if ($update && (!is_int($draftId) || $draftId < 1)) throw new NarrrfsOwnerLabStoreDraftException(400, 'invalid_draft_id');
    $expected = $update ? narrrfs_lab_store_draft_expected_version($input) : null;
    $semantic = ['action' => $update ? 'update' : 'create', 'draft_id' => $draftId, 'expected_version' => $expected] + $payload;
    ksort($semantic);
    $requestHash = hash('sha256', json_encode($semantic, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    $keyHash = hash('sha256', $actor . "\n" . $key);
    if (!$db->exec('PRAGMA foreign_keys = ON') || !$db->exec('BEGIN IMMEDIATE')) throw new RuntimeException('Draft transaction unavailable.');
    $open = true;
    try {
        $receipt = $db->prepare('SELECT request_hash,draft_id,resulting_version,action,expires_at FROM tbl_owner_control_lab_store_draft_receipts WHERE actor_subject=:actor AND idempotency_key_hash=:key LIMIT 1');
        $receipt->bindValue(':actor', $actor, SQLITE3_TEXT); $receipt->bindValue(':key', $keyHash, SQLITE3_TEXT);
        $oldReceipt = narrrfs_lab_store_draft_fetch_one($receipt);
        if ($oldReceipt !== null) {
            $expiry = strtotime((string)$oldReceipt['expires_at'] . ' UTC');
            if ($expiry === false || $expiry <= time()) throw new NarrrfsOwnerLabStoreDraftException(409, 'idempotency_key_expired');
            if (!hash_equals((string)$oldReceipt['request_hash'], $requestHash)) throw new NarrrfsOwnerLabStoreDraftException(409, 'idempotency_key_reused');
            if (!$db->exec('COMMIT')) throw new RuntimeException('Draft retry commit failed.'); $open = false;
            return ['replayed' => true, 'draft_id' => (int)$oldReceipt['draft_id'], 'version' => (int)$oldReceipt['resulting_version'], 'action' => (string)$oldReceipt['action']];
        }
        if ($update) {
            $statement = $db->prepare('UPDATE tbl_owner_control_lab_store_drafts SET title=:title,category=:category,description=:description,media_reference=:media,proposed_source_type=:type,proposed_source_item_id=:item,proposed_lp_price=:lp,proposed_dspoinc_price=:dsp,proposed_stock=:stock,updated_by=:actor,updated_at=CURRENT_TIMESTAMP,version=version+1 WHERE draft_id=:id AND state=\'draft\' AND version=:expected');
            $statement->bindValue(':id', $draftId, SQLITE3_INTEGER); $statement->bindValue(':expected', $expected, SQLITE3_INTEGER);
            $prior = $expected;
        } else {
            $statement = $db->prepare('INSERT INTO tbl_owner_control_lab_store_drafts (title,category,description,media_reference,proposed_source_type,proposed_source_item_id,proposed_lp_price,proposed_dspoinc_price,proposed_stock,state,version,created_by,updated_by) VALUES (:title,:category,:description,:media,:type,:item,:lp,:dsp,:stock,\'draft\',1,:actor,:actor)');
            $prior = null;
        }
        foreach (['title' => $payload['title'], 'category' => $payload['category'], 'description' => $payload['description']] as $name => $value) $statement->bindValue(':'.$name, $value, SQLITE3_TEXT);
        $statement->bindValue(':media', $payload['media'], $payload['media'] === null ? SQLITE3_NULL : SQLITE3_TEXT); $statement->bindValue(':actor', $actor, SQLITE3_TEXT);
        $statement->bindValue(':type', $payload['type'], $payload['type'] === null ? SQLITE3_NULL : SQLITE3_TEXT); $statement->bindValue(':item', $payload['item'], $payload['item'] === null ? SQLITE3_NULL : SQLITE3_INTEGER); $statement->bindValue(':lp', $payload['lp'], $payload['lp'] === null ? SQLITE3_NULL : SQLITE3_INTEGER); $statement->bindValue(':dsp', $payload['dsp'], $payload['dsp'] === null ? SQLITE3_NULL : SQLITE3_INTEGER); $statement->bindValue(':stock', $payload['stock'], $payload['stock'] === null ? SQLITE3_NULL : SQLITE3_INTEGER);
        $result = $statement->execute(); if (!$result instanceof SQLite3Result) throw new RuntimeException('Draft write failed.'); $result->finalize();
        if ($update && $db->changes() !== 1) throw new NarrrfsOwnerLabStoreDraftException(409, 'version_conflict');
        $id = $update ? $draftId : (int)$db->lastInsertRowID();
        $lookup = $db->prepare('SELECT * FROM tbl_owner_control_lab_store_drafts WHERE draft_id=:id LIMIT 1'); $lookup->bindValue(':id',$id,SQLITE3_INTEGER);
        $row = narrrfs_lab_store_draft_fetch_one($lookup); if ($row === null) throw new RuntimeException('Draft result unavailable.');
        $version = (int)$row['version']; $action = $update ? 'updated' : 'created';
        $audit = $db->prepare('INSERT INTO tbl_owner_control_lab_store_draft_audit (draft_id,actor_subject,action,prior_version,resulting_version,change_hash) VALUES (:id,:actor,:action,:prior,:result,:hash)');
        $audit->bindValue(':id',$id,SQLITE3_INTEGER); $audit->bindValue(':actor',$actor,SQLITE3_TEXT); $audit->bindValue(':action',$action,SQLITE3_TEXT); $audit->bindValue(':prior',$prior,$prior===null?SQLITE3_NULL:SQLITE3_INTEGER); $audit->bindValue(':result',$version,SQLITE3_INTEGER); $audit->bindValue(':hash',$requestHash,SQLITE3_TEXT); $auditResult=$audit->execute(); if(!$auditResult instanceof SQLite3Result) throw new RuntimeException('Draft audit failed.'); $auditResult->finalize();
        $expires = gmdate('Y-m-d H:i:s', time()+NARRRFS_LAB_STORE_DRAFT_RECEIPT_TTL_SECONDS);
        $save = $db->prepare('INSERT INTO tbl_owner_control_lab_store_draft_receipts (actor_subject,idempotency_key_hash,request_hash,draft_id,resulting_version,action,expires_at) VALUES (:actor,:key,:hash,:id,:version,:action,:expires)');
        foreach([':actor'=>[$actor,SQLITE3_TEXT],':key'=>[$keyHash,SQLITE3_TEXT],':hash'=>[$requestHash,SQLITE3_TEXT],':id'=>[$id,SQLITE3_INTEGER],':version'=>[$version,SQLITE3_INTEGER],':action'=>[$action,SQLITE3_TEXT],':expires'=>[$expires,SQLITE3_TEXT]] as $name=>$spec) $save->bindValue($name,$spec[0],$spec[1]); $saveResult=$save->execute(); if(!$saveResult instanceof SQLite3Result)throw new RuntimeException('Draft receipt failed.');$saveResult->finalize();
        if(!$db->exec('COMMIT'))throw new RuntimeException('Draft commit failed.');$open=false; return ['replayed'=>false,'draft'=>narrrfs_lab_store_draft_public($row),'action'=>$action];
    } catch (Throwable $error) { if($open) $db->exec('ROLLBACK'); throw $error; }
}
