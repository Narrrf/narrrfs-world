<?php
/**
 * Manual script to award missing Level 2 Step 0 reward
 * Run this once to fix the missing reward for Narrrf
 */

require_once __DIR__ . '/../config/database.php';

$discordId = '328601656659017732'; // Narrrf's Discord ID
$riddleId = 'CHEESE_TEMPLE_LEVEL2_STEP0';
$levelId = 'CHEESE_TEMPLE_LEVEL2';
$baseReward = 100;

try {
    $pdo = getDatabaseConnection();
    
    // Check if already exists
    $checkStmt = $pdo->prepare("SELECT id FROM tbl_riddle_completions WHERE discord_id = ? AND riddle_id = ?");
    $checkStmt->execute([$discordId, $riddleId]);
    if ($checkStmt->fetch()) {
        echo "✅ Step 0 reward already exists. No action needed.\n";
        exit;
    }
    
    // Get role multiplier (same logic as riddle-reward.php)
    $ROLE_MULTIPLIERS_BY_NAME = [
        '🎴 VIP Holder' => 2.0,
        'VIP Holder' => 2.0,
        '🏆 Holder' => 1.5,
        'Holder' => 1.5,
        'Champion' => 1.4,
        'WL' => 1.3,
        'Season Tester' => 1.3,
        'Early Bird' => 1.2,
        '🧀 Cheese Hunter' => 1.1,
        'Cheese Hunter' => 1.1
    ];
    
    $roleStmt = $pdo->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
    $roleStmt->execute([$discordId]);
    $roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $multiplier = 1.0;
    $multiplierSource = 'none';
    
    $priorityRoleNames = [
        '🎴 VIP Holder',
        'VIP Holder',
        '🏆 Holder',
        'Holder',
        'Champion',
        'WL',
        'Season Tester',
        'Early Bird',
        '🧀 Cheese Hunter',
        'Cheese Hunter'
    ];
    
    foreach ($roles as $role) {
        $roleName = $role['role_name'] ?? '';
        foreach ($priorityRoleNames as $priorityName) {
            if ($roleName === $priorityName && isset($ROLE_MULTIPLIERS_BY_NAME[$priorityName])) {
                $multiplier = $ROLE_MULTIPLIERS_BY_NAME[$priorityName];
                $multiplierSource = $priorityName;
                break 2;
            }
        }
    }
    
    $totalReward = (int)round($baseReward * $multiplier);
    
    // Get current season
    $currentSeason = 'Season 5';
    try {
        $seasonStmt = $pdo->query("SELECT season_name FROM tbl_seasons WHERE is_active = 1 ORDER BY start_date DESC LIMIT 1");
        $seasonRow = $seasonStmt->fetch(PDO::FETCH_ASSOC);
        if ($seasonRow && !empty($seasonRow['season_name'])) {
            $currentSeason = $seasonRow['season_name'];
        }
    } catch (Exception $e) {
        // Fallback
    }
    
    $pdo->beginTransaction();
    
    // Insert riddle completion
    $completionStmt = $pdo->prepare("
        INSERT INTO tbl_riddle_completions (
            discord_id,
            discord_name,
            riddle_id,
            level_id,
            base_reward,
            multiplier,
            total_reward,
            session_id,
            metadata
        ) VALUES (
            :discord_id,
            :discord_name,
            :riddle_id,
            :level_id,
            :base_reward,
            :multiplier,
            :total_reward,
            :session_id,
            :metadata
        )
    ");
    
    $metadata = [
        'multiplier_source' => $multiplierSource,
        'level_id' => $levelId,
        'manually_awarded' => true,
        'awarded_at' => date('Y-m-d H:i:s')
    ];
    
    $completionStmt->execute([
        ':discord_id' => $discordId,
        ':discord_name' => 'Narrrf',
        ':riddle_id' => $riddleId,
        ':level_id' => $levelId,
        ':base_reward' => $baseReward,
        ':multiplier' => $multiplier,
        ':total_reward' => $totalReward,
        ':session_id' => 'manual-fix-' . time(),
        ':metadata' => json_encode($metadata)
    ]);
    
    // Add DSPOINC to user balance
    $scoreStmt = $pdo->prepare("
        INSERT INTO tbl_user_scores (user_id, score, game, source, season)
        VALUES (:user_id, :score, :game, :source, :season)
    ");
    $scoreStmt->execute([
        ':user_id' => $discordId,
        ':score' => $totalReward,
        ':game' => 'cheese_temple_riddles',
        ':source' => 'riddle_completion',
        ':season' => $currentSeason
    ]);
    
    // Track adjustment history
    $reason = sprintf(
        'Riddle completion (%s): base %d × %.2f = %d DSPOINC',
        $riddleId,
        $baseReward,
        $multiplier,
        $totalReward
    );
    
    $adjustStmt = $pdo->prepare("
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason)
        VALUES (:user_id, :admin_id, :amount, :action, :reason)
    ");
    $adjustStmt->execute([
        ':user_id' => $discordId,
        ':admin_id' => 'system-manual-fix',
        ':amount' => $totalReward,
        ':action' => 'add',
        ':reason' => $reason
    ]);
    
    $pdo->commit();
    
    echo "✅ Successfully awarded Level 2 Step 0 reward:\n";
    echo "   - Base Reward: {$baseReward} DSPOINC\n";
    echo "   - Multiplier: {$multiplier}x ({$multiplierSource})\n";
    echo "   - Total Reward: {$totalReward} DSPOINC\n";
    echo "   - Reason: {$reason}\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

