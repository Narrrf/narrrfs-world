<?php
// 🧠 Cheese Architect API — Save Game Score to SQLite (supports Tetris + Snake)

// Suppress all errors and warnings to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

// Set proper headers for JSON response
header('Content-Type: application/json');

// Helper function for formatting time (for glyph memory)
function formatTimeMs($ms) {
    $totalSec = floor($ms / 1000);
    $min = floor($totalSec / 60);
    $sec = $totalSec % 60;
    return sprintf("%02d:%02d", $min, $sec);
}

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
    
    // 🧩 Glyph Memory specific fields
    $difficulty = $data['difficulty'] ?? null;
    $time_ms = $data['time_ms'] ?? null;
    $pairs_matched = $data['pairs_matched'] ?? null;

    // 🧩 Glyph Memory doesn't require wallet (time-based scoring, not DSPOINC-based)
    if ($game === 'glyph_memory') {
        if (!$discord_id || !$time_ms || !$difficulty || !$pairs_matched) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing discord_id, time_ms, difficulty, or pairs_matched for glyph_memory']);
            exit;
        }
        // Use empty wallet for glyph memory (not DSPOINC-based)
        $wallet = $wallet ?? '';
        $raw_score = $time_ms; // Use time_ms as raw_score for consistency
    } else {
        // Other games require wallet and score
        if (!$wallet || !$raw_score || !$game) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing wallet, score or game']);
            exit;
        }
    }

    // 🎮 Get current active season and settings
    // 🔍 Automatically detect current season from tbl_seasons table (where is_active = 1)
    $seasonDetectStmt = $db->prepare("SELECT season_name FROM tbl_seasons WHERE is_active = 1 ORDER BY start_date DESC LIMIT 1");
    $seasonDetectStmt->execute();
    $currentSeason = $seasonDetectStmt->fetchColumn() ?: 'Season 9'; // Fallback to Season 9
    
    error_log("🔍 Current season detected: $currentSeason");

    // Get season settings for scoring and limits
    $seasonStmt = $db->prepare("SELECT season_name, tetris_max_score, snake_max_score, points_per_line, points_per_cheese, space_invaders_max_score, points_per_invader FROM tbl_season_settings WHERE season_name = ?");
    $seasonStmt->execute([$currentSeason]);
    $seasonSettings = $seasonStmt->fetch(PDO::FETCH_ASSOC);

    // 🔍 DEBUG: Log the exact values retrieved from database
    if ($game === 'space_invaders') {
        error_log("🔍 DEBUG Space Invaders - Raw season settings: " . json_encode($seasonSettings));
        error_log("🔍 DEBUG Space Invaders - points_per_invader value: " . ($seasonSettings['points_per_invader'] ?? 'NOT_FOUND'));
        error_log("🔍 DEBUG Space Invaders - Raw score received: " . $raw_score);
        
        // 🔍 DEBUG: Show all available keys
        error_log("🔍 DEBUG Space Invaders - Available keys: " . implode(', ', array_keys($seasonSettings)));
        
        // 🔍 DEBUG: Show the exact database row
        $seasonStmt2 = $db->prepare("SELECT * FROM tbl_season_settings WHERE season_name = ?");
        $seasonStmt2->execute([$currentSeason]);
        $rawRow = $seasonStmt2->fetch(PDO::FETCH_NUM);
        error_log("🔍 DEBUG Space Invaders - Raw database row: " . json_encode($rawRow));
    }

    if (!$seasonSettings) {
        // No season settings found - create default settings
        error_log("No season settings found for season: $currentSeason - creating default settings");
        
        // Create default settings if none exist (1:1 ratio for tetris, 10:1 ratio for snake, 0.01 for space invaders)
        $createSettingsStmt = $db->prepare("INSERT INTO tbl_season_settings (season_name, tetris_max_score, snake_max_score, space_invaders_max_score, points_per_line, points_per_cheese, points_per_invader) VALUES (?, 10000, 10000, 10000, 1, 10, 0.01)");
        $createSettingsStmt->execute([$currentSeason]);
        
        // Fetch the newly created settings
        $seasonStmt = $db->prepare("SELECT season_name, tetris_max_score, snake_max_score, points_per_line, points_per_cheese, space_invaders_max_score, points_per_invader FROM tbl_season_settings WHERE season_name = ?");
        $seasonStmt->execute([$currentSeason]);
        $seasonSettings = $seasonStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$seasonSettings) {
            error_log("Failed to create season settings - using emergency defaults");
            $seasonSettings = [
                'tetris_max_score' => 10000,
                'snake_max_score' => 10000,
                'space_invaders_max_score' => 10000,
                'points_per_line' => 1,
                'points_per_cheese' => 10,
                'points_per_invader' => 0.01
            ];
        }
    }

    // 🧀 Convert raw score to DSPOINC (use season settings for points per unit)
    $pointsPerUnit = 0;
    $unit = '';
    if ($game === 'tetris') {
        // 🔧 FIX: Tetris frontend already calculates DSPOINC (lines * 2)
        // Don't multiply again - use score as-is
        $pointsPerUnit = 1; // No multiplication needed
        $unit = 'lines';
        $dspoinc_score = $raw_score; // Use score directly (already DSPOINC)
    } elseif ($game === 'snake') {
        // 🔧 FIX: Snake frontend now calculates DSPOINC (like Tetris and Space Invaders)
        // Don't multiply again - use score as-is (already includes role bonus)
        $pointsPerUnit = 1; // No multiplication needed
        $unit = 'dspoinc';
        $dspoinc_score = $raw_score; // Use score directly (already DSPOINC with role bonus)
    } elseif ($game === 'space_invaders') {
        // 🔧 SEASON 5 FIX: Space Invaders 10:1 conversion for balanced scoring
        // Frontend sends full DSPOINC (with role bonus), backend divides by 10
        $pointsPerUnit = 0.1; // 10:1 conversion ratio
        $unit = 'invaders';
        $original_dspoinc = $raw_score; // Store original for logging
        $dspoinc_score = floor($raw_score / 10); // 10:1 conversion (2,000 → 200)
        error_log("🎯 Space Invaders 10:1 conversion: {$original_dspoinc} DSPOINC → {$dspoinc_score} DSPOINC (saved)");
    } elseif ($game === 'glyph_memory') {
        // 🧩 Glyph Memory: Time-based scoring (no DSPOINC rewards yet - future feature)
        // Store time_ms directly (lower is better)
        $pointsPerUnit = 0; // No DSPOINC conversion for time-based scoring
        $unit = 'milliseconds';
        $dspoinc_score = 0; // No DSPOINC rewards for glyph memory (yet)
        error_log("🧩 Glyph Memory score: difficulty=$difficulty, time_ms=$time_ms, pairs=$pairs_matched");
    } else {
        $pointsPerUnit = 10; // Default fallback
        $unit = 'units';
        $dspoinc_score = $raw_score * $pointsPerUnit;
    }

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

    // 🚨 FINAL SAFETY CHECK: Prevent negative scores from being saved (Bug #159 - Backend protection)
    if ($dspoinc_score < 0) {
        error_log("⚠️ NEGATIVE SCORE PREVENTED IN BACKEND: Game=$game, Score=$dspoinc_score, User=$discord_id - Converting to 0");
        $dspoinc_score = 0;
    }

    // 💾 Save to DB with DSPOINC score (always save, not just high scores)
    try {
        if ($game === 'glyph_memory') {
            // 🧩 Glyph Memory scores go to dedicated glyph_memory table
            // Check if table exists first
            $tableCheck = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_glyph_memory_scores'");
            $tableCheck->execute();
            if (!$tableCheck->fetch()) {
                error_log("⚠️ tbl_glyph_memory_scores table does not exist - please run migration: db/migrations/create_glyph_memory_scores_table.sql");
                throw new Exception("Glyph Memory scores table not found - migration required");
            }
            
            $stmt = $db->prepare("
              INSERT INTO tbl_glyph_memory_scores (discord_id, discord_name, difficulty, time_ms, pairs_matched, season)
              VALUES (:discord_id, :discord_name, :difficulty, :time_ms, :pairs_matched, :season)
            ");

            $stmt->bindValue(':discord_id', $discord_id);
            $stmt->bindValue(':discord_name', $discord_name);
            $stmt->bindValue(':difficulty', $difficulty);
            $stmt->bindValue(':time_ms', $time_ms, PDO::PARAM_INT);
            $stmt->bindValue(':pairs_matched', $pairs_matched, PDO::PARAM_INT);
            $stmt->bindValue(':season', $currentSeason);
            $stmt->execute();
            
            error_log("🧩 Glyph Memory score inserted: difficulty=$difficulty, time_ms=$time_ms, pairs=$pairs_matched for user $discord_id in season $currentSeason");
        } elseif ($game === 'tetris') {
            // Tetris scores go to dedicated tetris table
            $stmt = $db->prepare("
              INSERT INTO tbl_tetris_scores (wallet, score, discord_id, discord_name, game, season)
              VALUES (:wallet, :score, :discord_id, :discord_name, :game, :season)
            ");

            $stmt->bindValue(':wallet', $wallet);
            $stmt->bindValue(':score', round($dspoinc_score), PDO::PARAM_INT);
            $stmt->bindValue(':discord_id', $discord_id);
            $stmt->bindValue(':discord_name', $discord_name);
            $stmt->bindValue(':game', $game);
            $stmt->bindValue(':season', $currentSeason);
            $stmt->execute();
            
            error_log("Tetris score inserted: $raw_score lines = " . round($dspoinc_score) . " DSPOINC for user $discord_id in season $currentSeason");
        } else {
            // 🚀 CRITICAL FIX: Snake and Space Invaders scores ALSO go to tbl_tetris_scores for mission status
            $stmt = $db->prepare("
              INSERT INTO tbl_tetris_scores (wallet, score, discord_id, discord_name, game, season)
              VALUES (:wallet, :score, :discord_id, :discord_name, :game, :season)
            ");

            $stmt->bindValue(':wallet', $wallet);
            $stmt->bindValue(':score', $dspoinc_score, PDO::PARAM_INT); // 🐛 FIX (Nov 3): Use converted DSPOINC score (applies 10:1 for Space Invaders)
            $stmt->bindValue(':discord_id', $discord_id);
            $stmt->bindValue(':discord_name', $discord_name);
            $stmt->bindValue(':game', $game);
            $stmt->bindValue(':season', $currentSeason);
            $stmt->execute();
            
            error_log("$game score inserted to tbl_tetris_scores: $dspoinc_score DSPOINC for user $discord_id in season $currentSeason");
        }
        
    } catch (Exception $e) {
        error_log("Error inserting game score: " . $e->getMessage());
        throw $e;
    }

    // 🎯 Add score to user's DSPOINC balance (ALWAYS add, not just high scores)
    // 🧩 Skip DSPOINC updates for glyph_memory (time-based, no DSPOINC rewards yet)
    if ($game !== 'glyph_memory') {
        try {
            // Insert new record for this game score
            $insertStmt = $db->prepare("
                INSERT INTO tbl_user_scores (user_id, score, game, source, season) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->bindValue(1, $discord_id);
            $insertStmt->bindValue(2, round($dspoinc_score), PDO::PARAM_INT);
            $insertStmt->bindValue(3, $game);
            $insertStmt->bindValue(4, 'game_score');
            $insertStmt->bindValue(5, $currentSeason);
            $insertStmt->execute();
            
            error_log("User score inserted: " . round($dspoinc_score) . " DSPOINC for user $discord_id in game $game (season: $currentSeason)");
            
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
                $reason = "$game game score: $dspoinc_score DSPOINC (frontend calculated)";
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
    }

    // 🏆 Check for WL Role Eligibility (use DSPOINC score for threshold check)
    $wl_result = checkWLEligibility($db, $discord_id, $game, $dspoinc_score);

    // Get conversion rate for display (use actual season settings)
    $conversion_rate = "1:$pointsPerUnit";

    // Generate appropriate message based on game type
    if ($game === 'glyph_memory') {
        $timeFormatted = formatTimeMs($time_ms);
        $message = "🧩 Glyph Memory score saved: difficulty=$difficulty, time=$timeFormatted ($time_ms ms), pairs=$pairs_matched";
    } elseif ($game === 'tetris') {
        $message = "Score saved for $game: " . round($dspoinc_score) . " DSPOINC (frontend calculated)";
    } elseif ($game === 'snake') {
        $message = "Score saved for $game: $raw_score cheese = " . round($dspoinc_score) . " DSPOINC ($conversion_rate)";
    } elseif ($game === 'space_invaders') {
        // Show 10:1 conversion in message
        $message = "Score saved for $game: $raw_score DSPOINC displayed → " . round($dspoinc_score) . " DSPOINC saved (10:1 Season 5 conversion)";
    } else {
        $message = "Score saved for $game: $raw_score $unit = " . round($dspoinc_score) . " DSPOINC ($conversion_rate)";
    }

    if ($score_capped) {
        $message .= " (capped at max score: $max_score DSPOINC)";
    }

    // Build response based on game type
    $response = [
        'success' => true, 
        'message' => $message,
        'season' => $currentSeason
    ];
    
    if ($game === 'glyph_memory') {
        // Glyph Memory specific response
        $response['difficulty'] = $difficulty;
        $response['time_ms'] = $time_ms;
        $response['time_formatted'] = formatTimeMs($time_ms);
        $response['pairs_matched'] = $pairs_matched;
    } else {
        // Other games response
        $response['raw_score'] = $raw_score;
        $response['dspoinc_score'] = round($dspoinc_score);
        $response['conversion_rate'] = $conversion_rate;
        $response['score_capped'] = $score_capped;
        $response['max_score'] = $max_score;
        $response['wl_check'] = $wl_result;
    }
    
    echo json_encode($response);

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
