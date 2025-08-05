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
$score = $data['score'] ?? null;
$discord_id = $data['discord_id'] ?? null;
$discord_name = $data['discord_name'] ?? null;
$game = $data['game'] ?? 'tetris'; // default to tetris if not specified

if (!$wallet || !$score || !$game) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing wallet, score or game']);
    exit;
}

// 💾 Save to DB
$stmt = $db->prepare("
  INSERT INTO tbl_tetris_scores (wallet, score, discord_id, discord_name, game)
  VALUES (:wallet, :score, :discord_id, :discord_name, :game)
");

$stmt->bindValue(':wallet', $wallet);
$stmt->bindValue(':score', $score, PDO::PARAM_INT);
$stmt->bindValue(':discord_id', $discord_id);
$stmt->bindValue(':discord_name', $discord_name);
$stmt->bindValue(':game', $game);
$stmt->execute();

// 🏆 Check for WL Role Eligibility
$wl_result = checkWLEligibility($db, $discord_id, $game, $score);

echo json_encode([
    'success' => true, 
    'message' => "Score saved for $game",
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
        // Use the WL role granting API
        $api_url = 'https://narrrfs.world/api/admin/grant-wl-role.php';
        
        $data = [
            'action' => 'grant_wl_role',
            'user_id' => $user_id,
            'game' => $game,
            'score' => $score,
            'role_id' => $role_id
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            return $result && isset($result['success']) && $result['success'];
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("WL role grant error: " . $e->getMessage());
        return false;
    }
}
?>
