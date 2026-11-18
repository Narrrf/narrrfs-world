<?php
/** Local Christmas draft planning; neither approval nor payout authority. */
declare(strict_types=1);
require_once __DIR__ . '/christmas-advent-2026-draft-preflight-v2.php';

/** Save one validated draft revision, rejecting stale concurrent updates. */
function christmas26_draft_save_v1(SQLite3 $db, array $draft, int $expectedRevision, string $actor): array
{
    if ($expectedRevision < 0 || !preg_match('/^\d{17,20}$/D', $actor)) {
        throw new InvalidArgumentException('invalid_request');
    }
    $review = christmas26_draft_preflight_v2($draft);
    if (!$review['valid']) throw new InvalidArgumentException('invalid_draft');
    $json = json_encode($draft, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if (strlen($json) > 65536) throw new InvalidArgumentException('draft_too_large');
    $started = false;
    try {
        $db->enableExceptions(true);
        $db->exec('BEGIN IMMEDIATE');
        $started = true;
        $revision = (int)$db->querySingle('SELECT COALESCE(MAX(revision),0) FROM tbl_christmas_advent_2026_draft_revisions');
        if ($revision !== $expectedRevision) throw new RuntimeException('draft_revision_conflict');
        foreach ($review['review']['candidate_store_item_ids'] as $id) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM tbl_store_items WHERE item_id=:id AND is_active=1');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            if (!$result || (int)$result->fetchArray(SQLITE3_NUM)[0] !== 1) throw new InvalidArgumentException('invalid_store_item');
        }
        $fingerprint = hash('sha256', $json);
        $stmt = $db->prepare('INSERT INTO tbl_christmas_advent_2026_draft_revisions (revision,event_key,draft_json,fingerprint,actor_user_id) VALUES (:revision,:event,:json,:fingerprint,:actor)');
        $stmt->bindValue(':revision', $revision + 1, SQLITE3_INTEGER);
        $stmt->bindValue(':event', CHRISTMAS26_EVENT_KEY, SQLITE3_TEXT);
        $stmt->bindValue(':json', $json, SQLITE3_TEXT);
        $stmt->bindValue(':fingerprint', $fingerprint, SQLITE3_TEXT);
        $stmt->bindValue(':actor', $actor, SQLITE3_TEXT);
        $stmt->execute();
        $db->exec('COMMIT');
        $started = false;
        return ['revision'=>$revision+1,'fingerprint'=>$fingerprint,'review'=>$review['review'],'state'=>'DRAFT','publishable'=>false];
    } catch (Throwable $error) {
        if ($started) $db->exec('ROLLBACK');
        throw $error;
    }
}

/** Load the latest validated draft, rejecting altered bytes. */
function christmas26_draft_load_v1(SQLite3 $db): ?array
{
    $result = $db->query('SELECT revision,draft_json,fingerprint FROM tbl_christmas_advent_2026_draft_revisions ORDER BY revision DESC LIMIT 1');
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;
    if ($row === false) return null;
    if (!hash_equals((string)$row['fingerprint'], hash('sha256', (string)$row['draft_json']))) throw new RuntimeException('draft_integrity_failed');
    $draft = json_decode((string)$row['draft_json'], true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($draft) || !christmas26_draft_preflight_v2($draft)['valid']) throw new RuntimeException('draft_integrity_failed');
    return ['revision'=>(int)$row['revision'],'draft'=>$draft,'fingerprint'=>$row['fingerprint'],'state'=>'DRAFT','publishable'=>false];
}
