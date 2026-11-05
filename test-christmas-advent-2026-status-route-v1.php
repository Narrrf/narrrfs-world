<?php
/** Disposable CLI route contract test; gate paths exit before any SQLite open. */
declare(strict_types=1);

$endpoint = __DIR__ . '/api/user/christmas-advent-2026.php';
$quotedEndpoint = var_export($endpoint, true);

function christmas26_route_run(string $code): string
{
    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, '-r', $code],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('route subprocess did not start');
    }
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || !is_string($output) || $errors !== '') {
        throw new RuntimeException('route subprocess failed');
    }
    return trim($output);
}

function christmas26_route_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

$defaultOff = christmas26_route_run(
    'putenv("NARRRFS_CHRISTMAS_ADVENT_2026_STATUS_ENABLED"); '
    . '$_SERVER["REQUEST_METHOD"]="GET"; require ' . $quotedEndpoint . ';'
);
christmas26_route_assert(
    $defaultOff === '{"success":false,"error":"status_unavailable"}',
    'absent gate returns default-off status before database dispatch'
);

$anonymous = christmas26_route_run(
    'putenv("NARRRFS_CHRISTMAS_ADVENT_2026_STATUS_ENABLED=1"); '
    . '$_SERVER["REQUEST_METHOD"]="GET"; $_SESSION=[]; require ' . $quotedEndpoint . ';'
);
christmas26_route_assert(
    $anonymous === '{"success":false,"error":"authentication_required"}',
    'enabled anonymous GET is denied before database dispatch'
);

$method = christmas26_route_run(
    'putenv("NARRRFS_CHRISTMAS_ADVENT_2026_STATUS_ENABLED=1"); '
    . '$_SERVER["REQUEST_METHOD"]="POST"; require ' . $quotedEndpoint . ';'
);
christmas26_route_assert(
    $method === '{"success":false,"error":"method_not_allowed"}',
    'enabled non-GET request is rejected before authentication or database dispatch'
);

$source = file_get_contents($endpoint);
if (!is_string($source)) {
    throw new RuntimeException('status endpoint source unavailable');
}
foreach (['SQLITE3_OPEN_READONLY', 'PRAGMA query_only = ON', 'payout_authorized'] as $marker) {
    christmas26_route_assert(str_contains($source, $marker), 'endpoint preserves ' . $marker);
}
$responseSource = substr($source, strpos($source, '$status = christmas26_status'));
foreach (['INSERT ', 'UPDATE ', 'DELETE ', 'reward_dspoinc', 'score_id', 'adjustment_id', 'discord_id'] as $forbidden) {
    christmas26_route_assert(!str_contains($responseSource, $forbidden), 'endpoint response path excludes ' . $forbidden);
}

echo "PASS test-christmas-advent-2026-status-route-v1\n";
