<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database path
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get open quests count
    $stmt = $pdo->query("SELECT COUNT(*) as open_quests FROM tbl_quests WHERE is_active = 1");
    $openQuests = $stmt->fetch(PDO::FETCH_ASSOC)['open_quests'];

    // Get pending claims count
    $stmt = $pdo->query("SELECT COUNT(*) as pending_claims FROM tbl_quest_claims WHERE status = 'pending'");
    $pendingClaims = $stmt->fetch(PDO::FETCH_ASSOC)['pending_claims'];

    // Get completed quests count (approved claims)
    $stmt = $pdo->query("SELECT COUNT(*) as completed_quests FROM tbl_quest_claims WHERE status = 'approved'");
    $completedQuests = $stmt->fetch(PDO::FETCH_ASSOC)['completed_quests'];

    // Get roles granted count (quests with role_id that have approved claims)
    $stmt = $pdo->query("SELECT COUNT(*) as roles_granted 
                         FROM tbl_quest_claims qc 
                         JOIN tbl_quests q ON qc.quest_id = q.quest_id 
                         WHERE qc.status = 'approved' AND q.role_id IS NOT NULL AND q.role_id != ''");
    $rolesGranted = $stmt->fetch(PDO::FETCH_ASSOC)['roles_granted'];

    // Get recent quest claims (last 5)
    $stmt = $pdo->query("SELECT qc.claim_id, qc.user_id, qc.status, qc.claimed_at, q.description as quest_title, u.username
                         FROM tbl_quest_claims qc
                         JOIN tbl_quests q ON qc.quest_id = q.quest_id
                         LEFT JOIN tbl_users u ON qc.user_id = u.discord_id
                         ORDER BY qc.claimed_at DESC
                         LIMIT 5");
    $recentClaims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get active quests by type
    $stmt = $pdo->query("SELECT type, COUNT(*) as count 
                         FROM tbl_quests 
                         WHERE is_active = 1 
                         GROUP BY type 
                         ORDER BY count DESC");
    $activeQuestsByType = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'open_quests' => $openQuests,
            'pending_claims' => $pendingClaims,
            'completed_quests' => $completedQuests,
            'roles_granted' => $rolesGranted,
            'recent_claims' => $recentClaims,
            'active_quests_by_type' => $activeQuestsByType
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