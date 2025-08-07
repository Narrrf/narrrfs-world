<?php
// 🧠 Cheese Architect API — Save Game Score to SQLite (supports Tetris + Snake)
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 📦 Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents(__DIR__ . '/log.txt', json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

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
    // No season settings found - use safe defaults (10:1 ratio)
    error_log("No season settings found for season: $currentSeason - using safe defaults");
    $seasonSettings = [
        'tetris_max_score' => 10000,
        'snake_max_score' => 10000,
        'points_per_line' => 10,
        'points_per_cheese' => 10
    ];
}

// 🧀 Convert raw score to DSPOINC (use season settings for points per line/cheese)
$pointsPerUnit = ($game === 'tetris') ? $seasonSettings['points_per_line'] : $seasonSettings['points_per_cheese'];
$dspoinc_score = $raw_score * $pointsPerUnit; // Use season settings for scoring

// 🛡️ Check maximum score limit (cheat prevention)
$max_score = ($game === 'tetris') ? $seasonSettings['tetris_max_score'] : $seasonSettings['snake_max_score'];
if ($dspoinc_score > $max_score) {
    $dspoinc_score = $max_score;
    $raw_score = $max_score / $pointsPerUnit; // Adjust raw score to match limit
    $score_capped = true;
} else {
    $score_capped = false;
}

// 💾 Save to DB with DSPOINC score (always save, not just high scores)
$stmt = $db->prepare("
  INSERT INTO tbl_tetris_scores (wallet, score, discord_id, discord_name, game, season)
  VALUES (:wallet, :score, :discord_id, :discord_name, :game, :season)
");

$stmt->bindValue(':wallet', $wallet);
$stmt->bindValue(':score', $dspoinc_score, PDO::PARAM_INT);
$stmt->bindValue(':discord_id', $discord_id);
$stmt->bindValue(':discord_name', $discord_name);
$stmt->bindValue(':game', $game);
$stmt->bindValue(':season', $currentSeason);
$stmt->execute();

// 🎯 Add score to user's DSPOINC balance (ALWAYS add, not just high scores)
try {
    // Insert new record for this game score
    $insertStmt = $db->prepare("
        INSERT INTO tbl_user_scores (user_id, score, game, source) 
        VALUES (?, ?, ?, ?)
    ");
    $insertStmt->bindValue(1, $discord_id);
    $insertStmt->bindValue(2, $dspoinc_score, PDO::PARAM_INT);
    $insertStmt->bindValue(3, $game);
    $insertStmt->bindValue(4, 'game_score');
    $insertStmt->execute();
    
    // 🎯 Create score adjustment entry for tracking
    $adjustmentStmt = $db->prepare("
        INSERT INTO tbl_score_adjustments (user_id, admin_id, amount, action, reason) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $adjustmentStmt->bindValue(1, $discord_id);
    $adjustmentStmt->bindValue(2, 'system'); // System-generated adjustment
    $adjustmentStmt->bindValue(3, $dspoinc_score, PDO::PARAM_INT);
    $adjustmentStmt->bindValue(4, 'add'); // Changed from 'game_score' to 'add' to match table constraint
    $adjustmentStmt->bindValue(5, "$game game score: $raw_score cheese = $dspoinc_score DSPOINC");
    $adjustmentStmt->execute();
    
} catch (Exception $e) {
    error_log("Error updating user scores: " . $e->getMessage());
}

// 🏆 Check for WL Role Eligibility (use DSPOINC score for threshold check)
$wl_result = checkWLEligibility($db, $discord_id, $game, $dspoinc_score);

// Get conversion rate for display (use actual season settings)
$conversion_rate = "1:$pointsPerUnit";

$message = "Score saved for $game: $raw_score cheese = $dspoinc_score DSPOINC ($conversion_rate)";
if ($score_capped) {
    $message .= " (capped at max score: $max_score DSPOINC)";
}

echo json_encode([
    'success' => true, 
    'message' => $message,
    'raw_score' => $raw_score,
    'dspoinc_score' => $dspoinc_score,
    'conversion_rate' => $conversion_rate,
    'score_capped' => $score_capped,
    'max_score' => $max_score,
    'season' => $currentSeason,
    'wl_check' => $wl_result
]);
?>

<?php
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
?>
