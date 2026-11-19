<?php
/** Bot-only aggregate economy overview; never return holder identities or write data. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function economy_reply(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}
if (getenv('NARRRFS_DISCORD_ECONOMY_READ_ENABLED') !== '1') {
    economy_reply(404, ['success' => false, 'error' => 'unavailable']);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    economy_reply(405, ['success' => false, 'error' => 'method_not_allowed']);
}
$secret = getenv('NARRRFS_DISCORD_ECONOMY_READ_SECRET');
if (!is_string($secret) || !preg_match('/\A[A-Za-z0-9_-]{32,256}\z/D', $secret)) {
    economy_reply(503, ['success' => false, 'error' => 'unavailable']);
}
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (!is_string($auth) || !preg_match('/\ABearer ([A-Za-z0-9_-]{32,256})\z/D', $auth, $m) || !hash_equals($secret, $m[1])) {
    economy_reply(401, ['success' => false, 'error' => 'unauthorized']);
}
$path = PHP_OS_FAMILY === 'Windows' ? dirname(__DIR__, 2) . '/db/narrrf_world.sqlite' : '/var/www/html/db/narrrf_world.sqlite';
if (!is_file($path) || !is_readable($path)) economy_reply(503, ['success' => false, 'error' => 'unavailable']);
try {
    $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    $db->enableExceptions(true);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA query_only=ON');
    $db->exec('BEGIN');
    $scalar = static function(string $sql) use ($db) { return $db->querySingle($sql); };
    $total = (int)$scalar('SELECT COALESCE(SUM(score),0) FROM tbl_user_scores');
    $frozen = (int)$scalar("SELECT COALESCE(SUM(amount),0) FROM tbl_dspoinc_stakes WHERE status='active'");
    $holders = 'SELECT SUM(score) AS balance FROM tbl_user_scores GROUP BY user_id HAVING SUM(score)>0';
    $data = [
        'total_supply' => $total,
        'available_balance' => $total - $frozen,
        'frozen_balance' => $frozen,
        'active_stakes' => (int)$scalar("SELECT COUNT(*) FROM tbl_dspoinc_stakes WHERE status='active'"),
        'pending_rewards' => (int)$scalar("SELECT COALESCE(SUM(COALESCE(expected_reward,0)-COALESCE(reward_paid,0)),0) FROM tbl_dspoinc_stakes WHERE status='active'"),
        'funded_users' => (int)$scalar("SELECT COUNT(*) FROM ($holders)"),
        'average_balance' => (int)round((float)$scalar("SELECT COALESCE(AVG(balance),0) FROM ($holders)")),
        'top_holder_balance' => (int)$scalar("SELECT COALESCE(MAX(balance),0) FROM ($holders)"),
        'entries_24h' => (int)$scalar("SELECT COUNT(*) FROM tbl_user_scores WHERE timestamp>=datetime('now','-24 hours')"),
        'entries_7d' => (int)$scalar("SELECT COUNT(*) FROM tbl_user_scores WHERE timestamp>=datetime('now','-7 days')"),
        'negative_flow_24h' => abs((int)$scalar("SELECT COALESCE(SUM(score),0) FROM tbl_user_scores WHERE score<0 AND timestamp>=datetime('now','-24 hours')")),
        'negative_flow_all_time' => abs((int)$scalar('SELECT COALESCE(SUM(score),0) FROM tbl_user_scores WHERE score<0')),
        'mousefight_burned_24h' => (int)$scalar("SELECT COALESCE(SUM(amount),0) FROM tbl_mousefight_dspoinc_burns WHERE status='burned' AND burned_at>=datetime('now','-24 hours')"),
        'mousefight_burned_all_time' => (int)$scalar("SELECT COALESCE(SUM(amount),0) FROM tbl_mousefight_dspoinc_burns WHERE status='burned'"),
        'staked_ratio' => $total > 0 ? round($frozen / $total * 100, 2) : 0,
        'current_season' => (string)($scalar('SELECT season_name FROM tbl_seasons WHERE is_active=1 ORDER BY season_id DESC LIMIT 1') ?: 'unknown')
    ];
    $db->exec('COMMIT');
    $db->close();
    economy_reply(200, ['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    error_log('[DISCORD ECONOMY] Aggregate query failed');
    economy_reply(503, ['success' => false, 'error' => 'unavailable']);
}
