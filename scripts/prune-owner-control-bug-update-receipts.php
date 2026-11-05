<?php
declare(strict_types=1);

/**
 * Explicit maintenance command for expired Owner Control retry receipts.
 * It is never called by startup or an HTTP route. Operators must separately
 * approve each invocation against a verified runtime database recovery point.
 */
if (PHP_SAPI !== 'cli' || ($argv[1] ?? null) !== '--prune-expired-receipts') {
    fwrite(STDERR, "Usage: php prune-owner-control-bug-update-receipts.php --prune-expired-receipts <db-path>\n");
    exit(64);
}
$path = $argv[2] ?? '';
if (!is_string($path) || $path === '' || !is_file($path) || !is_writable($path)) {
    fwrite(STDERR, "Receipt prune: database unavailable\n");
    exit(1);
}
$db = new SQLite3($path, SQLITE3_OPEN_READWRITE);
try {
    if (!$db->exec('BEGIN IMMEDIATE')) throw new RuntimeException('begin failed');
    $statement = $db->prepare('DELETE FROM tbl_owner_control_bug_update_receipts WHERE expires_at < CURRENT_TIMESTAMP');
    if (!$statement instanceof SQLite3Stmt || !$statement->execute()) throw new RuntimeException('delete failed');
    $count = $db->changes();
    if (!$db->exec('COMMIT')) throw new RuntimeException('commit failed');
    echo 'deleted_count=' . $count . ' utc=' . gmdate('c') . PHP_EOL;
} catch (Throwable $error) {
    $db->exec('ROLLBACK');
    fwrite(STDERR, "Receipt prune: failed\n");
    exit(1);
} finally { $db->close(); }
