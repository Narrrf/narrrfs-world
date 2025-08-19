<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getPDOConnection();
    
    // Debug logging
    error_log("Enhanced quest claims API called");
    error_log("Status filter: " . ($_GET['status'] ?? 'all'));
    error_log("Database connection successful");

    $status_filter = $_GET['status'] ?? 'all';
    $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100 records
    $offset = (int)($_GET['offset'] ?? 0);

    // Build the main query
    $where_clause = "";
    $params = [];
    
    if ($status_filter !== 'all') {
        $where_clause = "WHERE qc.status = ?";
        $params[] = $status_filter;
    }

    $sql = "SELECT 
                qc.claim_id,
                qc.quest_id,
                qc.user_id,
                qc.proof,
                qc.claimed_at,
                qc.status,
                qc.reviewed_at,
                q.description as quest_title,
                q.type as quest_type,
                q.reward as quest_reward,
                q.role_id as quest_role_id,
                u.username
            FROM tbl_quest_claims qc
            LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id
            LEFT JOIN tbl_users u ON qc.user_id = u.discord_id
            $where_clause
            ORDER BY qc.claimed_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enhance each claim with user statistics
    foreach ($claims as &$claim) {
        $user_id = $claim['user_id'];
        
        // Get user's general cheese click statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as clicks_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as clicks_7d,
                COUNT(CASE WHEN timestamp >= datetime('now', '-30 days') THEN 1 END) as clicks_30d,
                MIN(timestamp) as first_click,
                MAX(timestamp) as last_click
            FROM tbl_cheese_clicks 
            WHERE user_wallet = ?
        ");
        $stmt->execute([$user_id]);
        $cheese_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get quest-specific cheese click statistics (if this is a cheese hunt quest)
        $quest_specific_clicks = null;
        if ($claim['quest_type'] === 'cheese_hunt' && $claim['quest_id']) {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as quest_clicks,
                    COUNT(DISTINCT egg_id) as unique_eggs_clicked,
                    GROUP_CONCAT(DISTINCT egg_id) as eggs_clicked,
                    MIN(timestamp) as first_quest_click,
                    MAX(timestamp) as last_quest_click
                FROM tbl_cheese_clicks 
                WHERE user_wallet = ? AND quest_id = ?
            ");
            $stmt->execute([$user_id, $claim['quest_id']]);
            $quest_specific_clicks = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get user's quest completion statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_claims,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_claims,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_claims,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_claims,
                SUM(CASE WHEN status = 'approved' THEN q.reward ELSE 0 END) as total_rewards_earned
            FROM tbl_quest_claims qc
            LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id
            WHERE qc.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $quest_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get user's total balance
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(score), 0) as total_balance 
            FROM tbl_user_scores 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate quest completion rate
        $completion_rate = 0;
        if ($quest_stats['total_claims'] > 0) {
            $completion_rate = round(($quest_stats['approved_claims'] / $quest_stats['total_claims']) * 100, 1);
        }
        
        // Add enhanced data to the claim
        $claim['user_stats'] = [
            'cheese_clicks' => [
                'total' => (int)$cheese_stats['total_clicks'],
                'last_24h' => (int)$cheese_stats['clicks_24h'],
                'last_7d' => (int)$cheese_stats['clicks_7d'],
                'last_30d' => (int)$cheese_stats['clicks_30d'],
                'first_click' => $cheese_stats['first_click'],
                'last_click' => $cheese_stats['last_click']
            ],
            'quest_specific_clicks' => $quest_specific_clicks ? [
                'quest_clicks' => (int)$quest_specific_clicks['quest_clicks'],
                'unique_eggs_clicked' => (int)$quest_specific_clicks['unique_eggs_clicked'],
                'eggs_clicked' => $quest_specific_clicks['eggs_clicked'] ? explode(',', $quest_specific_clicks['eggs_clicked']) : [],
                'first_quest_click' => $quest_specific_clicks['first_quest_click'],
                'last_quest_click' => $quest_specific_clicks['last_quest_click']
            ] : null,
            'quests' => [
                'total_claims' => (int)$quest_stats['total_claims'],
                'approved' => (int)$quest_stats['approved_claims'],
                'rejected' => (int)$quest_stats['rejected_claims'],
                'pending' => (int)$quest_stats['pending_claims'],
                'total_rewards' => (int)$quest_stats['total_rewards_earned'],
                'completion_rate' => $completion_rate
            ],
            'balance' => (int)$balance['total_balance']
        ];
        
        // Add risk assessment
        $risk_level = 'LOW';
        $risk_factors = [];
        
        // Check quest-specific risks first for cheese hunt quests
        if ($claim['quest_type'] === 'cheese_hunt' && $quest_specific_clicks) {
            if ($quest_specific_clicks['quest_clicks'] == 0) {
                $risk_level = 'HIGH';
                $risk_factors[] = 'No cheese clicks recorded for this quest';
            } elseif ($quest_specific_clicks['unique_eggs_clicked'] < 3) {
                $risk_level = 'MEDIUM';
                $risk_factors[] = 'Incomplete cheese hunt - only ' . $quest_specific_clicks['unique_eggs_clicked'] . ' eggs clicked';
            }
        }
        
        // General cheese click activity assessment
        if ($cheese_stats['total_clicks'] < 10) {
            $risk_level = 'HIGH';
            $risk_factors[] = 'Low overall cheese click activity';
        } elseif ($cheese_stats['total_clicks'] < 50) {
            if ($risk_level !== 'HIGH') $risk_level = 'MEDIUM';
            $risk_factors[] = 'Moderate overall cheese click activity';
        }
        
        if ($quest_stats['rejected_claims'] > $quest_stats['approved_claims']) {
            $risk_level = 'HIGH';
            $risk_factors[] = 'More rejected than approved quests';
        }
        
        if ($completion_rate < 50) {
            $risk_level = 'MEDIUM';
            $risk_factors[] = 'Low quest completion rate';
        }
        
        if ($cheese_stats['last_click'] && strtotime($cheese_stats['last_click']) < strtotime('-7 days')) {
            $risk_level = 'MEDIUM';
            $risk_factors[] = 'No recent cheese activity';
        }
        
        $claim['risk_assessment'] = [
            'level' => $risk_level,
            'factors' => $risk_factors
        ];
    }

    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM tbl_quest_claims qc $where_clause";
    $stmt = $pdo->prepare($count_sql);
    if ($status_filter !== 'all') {
        $stmt->execute([$status_filter]);
    } else {
        $stmt->execute();
    }
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Debug logging
    error_log("Returning " . count($claims) . " quest claims");
    error_log("Total count: " . $total_count);
    
    $response = [
        'success' => true,
        'claims' => $claims,
        'pagination' => [
            'total' => (int)$total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ],
        'filters' => [
            'status' => $status_filter
        ]
    ];
    
    error_log("Response: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 