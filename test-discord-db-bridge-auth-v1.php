<?php
/** Disposable-only HTTP regression; never runs against an application database. */
declare(strict_types=1);
$root = sys_get_temp_dir() . '/discord-bridge-auth-' . bin2hex(random_bytes(8));
mkdir($root . '/api/discord', 0700, true);
mkdir($root . '/db', 0700, true);
copy(__DIR__ . '/api/discord/db-access.php', $root . '/api/discord/db-access.php');
$db = new SQLite3($root . '/db/narrrf_world.sqlite');
$db->exec('CREATE TABLE samples (id INTEGER PRIMARY KEY, value TEXT)');
$db->close();
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if (!$socket) throw new RuntimeException('Cannot reserve local port');
$port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
fclose($socket);
$token = 'dummy-valid-token-for-isolated-test-only';
function startFixture(string $root, int $port, ?string $token, array &$pipes, ?string $alternate = null) {
    $env = getenv();
    unset($env['DISCORD_BOT_SECRET'], $env['DISCORD_SECRET']);
    if ($token !== null) $env['DISCORD_BOT_SECRET'] = $token;
    if ($alternate !== null) $env['DISCORD_SECRET'] = $alternate;
    $process = proc_open([PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $root],
        [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, $root, $env);
    if (!is_resource($process)) throw new RuntimeException('Cannot start fixture');
    $ready = false;
    for ($i=0; $i<100; $i++) {
        $conn = @fsockopen('127.0.0.1', $port, $e, $m, 0.1);
        if ($conn) { fclose($conn); $ready = true; break; }
        usleep(50000);
    }
    if (!$ready) throw new RuntimeException('Fixture did not start');
    return $process;
}
function requestFixture(int $port, ?string $token, string $sql, array $params = []): array {
    $headers = "Content-Type: application/json\r\n";
    if ($token !== null) $headers .= "Authorization: $token\r\n";
    $ctx = stream_context_create(['http' => ['method'=>'POST','header'=>$headers,
        'content'=>json_encode(['action'=>'query','query'=>$sql,'params'=>$params]),
        'ignore_errors'=>true,'timeout'=>5]]);
    $data = file_get_contents("http://127.0.0.1:$port/api/discord/db-access.php", false, $ctx);
    $status = $http_response_header[0] ?? '';
    $json = json_decode((string)$data, true);
    if (!is_array($json)) throw new RuntimeException('Invalid fixture response');
    return [$status, $json];
}
function stopFixture($process, array $pipes, string $token, ?string $alternate = null): void {
    proc_terminate($process);
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
    foreach (array_filter([$token, $alternate]) as $credential) {
        if (str_contains($out . $err, substr($credential,0,10))) {
            throw new RuntimeException('Credential material appeared in fixture logs');
        }
    }
}
$pipes = [];
$alternate = 'dummy-alternate-token-for-isolated-test';
$server = startFixture($root, $port, $token, $pipes, $alternate);
try {
    foreach ([null, '', 'incorrect-token'] as $wrong) {
        [$status,$json] = requestFixture($port, $wrong, "INSERT INTO samples(value) VALUES('unauthorized')");
        if (!str_contains($status,'401') || ($json['error'] ?? '') !== 'Unauthorized') throw new RuntimeException('Unauthorized request accepted');
    }
    [$status,$json] = requestFixture($port, $token, 'SELECT COUNT(*) AS n FROM samples');
    if (($json['data'][0]['n'] ?? -1) !== 0) throw new RuntimeException('Unauthorized write changed fixture');
    [$status,$json] = requestFixture($port, $token, 'INSERT INTO samples(value) VALUES(?)', ['fixture']);
    if (!str_contains($status,'200') || ($json['affectedRows'] ?? 0) !== 1) throw new RuntimeException('Authorized fixture write failed');
    [$status,$json] = requestFixture($port, $token, 'SELECT value FROM samples');
    if (!str_contains($status,'200') || ($json['data'][0]['value'] ?? '') !== 'fixture') throw new RuntimeException('Authorized fixture read failed');
    [$status,$json] = requestFixture($port, $alternate, 'SELECT value FROM samples');
    if (!str_contains($status,'200') || ($json['data'][0]['value'] ?? '') !== 'fixture') throw new RuntimeException('Alternate credential rejected');
    [$status,$json] = requestFixture($port, $token, 'UPDATE samples SET value=? WHERE value=?', ['updated','fixture']);
    if (($json['affectedRows'] ?? 0) !== 1) throw new RuntimeException('Authorized fixture update failed');
    [$status,$json] = requestFixture($port, $alternate, 'DELETE FROM samples WHERE value=?', ['updated']);
    if (($json['affectedRows'] ?? 0) !== 1) throw new RuntimeException('Authorized fixture delete failed');
} finally { stopFixture($server, $pipes, $token, $alternate); }
$pipes=[]; $server=startFixture($root, $port, null, $pipes);
try {
    [$status,$json]=requestFixture($port, null, 'SELECT 1');
    if (!str_contains($status,'503') || ($json['error'] ?? '') !== 'Service unavailable') throw new RuntimeException('Missing config must fail closed');
} finally { stopFixture($server, $pipes, $token); }
$pipes=[]; $server=startFixture($root, $port, '', $pipes);
try {
    [$status,$json]=requestFixture($port, '', 'SELECT 1');
    if (!str_contains($status,'503') || ($json['error'] ?? '') !== 'Service unavailable') throw new RuntimeException('Empty config must fail closed');
} finally { stopFixture($server, $pipes, $token); }
echo "PASS: authorized read/write fixture, anonymous/invalid 401, missing-config 503, no credential logging\n";
