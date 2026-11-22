<?php
declare(strict_types=1);
// Public aggregate only; never disclose individual holder information.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=15, s-maxage=15');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}
$db = null;
$cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'narrrfs-public-economy-pulse-v1.json';
try {
    $cached = is_file($cachePath) ? file_get_contents($cachePath) : false;
    $cache = is_string($cached) ? json_decode($cached, true) : null;
    if (is_array($cache) && is_int($cache['at'] ?? null) && time() - $cache['at'] < 15
        && is_array($cache['data'] ?? null) && is_int($cache['data']['total_supply'] ?? null)
        && is_int($cache['data']['average_balance'] ?? null)) {
        echo json_encode(['success' => true, 'data' => $cache['data']], JSON_THROW_ON_ERROR);
        exit;
    }
    require_once __DIR__ . '/../config/database.php';
    $path = getDatabasePath();
    if (!is_file($path) || !is_readable($path)) throw new RuntimeException('Database unavailable');
    $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA query_only=ON');
    $db->exec('BEGIN');
    require_once __DIR__ . '/../lib/economy-pulse.php';
    $data = narrrfs_public_economy_pulse($db);
    $db->exec('COMMIT');
    $temporary = tempnam(sys_get_temp_dir(), 'economy-pulse-');
    if ($temporary !== false && file_put_contents($temporary, json_encode(['at' => time(), 'data' => $data])) !== false) {
        @rename($temporary, $cachePath);
    } elseif (is_string($temporary) && is_file($temporary)) {
        @unlink($temporary);
    }
    echo json_encode(['success' => true, 'data' => $data], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('[ECONOMY PULSE] Aggregate unavailable');
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'unavailable']);
} finally {
    if ($db instanceof SQLite3) $db->close();
}
