<?php
declare(strict_types=1);

/** Disposable two-process concurrency proof for R2.1 optimistic locking. */
if (($argv[1] ?? '') === '--worker') {
    require_once __DIR__ . '/api/lib/owner-bug-tracker-update-v1.php';
    [$dbPath, $readyPath, $startPath, $resultPath, $key] = array_slice($argv, 2);
    file_put_contents($readyPath, 'ready');
    for ($i = 0; $i < 200 && !is_file($startPath); $i++) {
        usleep(10_000);
    }
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
    $db->busyTimeout(5_000);
    try {
        $result = narrrfs_owner_bug_tracker_update($db, 1, [
            'title' => 'parallel title', 'description' => 'parallel description',
            'category_id' => 1, 'priority_id' => 1, 'status_id' => 2,
            'assigned_to' => null, 'resolution_notes' => null,
            'change_reason' => 'parallel test', 'expected_version' => 1,
            'idempotency_key' => $key,
        ], '111111111111111111');
        file_put_contents($resultPath, json_encode(['status' => 200, 'result' => $result]));
    } catch (NarrrfsOwnerBugTrackerUpdateException $error) {
        file_put_contents($resultPath, json_encode(['status' => $error->httpStatus(), 'error' => $error->publicCode()]));
    } finally {
        $db->close();
    }
    exit(0);
}

require_once __DIR__ . '/api/lib/owner-bug-tracker-update-v1.php';
$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'narrrfs-r21-' . bin2hex(random_bytes(6));
mkdir($dir, 0700, true);
$dbPath = $dir . '/fixture.sqlite';
$db = new SQLite3($dbPath);
$db->exec('CREATE TABLE tbl_bug_categories(category_id INTEGER PRIMARY KEY); CREATE TABLE tbl_bug_priorities(priority_id INTEGER PRIMARY KEY); CREATE TABLE tbl_bug_statuses(status_id INTEGER PRIMARY KEY,is_active INTEGER,is_resolved INTEGER); CREATE TABLE tbl_bug_reports(bug_id INTEGER PRIMARY KEY,title TEXT,description TEXT,category_id INTEGER,priority_id INTEGER,status_id INTEGER,assigned_to TEXT,resolution_notes TEXT,created_at TEXT,updated_at TEXT,resolved_at TEXT,owner_control_version INTEGER NOT NULL DEFAULT 1); CREATE TABLE tbl_bug_status_history(history_id INTEGER PRIMARY KEY,bug_id INTEGER,old_status_id INTEGER,new_status_id INTEGER,changed_by TEXT,changed_at TEXT,change_reason TEXT); CREATE TABLE tbl_bug_assignments(assignment_id INTEGER PRIMARY KEY,bug_id INTEGER,assigned_to TEXT,assigned_by TEXT,assigned_at TEXT,unassigned_at TEXT,assignment_notes TEXT); CREATE TABLE tbl_owner_control_bug_update_receipts(receipt_id INTEGER PRIMARY KEY,actor_subject TEXT,idempotency_key_hash TEXT,request_hash TEXT,bug_id INTEGER,expected_version INTEGER,result_version INTEGER,result_json TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP,expires_at TEXT,UNIQUE(actor_subject,idempotency_key_hash)); INSERT INTO tbl_bug_categories VALUES(1); INSERT INTO tbl_bug_priorities VALUES(1); INSERT INTO tbl_bug_statuses VALUES(1,1,0),(2,1,1); INSERT INTO tbl_bug_reports(bug_id,title,description,category_id,priority_id,status_id) VALUES(1,\'before\',\'before\',1,1,1);');
$db->close();
$php = PHP_BINARY;
$workers = [];
foreach (['parallel-key-A-000000000001', 'parallel-key-B-000000000002'] as $index => $key) {
    $ready = "$dir/ready-$index"; $result = "$dir/result-$index";
    $workers[] = [proc_open([$php, __FILE__, '--worker', $dbPath, $ready, "$dir/start", $result, $key], [STDIN, STDOUT, STDERR], $pipes), $ready, $result];
}
for ($i = 0; $i < 200 && (!is_file($workers[0][1]) || !is_file($workers[1][1])); $i++) usleep(10_000);
if (!is_file($workers[0][1]) || !is_file($workers[1][1])) throw new RuntimeException('workers did not synchronize');
touch("$dir/start");
$results = [];
foreach ($workers as [$process, , $result]) { proc_close($process); $results[] = json_decode((string)file_get_contents($result), true, 32, JSON_THROW_ON_ERROR); }
$db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
if (count(array_filter($results, fn($r) => $r['status'] === 200)) !== 1 || count(array_filter($results, fn($r) => ($r['error'] ?? '') === 'version_conflict')) !== 1 || (int)$db->querySingle('SELECT owner_control_version FROM tbl_bug_reports WHERE bug_id=1') !== 2 || (int)$db->querySingle('SELECT COUNT(*) FROM tbl_bug_status_history') !== 1 || (int)$db->querySingle('SELECT COUNT(*) FROM tbl_owner_control_bug_update_receipts') !== 1) throw new RuntimeException('parallel invariant failed');
$db->close();
array_map('unlink', glob($dir . '/*')); rmdir($dir);
echo "PASS test-owner-control-bug-tracker-update-r21-concurrency-v1\n";
