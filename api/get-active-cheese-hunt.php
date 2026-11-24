<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

if (getenv('NARRRFS_CHEESE_HUNT_PLAYER_ENABLED') === '1') {
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
        exit;
    }

    $db = null;
    try {
        require_once __DIR__ . '/config/database.php';
        $databasePath = getDatabasePath();
        if (!is_file($databasePath) || !is_readable($databasePath)) {
            throw new RuntimeException('Database unavailable.');
        }

        $db = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $db->busyTimeout(5000);
        $db->exec('PRAGMA query_only = ON');

        $hasMissionsTable = (int)$db->querySingle(
            "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'tbl_owner_cheese_hunt_missions'"
        ) === 1;

        if (!$hasMissionsTable) {
            echo json_encode([
                'success' => true,
                'player_capture_enabled' => true,
                'quest' => null,
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }

        $mission = $db->querySingle(
            "SELECT mission_id, description, reward_amount, required_clicks,
                    movement_pattern, movement_speed, hidden_areas, winner_message, expires_at
             FROM tbl_owner_cheese_hunt_missions
             WHERE status = 'active'
               AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
             ORDER BY mission_id DESC
             LIMIT 1",
            true
        );

        if (!is_array($mission)) {
            echo json_encode([
                'success' => true,
                'player_capture_enabled' => true,
                'quest' => null,
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }

        $requiredClicks = (int)$mission['required_clicks'];
        echo json_encode([
            'success' => true,
            'player_capture_enabled' => true,
            'quest' => [
                'quest_id' => (int)$mission['mission_id'],
                'source' => 'owner_cheese_hunt_v1',
                'description' => (string)$mission['description'],
                'reward' => (int)$mission['reward_amount'],
                'expires_at' => $mission['expires_at'],
                'cheese_config' => [
                    'cheese_count' => $requiredClicks,
                    'required_clicks' => $requiredClicks,
                    'movement_pattern' => (string)$mission['movement_pattern'],
                    'movement_speed' => (string)$mission['movement_speed'],
                    'hidden_areas' => ((int)$mission['hidden_areas']) === 1,
                    'winner_message' => (string)$mission['winner_message'],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES);
    } catch (Throwable) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'cheese_hunt_unavailable',
        ]);
    } finally {
        if ($db instanceof SQLite3) {
            $db->close();
        }
    }
    exit;
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('X-XSS-Protection: 1; mode=block');

try {
    // Use centralized database configuration
    require_once __DIR__ . '/config/database.php';
    $pdo = getDatabaseConnection();

    // Get active cheese hunt quest
    // Expired quests are ignored unless expires_at is empty or null.
    $stmt = $pdo->prepare("
        SELECT quest_id, description, reward, expires_at, cheese_config 
        FROM tbl_quests 
        WHERE type = 'cheese_hunt'
          AND is_active = 1
          AND (
              expires_at IS NULL
              OR expires_at = ''
              OR datetime(expires_at) > datetime('now')
          )
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $quest = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($quest) {
        // Parse cheese config if it exists
        $cheese_config = null;
        if ($quest['cheese_config']) {
            $cheese_config = json_decode($quest['cheese_config'], true);
        }

        echo json_encode([
            'success' => true,
            'quest' => [
                'quest_id' => (int)$quest['quest_id'],
                'description' => $quest['description'],
                'reward' => (int)$quest['reward'],
                'expires_at' => $quest['expires_at'],
                'cheese_config' => $cheese_config
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'quest' => null
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
