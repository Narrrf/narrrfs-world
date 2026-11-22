<?php
declare(strict_types=1);
require_once __DIR__ . '/api/lib/economy-pulse.php';

function pulse_assert(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$path = tempnam(sys_get_temp_dir(), 'economy-pulse-');
if ($path === false) throw new RuntimeException('fixture unavailable');
try {
    $db = new SQLite3($path);
    $db->exec('CREATE TABLE tbl_users (discord_id TEXT, username TEXT)');
    $db->exec('CREATE TABLE tbl_user_scores (user_id TEXT, score INTEGER)');
    $db->exec("INSERT INTO tbl_users VALUES ('1','One'),('2','Two'),('3','Three'),('4','Four')");
    $db->exec("INSERT INTO tbl_user_scores VALUES ('1',10),('1',5),('2',8),('2',-8),('3',-4),('4',0)");
    $db->exec('PRAGMA query_only=ON');
    $before = (int)$db->querySingle('SELECT total_changes()');
    $data = narrrfs_public_economy_pulse($db);
    pulse_assert($data === ['total_supply' => 11, 'average_balance' => 15], 'legacy aggregates preserve repeated score and positive-holder semantics');
    pulse_assert((int)$db->querySingle('SELECT total_changes()') === $before, 'aggregate helper performs no writes');
    $plan = $db->query('EXPLAIN QUERY PLAN SELECT COALESCE(SUM(score), 0) FROM tbl_user_scores');
    pulse_assert($plan !== false, 'query plan is available on representative fixture');
    $db->close();
    $empty = new SQLite3(':memory:');
    $empty->exec('CREATE TABLE tbl_users (discord_id TEXT, username TEXT)');
    $empty->exec('CREATE TABLE tbl_user_scores (user_id TEXT, score INTEGER)');
    $empty->exec('PRAGMA query_only=ON');
    pulse_assert(narrrfs_public_economy_pulse($empty) === ['total_supply' => 0, 'average_balance' => 0], 'no eligible holders returns zero average');
    $empty->close();
    $invalid = new SQLite3(':memory:');
    $invalid->enableExceptions(true);
    try { narrrfs_public_economy_pulse($invalid); throw new RuntimeException('missing table accepted'); }
    catch (Throwable $error) { pulse_assert($error->getMessage() !== 'missing table accepted', 'unavailable database result fails closed'); }
    $invalid->close();
    echo "PASS: public economy pulse V1\n";
} finally { if (isset($db) && $db instanceof SQLite3) $db->close(); if (is_file($path)) unlink($path); }
