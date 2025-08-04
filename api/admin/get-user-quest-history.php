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

// Database path
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User ID is required']);
        exit;
    }

    // Get user basic info
    $stmt = $pdo->prepare("SELECT discord_id, username, created_at FROM tbl_users WHERE discord_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    // Get cheese click statistics
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

    // Get cheese clicks by egg type
    $stmt = $pdo->prepare("
        SELECT egg_id, COUNT(*) as click_count 
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ? 
        GROUP BY egg_id 
        ORDER BY click_count DESC
    ");
    $stmt->execute([$user_id]);
    $cheese_by_egg = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent cheese clicks (last 20)
    $stmt = $pdo->prepare("
        SELECT egg_id, timestamp 
        FROM tbl_cheese_clicks 
        WHERE user_wallet = ? 
        ORDER BY timestamp DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $recent_cheese_clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get quest claims history
    $stmt = $pdo->prepare("
        SELECT 
            qc.claim_id,
            qc.quest_id,
            qc.proof,
            qc.claimed_at,
            qc.status,
            qc.reviewed_at,
            q.description as quest_title,
            q.type as quest_type,
            q.reward as quest_reward,
            q.role_id as quest_role_id
        FROM tbl_quest_claims qc
        LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id
        WHERE qc.user_id = ?
        ORDER BY qc.claimed_at DESC
    ");
    $stmt->execute([$user_id]);
    $quest_claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get quest completion statistics
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

    // Get quest claims by type
    $stmt = $pdo->prepare("
        SELECT 
            q.type as quest_type,
            COUNT(*) as total_claims,
            COUNT(CASE WHEN qc.status = 'approved' THEN 1 END) as approved_claims,
            COUNT(CASE WHEN qc.status = 'rejected' THEN 1 END) as rejected_claims,
            COUNT(CASE WHEN qc.status = 'pending' THEN 1 END) as pending_claims
        FROM tbl_quest_claims qc
        LEFT JOIN tbl_quests q ON qc.quest_id = q.quest_id
        WHERE qc.user_id = ?
        GROUP BY q.type
        ORDER BY total_claims DESC
    ");
    $stmt->execute([$user_id]);
    $quest_claims_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent quest activity (last 30 days)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as claims_30d,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_30d,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_30d,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_30d
        FROM tbl_quest_claims 
        WHERE user_id = ? AND claimed_at >= datetime('now', '-30 days')
    ");
    $stmt->execute([$user_id]);
    $recent_quest_activity = $stmt->fetch(PDO::FETCH_ASSOC);

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

    echo json_encode([
        'success' => true,
        'user' => $user,
        'cheese_stats' => [
            'total_clicks' => (int)$cheese_stats['total_clicks'],
            'clicks_24h' => (int)$cheese_stats['clicks_24h'],
            'clicks_7d' => (int)$cheese_stats['clicks_7d'],
            'clicks_30d' => (int)$cheese_stats['clicks_30d'],
            'first_click' => $cheese_stats['first_click'],
            'last_click' => $cheese_stats['last_click'],
            'clicks_by_egg' => $cheese_by_egg,
            'recent_clicks' => $recent_cheese_clicks
        ],
        'quest_stats' => [
            'total_claims' => (int)$quest_stats['total_claims'],
            'approved_claims' => (int)$quest_stats['approved_claims'],
            'rejected_claims' => (int)$quest_stats['rejected_claims'],
            'pending_claims' => (int)$quest_stats['pending_claims'],
            'total_rewards_earned' => (int)$quest_stats['total_rewards_earned'],
            'completion_rate' => $completion_rate,
            'claims_by_type' => $quest_claims_by_type,
            'recent_activity' => $recent_quest_activity
        ],
        'quest_claims' => $quest_claims,
        'balance' => (int)$balance['total_balance']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 