<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database path
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get total cheese clicks
    $stmt = $pdo->query("SELECT COUNT(*) as total_clicks FROM tbl_cheese_clicks");
    $totalClicks = $stmt->fetch(PDO::FETCH_ASSOC)['total_clicks'];

    // Get clicks by egg type
    $stmt = $pdo->query("SELECT egg_id, COUNT(*) as click_count 
                         FROM tbl_cheese_clicks 
                         GROUP BY egg_id 
                         ORDER BY click_count DESC");
    $clicksByEgg = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent clicks (last 24 hours)
    $stmt = $pdo->query("SELECT COUNT(*) as recent_clicks 
                         FROM tbl_cheese_clicks 
                         WHERE timestamp >= datetime('now', '-1 day')");
    $recentClicks = $stmt->fetch(PDO::FETCH_ASSOC)['recent_clicks'];

    // Get top users by cheese clicks with usernames
    $stmt = $pdo->query("SELECT c.user_wallet, u.username, COUNT(*) as click_count 
                         FROM tbl_cheese_clicks c
                         LEFT JOIN tbl_users u ON c.user_wallet = u.discord_id
                         GROUP BY c.user_wallet 
                         ORDER BY click_count DESC 
                         LIMIT 10");
    $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get top clicker with username
    $stmt = $pdo->query("SELECT c.user_wallet, u.username, COUNT(*) as click_count 
                         FROM tbl_cheese_clicks c
                         LEFT JOIN tbl_users u ON c.user_wallet = u.discord_id
                         GROUP BY c.user_wallet 
                         ORDER BY click_count DESC 
                         LIMIT 1");
    $topClicker = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get clicks by day (last 7 days)
    $stmt = $pdo->query("SELECT DATE(timestamp) as click_date, COUNT(*) as daily_clicks 
                         FROM tbl_cheese_clicks 
                         WHERE timestamp >= datetime('now', '-7 days') 
                         GROUP BY DATE(timestamp) 
                         ORDER BY click_date DESC");
    $dailyClicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_clicks' => $totalClicks,
            'recent_clicks_24h' => $recentClicks,
            'clicks_by_egg' => $clicksByEgg,
            'top_users' => $topUsers,
            'top_clicker' => $topClicker,
            'daily_clicks' => $dailyClicks
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => '❌ Database error',
        'details' => $e->getMessage()
    ]);
}
?>