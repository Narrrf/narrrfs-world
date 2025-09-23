<?php
// Twitter Mission Leaderboard API
// Returns leaderboard data for admin interface

// Disable error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

try {
    $db = getSQLite3Connection();
    
    // Get period from request
    $period = $_GET['period'] ?? 'all';
    $limit = (int)($_GET['limit'] ?? 50);
    
    // Build base query
    $query = "
        SELECT 
            user_id,
            username,
            COUNT(*) as completed_missions,
            SUM(reward_claimed) as total_rewards,
            MIN(completed_at) as first_mission,
            MAX(completed_at) as last_mission
        FROM tbl_twitter_mission_participants 
        WHERE verification_status = 'verified'
    ";
    
    // Add time filter based on period
    $now = new DateTime();
    switch ($period) {
        case 'month':
            $startOfMonth = new DateTime($now->format('Y-m-01'));
            $query .= " AND completed_at >= '" . $startOfMonth->format('Y-m-d H:i:s') . "'";
            break;
        case 'last_month':
            $startOfLastMonth = new DateTime($now->format('Y-m-01'));
            $startOfLastMonth->modify('-1 month');
            $endOfLastMonth = new DateTime($now->format('Y-m-01'));
            $endOfLastMonth->modify('-1 day');
            $query .= " AND completed_at >= '" . $startOfLastMonth->format('Y-m-d H:i:s') . "'";
            $query .= " AND completed_at <= '" . $endOfLastMonth->format('Y-m-d 23:59:59') . "'";
            break;
        case 'year':
            $startOfYear = new DateTime($now->format('Y-01-01'));
            $query .= " AND completed_at >= '" . $startOfYear->format('Y-m-d H:i:s') . "'";
            break;
        // 'all' case - no additional filter
    }
    
    $query .= " GROUP BY user_id, username ORDER BY completed_missions DESC, total_rewards DESC LIMIT " . $limit;
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total statistics
    $statsQuery = "
        SELECT 
            COUNT(DISTINCT user_id) as total_users,
            COUNT(*) as total_missions,
            SUM(reward_claimed) as total_rewards_distributed
        FROM tbl_twitter_mission_participants 
        WHERE verification_status = 'verified'
    ";
    
    // Add same time filter for stats
    if ($period !== 'all') {
        $now = new DateTime();
        switch ($period) {
            case 'month':
                $startOfMonth = new DateTime($now->format('Y-m-01'));
                $statsQuery .= " AND completed_at >= '" . $startOfMonth->format('Y-m-d H:i:s') . "'";
                break;
            case 'last_month':
                $startOfLastMonth = new DateTime($now->format('Y-m-01'));
                $startOfLastMonth->modify('-1 month');
                $endOfLastMonth = new DateTime($now->format('Y-m-01'));
                $endOfLastMonth->modify('-1 day');
                $statsQuery .= " AND completed_at >= '" . $startOfLastMonth->format('Y-m-d H:i:s') . "'";
                $statsQuery .= " AND completed_at <= '" . $endOfLastMonth->format('Y-m-d 23:59:59') . "'";
                break;
            case 'year':
                $startOfYear = new DateTime($now->format('Y-01-01'));
                $statsQuery .= " AND completed_at >= '" . $startOfYear->format('Y-m-d H:i:s') . "'";
                break;
        }
    }
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Format results
    $leaderboard = [];
    foreach ($results as $index => $row) {
        $leaderboard[] = [
            'rank' => $index + 1,
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'completed_missions' => (int)$row['completed_missions'],
            'total_rewards' => (int)$row['total_rewards'],
            'first_mission' => $row['first_mission'],
            'last_mission' => $row['last_mission']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'period' => $period,
        'leaderboard' => $leaderboard,
        'statistics' => [
            'total_users' => (int)$stats['total_users'],
            'total_missions' => (int)$stats['total_missions'],
            'total_rewards_distributed' => (int)$stats['total_rewards_distributed']
        ],
        'period_info' => getPeriodInfo($period),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function getPeriodInfo($period) {
    $now = new DateTime();
    switch ($period) {
        case 'month':
            return [
                'display' => 'This Month',
                'description' => $now->format('F Y'),
                'start_date' => $now->format('Y-m-01'),
                'end_date' => $now->format('Y-m-t')
            ];
        case 'last_month':
            $lastMonth = new DateTime($now->format('Y-m-01'));
            $lastMonth->modify('-1 month');
            return [
                'display' => 'Last Month',
                'description' => $lastMonth->format('F Y'),
                'start_date' => $lastMonth->format('Y-m-01'),
                'end_date' => $lastMonth->format('Y-m-t')
            ];
        case 'year':
            return [
                'display' => 'This Year',
                'description' => $now->format('Y'),
                'start_date' => $now->format('Y-01-01'),
                'end_date' => $now->format('Y-12-31')
            ];
        case 'all':
        default:
            return [
                'display' => 'All Time',
                'description' => 'Since the beginning of Twitter missions',
                'start_date' => null,
                'end_date' => null
            ];
    }
}
?>
