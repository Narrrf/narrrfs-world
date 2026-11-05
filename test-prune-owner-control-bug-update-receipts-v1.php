<?php
declare(strict_types=1);
$script = __DIR__ . '/scripts/prune-owner-control-bug-update-receipts.php';
$dbPath = sys_get_temp_dir() . '/narrrfs-prune-' . bin2hex(random_bytes(6)) . '.sqlite';
$db = new SQLite3($dbPath);
$db->exec('CREATE TABLE tbl_owner_control_bug_update_receipts (receipt_id INTEGER PRIMARY KEY, expires_at DATETIME NOT NULL); INSERT INTO tbl_owner_control_bug_update_receipts VALUES (1, datetime(CURRENT_TIMESTAMP, \'-1 second\')), (2, datetime(CURRENT_TIMESTAMP, \'+1 day\'));');
$db->close();
$run = static function () use ($script, $dbPath): string { return (string)shell_exec('"' . PHP_BINARY . '" "' . $script . '" --prune-expired-receipts "' . $dbPath . '"'); };
$first = $run(); $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
if (!str_contains($first, 'deleted_count=1') || (int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_bug_update_receipts') !== 1) throw new RuntimeException('expired-only prune failed');
$db->close(); $second = $run();
if (!str_contains($second, 'deleted_count=0')) throw new RuntimeException('idempotent empty prune failed');
$failPath = sys_get_temp_dir() . '/narrrfs-prune-fail-' . bin2hex(random_bytes(6)) . '.sqlite';
$failDb = new SQLite3($failPath); $failDb->exec('CREATE TABLE unrelated(value TEXT); INSERT INTO unrelated VALUES (\'preserve\');'); $failDb->close();
$before = hash_file('sha256', $failPath);
$status = 0; exec('"' . PHP_BINARY . '" "' . $script . '" --prune-expired-receipts "' . $failPath . '" 2>NUL', $unused, $status);
if ($status === 0 || $before !== hash_file('sha256', $failPath)) throw new RuntimeException('failed prune did not preserve database');
unlink($dbPath); unlink($failPath); echo "PASS test-prune-owner-control-bug-update-receipts-v1\n";
