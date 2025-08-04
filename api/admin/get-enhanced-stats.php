<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection - use production path for Render
$db_path = '/var/www/html/db/narrrf_world.sqlite';
if (!file_exists($db_path)) {
    $db_path = __DIR__ . '/../../db/narrrf_world.sqlite';
}

try {
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    // Get basic stats first
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM tbl_users")->fetchColumn();
    $totalClicks = $pdo->query("SELECT COUNT(*) FROM tbl_cheese_clicks")->fetchColumn();
    $totalScoreRecords = $pdo->query("SELECT COUNT(*) FROM tbl_user_scores")->fetchColumn();
    
    // Debug logging
    error_log("Enhanced Stats Debug - Total Users: $totalUsers, Total Clicks: $totalClicks, Total Score Records: $totalScoreRecords");
    
    // Calculate immediate stats
    $averageClicksPerUser = $totalUsers > 0 ? round($totalClicks / $totalUsers, 2) : 0;
    
    // Get total $DSPOINC in circulation
    $totalDSPOINC = $pdo->query("SELECT COALESCE(SUM(score), 0) FROM tbl_user_scores")->fetchColumn();
    $averageBalancePerUser = $totalUsers > 0 ? round($totalDSPOINC / $totalUsers, 2) : 0;
    
    // Get quest completion rate
    $totalQuestClaims = $pdo->query("SELECT COUNT(*) FROM tbl_quest_claims")->fetchColumn();
    $completedQuests = $pdo->query("SELECT COUNT(*) FROM tbl_quest_claims WHERE status = 'approved'")->fetchColumn();
    $questCompletionRate = $totalQuestClaims > 0 ? round(($completedQuests / $totalQuestClaims) * 100, 1) : 0;
    
    // Get average quest reward
    $stmt = $pdo->query("SELECT AVG(reward) as avg_reward FROM tbl_quests WHERE reward > 0");
    $avgReward = $stmt->fetch(PDO::FETCH_ASSOC)['avg_reward'] ?? 0;
    $averageQuestReward = round($avgReward, 0);
    
    // Get most popular quest type
    $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM tbl_quests GROUP BY type ORDER BY count DESC LIMIT 1");
    $mostPopularQuest = $stmt->fetch(PDO::FETCH_ASSOC);
    $mostPopularQuestType = $mostPopularQuest ? $mostPopularQuest['type'] : 'None';
    $mostPopularQuestCount = $mostPopularQuest ? $mostPopularQuest['count'] : 0;
    
    // Get daily stats (last 24 hours)
    $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $clicksLast24h = $pdo->query("SELECT COUNT(*) FROM tbl_cheese_clicks WHERE timestamp >= '$yesterday'")->fetchColumn();
    $newUsersToday = $pdo->query("SELECT COUNT(*) FROM tbl_users WHERE created_at >= '$yesterday'")->fetchColumn();
    $questsClaimedToday = $pdo->query("SELECT COUNT(*) FROM tbl_quest_claims WHERE claimed_at >= '$yesterday'")->fetchColumn();
    $rolesGrantedToday = $pdo->query("SELECT COUNT(*) FROM tbl_quest_claims WHERE status = 'approved' AND reviewed_at >= '$yesterday'")->fetchColumn();
    
    // Get wealth distribution (richest vs poorest)
    $stmt = $pdo->query("SELECT 
                          MAX(score) as max_balance,
                          MIN(score) as min_balance,
                          AVG(score) as avg_balance
                        FROM tbl_user_scores");
    $wealthStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get power users (users with >100 clicks)
    $stmt = $pdo->query("SELECT COUNT(*) FROM (
        SELECT user_wallet, COUNT(*) as click_count 
        FROM tbl_cheese_clicks 
        GROUP BY user_wallet 
        HAVING COUNT(*) > 100
    )");
    $powerUsers = $stmt->fetchColumn();
    
    // Get active users (last 7 days)
    $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
    $activeUsers7Days = $pdo->query("SELECT COUNT(DISTINCT user_wallet) FROM tbl_cheese_clicks WHERE timestamp >= '$weekAgo'")->fetchColumn();
    
    // Calculate user retention rate
    $userRetentionRate = $totalUsers > 0 ? round(($activeUsers7Days / $totalUsers) * 100, 1) : 0;
    
    // Debug logging
    error_log("Enhanced Stats Debug - Power Users: $powerUsers, Active Users 7 Days: $activeUsers7Days, Retention Rate: $userRetentionRate%");
    
    echo json_encode([
        'success' => true,
        'immediate_stats' => [
            'average_clicks_per_user' => $averageClicksPerUser,
            'total_dspoinc_circulation' => $totalDSPOINC,
            'average_balance_per_user' => $averageBalancePerUser,
            'quest_completion_rate' => $questCompletionRate,
            'average_quest_reward' => $averageQuestReward,
            'most_popular_quest_type' => $mostPopularQuestType,
            'most_popular_quest_count' => $mostPopularQuestCount
        ],
        'daily_stats' => [
            'clicks_last_24h' => $clicksLast24h,
            'new_users_today' => $newUsersToday,
            'quests_claimed_today' => $questsClaimedToday,
            'roles_granted_today' => $rolesGrantedToday
        ],
        'engagement_stats' => [
            'power_users' => $powerUsers,
            'active_users_7_days' => $activeUsers7Days,
            'user_retention_rate' => $userRetentionRate
        ],
        'wealth_distribution' => [
            'max_balance' => $wealthStats['max_balance'] ?? 0,
            'min_balance' => $wealthStats['min_balance'] ?? 0,
            'avg_balance' => round($wealthStats['avg_balance'] ?? 0, 2)
        ],
        'base_stats' => [
            'total_users' => $totalUsers,
            'total_clicks' => $totalClicks,
            'total_score_records' => $totalScoreRecords
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get enhanced stats: ' . $e->getMessage()]);
}
?>