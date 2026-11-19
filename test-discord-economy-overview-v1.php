<?php
/** Disposable-only API checks; never points at an application database. */
declare(strict_types=1);
$root = sys_get_temp_dir() . '/economy-v1-' . bin2hex(random_bytes(8));
mkdir($root . '/api/discord', 0700, true);
mkdir($root . '/db', 0700, true);
copy(__DIR__ . '/api/discord/economy-overview-v1.php', $root . '/api/discord/economy-overview-v1.php');
$db = new SQLite3($root . '/db/narrrf_world.sqlite');
$db->exec("CREATE TABLE tbl_user_scores(user_id TEXT, score INTEGER, timestamp TEXT); INSERT INTO tbl_user_scores VALUES ('private-a',100,datetime('now')),('private-b',50,datetime('now')),('private-a',-10,datetime('now')); CREATE TABLE tbl_dspoinc_stakes(amount INTEGER,status TEXT,expected_reward INTEGER,reward_paid INTEGER); INSERT INTO tbl_dspoinc_stakes VALUES(20,'active',5,2); CREATE TABLE tbl_mousefight_dspoinc_burns(amount INTEGER,status TEXT,burned_at TEXT); INSERT INTO tbl_mousefight_dspoinc_burns VALUES(7,'burned',datetime('now')),(99,'refunded',datetime('now')); CREATE TABLE tbl_seasons(season_id INTEGER,season_name TEXT,is_active INTEGER); INSERT INTO tbl_seasons VALUES(1,'Test Season',1);");
$db->close();
$before = hash_file('sha256', $root . '/db/narrrf_world.sqlite');
$secret = str_repeat('a', 40);
$endpoint = $root . '/api/discord/economy-overview-v1.php';
$run = static function(string $gate, string $method, string $credential) use ($secret, $endpoint): array {
    $code = 'putenv(' . var_export('NARRRFS_DISCORD_ECONOMY_READ_ENABLED=' . $gate, true) . ');'
        . 'putenv(' . var_export('NARRRFS_DISCORD_ECONOMY_READ_SECRET=' . $secret, true) . ');'
        . '$_SERVER["REQUEST_METHOD"]=' . var_export($method, true) . ';'
        . '$_SERVER["HTTP_AUTHORIZATION"]=' . var_export($credential, true) . ';'
        . 'include ' . var_export($endpoint, true) . ';';
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Unable to launch endpoint');
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException('Endpoint process failed: ' . $err);
    $json = json_decode((string)$out, true);
    if (!is_array($json)) throw new RuntimeException('Invalid endpoint response');
    return $json;
};
if (($run('0', 'GET', '')['error'] ?? '') !== 'unavailable') throw new RuntimeException('gate');
if (($run('1', 'POST', '')['error'] ?? '') !== 'method_not_allowed') throw new RuntimeException('method');
if (($run('1', 'GET', 'Bearer ' . str_repeat('b', 40))['error'] ?? '') !== 'unauthorized') throw new RuntimeException('auth');
$response = $run('1', 'GET', 'Bearer ' . $secret);
$d = $response['data'] ?? [];
if (($response['success'] ?? false) !== true || ($d['total_supply'] ?? null) !== 140 || ($d['funded_users'] ?? null) !== 2 || ($d['top_holder_balance'] ?? null) !== 90 || ($d['mousefight_burned_all_time'] ?? null) !== 7) throw new RuntimeException('aggregate');
if (str_contains(json_encode($response), 'private-') || isset($d['top_holders']) || isset($d['database_path'])) throw new RuntimeException('redaction');
if ($before !== hash_file('sha256', $root . '/db/narrrf_world.sqlite')) throw new RuntimeException('database changed');
echo "PASS: gate, method, authorization, aggregate, redaction and no-write (disposable DB)\n";
