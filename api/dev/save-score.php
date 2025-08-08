<?php
// 🧠 Cheese Architect API — Save Game Score to SQLite (supports Tetris + Snake)

// Suppress all errors and warnings to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Set proper headers for JSON response
header('Content-Type: application/json');

// Wrap everything in try-catch to ensure clean JSON output
try {
    // Error handling for local testing
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    
    // Check if database file exists
    if (!file_exists($dbPath)) {
        error_log("Database file not found: $dbPath");
        echo json_encode([
            'success' => false, 
            'error' => 'Database file not found locally. This is expected for local testing.',
            'local_test' => true
        ]);
        exit;
    }
    
    // Check if database file is writable
    if (!is_writable($dbPath)) {
        error_log("Database file not writable: $dbPath");
        echo json_encode([
            'success' => false, 
            'error' => 'Database file not writable',
            'local_test' => false
        ]);
        exit;
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if required tables exist
    $tables = ['tbl_tetris_scores', 'tbl_user_scores', 'tbl_score_adjustments'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$table]);
        if (!$stmt->fetch()) {
            error_log("Table $table does not exist in database");
            echo json_encode([
                'success' => false, 
                'error' => "Required table $table does not exist in database",
                'local_test' => false
            ]);
            exit;
        }
    }

    // 📦 Parse JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Optional logging - only if directory is writable
    $logPath = __DIR__ . '/log.txt';
    if (is_writable(dirname($logPath))) {
        @file_put_contents($logPath, json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
    }

    // ✅ Extract + validate input
    $wallet = $data['wallet'] ?? null;
    $raw_score = $data['score'] ?? null;
    $discord_id = $data['discord_id'] ?? null;
    $discord_name = $data['discord_name'] ?? null;
    $game = $data['game'] ?? 'tetris'; // default to tetris if not specified

    if (!$wallet || !$raw_score || !$game) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing wallet, score or game']);
        exit;
    }

    // 🎮 Get current active season and settings
    $currentSeason = 'season_1'; // Default to season 1 for now

    // Get season settings for scoring and limits
    $seasonStmt = $db->prepare("SELECT * FROM tbl_season_settings WHERE season_name = ?");
    $seasonStmt->execute([$currentSeason]);
    $seasonSettings = $seasonStmt->fetch(PDO::FETCH_ASSOC);

    if (!$seasonSettings) {
        // No season settings found - use safe defaults
        error_log("No season settings found for season: $currentSeason - using safe defaults");
        $seasonSettings = [
            'tetris_max_score' => 10000,
            'snake_max_score' => 10000,
            'space_invaders_max_score' => 10000,
            'points_per_line' => 10,
            'points_per_cheese' => 10,
            'points_per_invader' => 0.01 // 1,000 invaders = 10 DSPOINC
        ];
    }

    // 🧀 Convert raw score to DSPOINC (use season settings for points per unit)
    $pointsPerUnit = 0;
    $unit = '';
    if ($game === 'tetris') {
        $pointsPerUnit = $seasonSettings['points_per_line'] ?? 10;
        $unit = 'lines';
    } elseif ($game === 'snake') {
        $pointsPerUnit = $seasonSettings['points_per_cheese'] ?? 10;
        $unit = 'cheese';
    } elseif ($game === 'space_invaders') {
        $pointsPerUnit = $seasonSettings['points_per_invader'] ?? 0.01; // 1,000 invaders = 10 DSPOINC
        $unit = 'invaders';
    } else {
        $pointsPerUnit = 10; // Default fallback
        $unit = 'units';
    }

    $dspoinc_score = $raw_score * $pointsPerUnit; // Use season settings for scoring

    // 🛡️ Check maximum score limit (cheat prevention)
    $max_score = 0;
    if ($game === 'tetris') {
        $max_score = $seasonSettings['tetris_max_score'] ?? 10000;
    } elseif ($game === 'snake') {
        $max_score = $seasonSettings['snake_max_score'] ?? 10000;
    } elseif ($game === 'space_invaders') {
        $max_score = $seasonSettings['space_invaders_max_score'] ?? 10000;
    } else {
        $max_score = 10000; // Default fallback
    }

    if ($dspoinc_score > $max_score) {
        $dspoinc_score = $max_score;
        $raw_score = $max_score / $pointsPerUnit; // Adjust raw score to match limit
        $score_capped = true;
    } else {
        $score_capped = false;
    }

    // 💾 Save to DB with DSPOINC score (always save, not just high scores)
    try {
        $stmt = $db->prepare("
          INSERT INTO tbl_tetris_scores (wallet, score, discord_id, discord_name, game, season, is_current_season)
          VALUES (:wallet, :score, :discord_id, :discord_name, :game, :season, 1)
        ");

        $stmt->bindValue(':wallet', $wallet);
        $stmt->bindValue(':score', round($dspoinc_score), PDO::PARAM_INT);
        $stmt->bindValue(':discord_id', $discord_id);
        $stmt->bindValue(':discord_name', $discord_name);
        $stmt->bindValue(':game', $game);
        $stmt->bindValue(':season', $currentSeason);
        $stmt->execute();
        
        // Log successful insert
        error_log("Space Invaders score inserted: $raw_score invaders = " . round($dspoinc_score) . " DSPOINC for user $discord_id");
        
    } catch (Exception $e) {
        error_log("Error inserting Space Invaders score: " . $e->getMessage());
        throw $e;
    }

    // 🎯 Add score to user's DSPOINC balance (ALWAYS add, not just high scores)
    try {
        // Insert new record for this game score
        $insertStmt = $db->prepare("
            INSERT INTO tbl_user_scores (user_id, score, game, source) 
            VALUES (?, ?, ?, ?)
        ");
        $insertStmt->bindValue(1, $discord_id);
        $insertStmt->bindValue(2, round($dspoinc_score), PDO::PARAM_INT);
        $insertStmt->bindValue(3, $game);
        $insertStmt->bindValue(4, 'game_score');
        $insertStmt->execute();
        
        error_log("User score inserted: " . round($dspoinc_score) . " DSPOINC for user $discord_id in game $game");
        
        // 🎯 Create score adjustment entry for tracking
        $adjustmentStmt = $db->prepare("
            INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $adjustmentStmt->bindValue(1, $discord_id);
        $adjustmentStmt->bindValue(2, 'system'); // System-generated adjustment
        $adjustmentStmt->bindValue(3, round($dspoinc_score), PDO::PARAM_INT);
        $adjustmentStmt->bindValue(4, 'add'); // Changed from 'game_score' to 'add' to match table constraint
        
        // Generate appropriate reason message based on game type
        if ($game === 'tetris') {
            $reason = "$game game score: $raw_score lines = $dspoinc_score DSPOINC";
        } elseif ($game === 'snake') {
            $reason = "$game game score: $raw_score cheese = $dspoinc_score DSPOINC";
        } elseif ($game === 'space_invaders') {
            $reason = "$game game score: $raw_score invaders = $dspoinc_score DSPOINC";
        } else {
            $reason = "$game game score: $raw_score $unit = $dspoinc_score DSPOINC";
        }
        
        $adjustmentStmt->bindValue(5, $reason);
        $adjustmentStmt->execute();
        
        error_log("Score adjustment inserted: " . round($dspoinc_score) . " DSPOINC for user $discord_id - $reason");
        
    } catch (Exception $e) {
        error_log("Error updating user scores: " . $e->getMessage());
    }

    // 🏆 Check for WL Role Eligibility (use DSPOINC score for threshold check)
    $wl_result = checkWLEligibility($db, $discord_id, $game, $dspoinc_score);

    // Get conversion rate for display (use actual season settings)
    $conversion_rate = "1:$pointsPerUnit";

    // Generate appropriate message based on game type
    if ($game === 'tetris') {
        $message = "Score saved for $game: $raw_score lines = " . round($dspoinc_score) . " DSPOINC ($conversion_rate)";
    } elseif ($game === 'snake') {
        $message = "Score saved for $game: $raw_score cheese = " . round($dspoinc_score) . " DSPOINC ($conversion_rate)";
    } elseif ($game === 'space_invaders') {
        $message = "Score saved for $game: $raw_score invaders = " . round($dspoinc_score) . " DSPOINC ($conversion_rate)";
    } else {
        $message = "Score saved for $game: $raw_score $unit = " . round($dspoinc_score) . " DSPOINC ($conversion_rate)";
    }

    if ($score_capped) {
        $message .= " (capped at max score: $max_score DSPOINC)";
    }

    echo json_encode([
        'success' => true, 
        'message' => $message,
        'raw_score' => $raw_score,
        'dspoinc_score' => round($dspoinc_score),
        'conversion_rate' => $conversion_rate,
        'score_capped' => $score_capped,
        'max_score' => $max_score,
        'season' => $currentSeason,
        'wl_check' => $wl_result
    ]);

} catch (Exception $e) {
    // Log the exception for debugging purposes
    error_log("An unexpected error occurred: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Internal server error: ' . $e->getMessage(),
        'local_test' => false // Indicate it's not a local test failure
    ]);
}

function checkWLEligibility($db, $user_id, $game, $score) {
    try {
        // Get game settings
        $stmt = $db->prepare("SELECT * FROM tbl_game_settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            return ['eligible' => false, 'message' => 'No WL settings configured'];
        }
        
        // Check if WL is enabled for this game
        $wl_enabled = false;
        $wl_threshold = 0;
        $wl_role_id = '';
        $wl_bonus = 0;
        
        if ($game === 'tetris' && $settings['tetris_wl_enabled']) {
            $wl_enabled = true;
            $wl_threshold = $settings['tetris_wl_threshold'];
            $wl_role_id = $settings['tetris_wl_role_id'];
            $wl_bonus = $settings['tetris_wl_bonus'];
        } elseif ($game === 'snake' && $settings['snake_wl_enabled']) {
            $wl_enabled = true;
            $wl_threshold = $settings['snake_wl_threshold'];
            $wl_role_id = $settings['snake_wl_role_id'];
            $wl_bonus = $settings['snake_wl_bonus'];
        } elseif ($game === 'space_invaders' && $settings['space_invaders_wl_enabled']) {
            $wl_enabled = true;
            $wl_threshold = $settings['space_invaders_wl_threshold'];
            $wl_role_id = $settings['space_invaders_wl_role_id'];
            $wl_bonus = $settings['space_invaders_wl_bonus'];
        }
        
        if (!$wl_enabled || !$wl_role_id) {
            return ['eligible' => false, 'message' => 'WL not enabled for this game'];
        }
        
        // Check if score meets threshold
        if ($score < $wl_threshold) {
            return ['eligible' => false, 'message' => "Score $score is below WL threshold $wl_threshold"];
        }
        
        // Check if user already has WL role for this game
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_wl_role_grants 
                              WHERE user_id = ? AND game = ? AND role_id = ?");
        $stmt->bindValue(1, $user_id, PDO::PARAM_STR);
        $stmt->bindValue(2, $game, PDO::PARAM_STR);
        $stmt->bindValue(3, $wl_role_id, PDO::PARAM_STR);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing['count'] > 0) {
            return ['eligible' => false, 'message' => 'User already has WL role for this game'];
        }
        
        // User is eligible for WL role - grant it automatically
        $role_granted = grantWLRole($user_id, $game, $score, $wl_role_id);
        
        return [
            'eligible' => true,
            'message' => "🎉 WL Role Granted! Score: $score, Threshold: $wl_threshold",
            'role_granted' => $role_granted,
            'role_id' => $wl_role_id,
            'bonus_points' => $wl_bonus
        ];
        
    } catch (Exception $e) {
        error_log("WL eligibility check error: " . $e->getMessage());
        return ['eligible' => false, 'message' => 'Error checking WL eligibility'];
    }
}

function grantWLRole($user_id, $game, $score, $role_id) {
    try {
        // Log the role grant
        $stmt = $db->prepare("
            INSERT INTO tbl_wl_role_grants (user_id, game, score, role_id, granted_at)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->bindValue(1, $user_id);
        $stmt->bindValue(2, $game);
        $stmt->bindValue(3, $score);
        $stmt->bindValue(4, $role_id);
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log("Error granting WL role: " . $e->getMessage());
        return false;
    }
}
