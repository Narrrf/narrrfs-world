<?php
// 🧀 Score Adjustment Script - Update existing scores to 10:1 DSPOINC conversion
header('Content-Type: application/json');

// Admin authentication check
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$dbPath = '/var/www/html/db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'analyze_scores':
            analyzeExistingScores($db);
            break;
            
        case 'adjust_scores':
            adjustExistingScores($db);
            break;
            
        case 'get_adjustment_preview':
            getAdjustmentPreview($db);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function analyzeExistingScores($db) {
    // Get all unique users with their highest scores
    $stmt = $db->prepare("
        SELECT 
            discord_id,
            discord_name,
            game,
            MAX(score) as current_score,
            COUNT(*) as total_games
        FROM tbl_tetris_scores 
        GROUP BY discord_id, discord_name, game
        ORDER BY game, current_score DESC
    ");
    $stmt->execute();
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $analysis = [
        'tetris' => [],
        'snake' => [],
        'total_users' => 0,
        'total_adjustments_needed' => 0
    ];
    
    foreach ($scores as $score) {
        $currentScore = $score['current_score'];
        $adjustedScore = $currentScore * 10; // Convert to new 10:1 ratio
        $needsAdjustment = $currentScore < 100; // If score is less than 100, it's likely old format
        
        $scoreData = [
            'discord_id' => $score['discord_id'],
            'discord_name' => $score['discord_name'],
            'current_score' => $currentScore,
            'adjusted_score' => $adjustedScore,
            'total_games' => $score['total_games'],
            'needs_adjustment' => $needsAdjustment
        ];
        
        $analysis[$score['game']][] = $scoreData;
        
        if ($needsAdjustment) {
            $analysis['total_adjustments_needed']++;
        }
    }
    
    $analysis['total_users'] = count($scores);
    
    echo json_encode([
        'success' => true,
        'analysis' => $analysis
    ]);
}

function getAdjustmentPreview($db) {
    // Show what scores would look like after adjustment
    $stmt = $db->prepare("
        SELECT 
            discord_id,
            discord_name,
            game,
            MAX(score) as current_score
        FROM tbl_tetris_scores 
        WHERE score < 1000  -- Likely old format scores
        GROUP BY discord_id, discord_name, game
        ORDER BY game, current_score DESC
        LIMIT 20
    ");
    $stmt->execute();
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $preview = [];
    foreach ($scores as $score) {
        $preview[] = [
            'discord_name' => $score['discord_name'],
            'game' => $score['game'],
            'current_score' => $score['current_score'],
            'adjusted_score' => $score['current_score'] * 10,
            'improvement' => ($score['current_score'] * 10) - $score['current_score']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'preview' => $preview,
        'message' => 'This shows how scores would look after adjustment to 10:1 ratio'
    ]);
}

function adjustExistingScores($db) {
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Get all scores that need adjustment (likely old format)
        $stmt = $db->prepare("
            SELECT 
                id,
                discord_id,
                discord_name,
                game,
                score,
                season,
                timestamp
            FROM tbl_tetris_scores 
            WHERE score < 1000  -- Likely old format scores
            ORDER BY timestamp ASC
        ");
        $stmt->execute();
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $adjusted_count = 0;
        $errors = [];
        
        foreach ($scores as $score) {
            try {
                $oldScore = $score['score'];
                $newScore = $oldScore * 10; // Convert to new 10:1 ratio
                
                // Update the score
                $updateStmt = $db->prepare("
                    UPDATE tbl_tetris_scores 
                    SET score = ? 
                    WHERE id = ?
                ");
                $updateStmt->bindValue(1, $newScore, PDO::PARAM_INT);
                $updateStmt->bindValue(2, $score['id'], PDO::PARAM_INT);
                $updateStmt->execute();
                
                $adjusted_count++;
                
                // Log the adjustment
                $logStmt = $db->prepare("
                    INSERT INTO tbl_score_adjustments (
                        user_id, 
                        game, 
                        old_score, 
                        new_score, 
                        reason, 
                        adjusted_at
                    ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $logStmt->bindValue(1, $score['discord_id']);
                $logStmt->bindValue(2, $score['game']);
                $logStmt->bindValue(3, $oldScore, PDO::PARAM_INT);
                $logStmt->bindValue(4, $newScore, PDO::PARAM_INT);
                $logStmt->bindValue(5, 'DSPOINC conversion fix - 10:1 ratio');
                $logStmt->execute();
                
            } catch (Exception $e) {
                $errors[] = "Error adjusting score ID {$score['id']}: " . $e->getMessage();
            }
        }
        
        // Also update user_scores table if needed
        $userScoreStmt = $db->prepare("
            SELECT user_id, game, score 
            FROM tbl_user_scores 
            WHERE score < 1000
        ");
        $userScoreStmt->execute();
        $userScores = $userScoreStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($userScores as $userScore) {
            $oldScore = $userScore['score'];
            $newScore = $oldScore * 10;
            
            $updateUserStmt = $db->prepare("
                UPDATE tbl_user_scores 
                SET score = ?, last_updated = CURRENT_TIMESTAMP
                WHERE user_id = ? AND game = ?
            ");
            $updateUserStmt->bindValue(1, $newScore, PDO::PARAM_INT);
            $updateUserStmt->bindValue(2, $userScore['user_id']);
            $updateUserStmt->bindValue(3, $userScore['game']);
            $updateUserStmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully adjusted $adjusted_count scores to 10:1 DSPOINC ratio",
            'adjusted_count' => $adjusted_count,
            'user_scores_updated' => count($userScores),
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Transaction failed: ' . $e->getMessage()
        ]);
    }
}
?> 