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

    define('NARRRFS_SESSION_READ_ONLY', true);
    require_once __DIR__ . '/../config/admin-session.php';

    $authenticated = narrrfs_session_has_trusted_discord_oauth_provenance($_SESSION);
    $playerId = $authenticated ? (string)$_SESSION['discord_id'] : null;

    $db = null;
    try {
        require_once __DIR__ . '/../config/database.php';
        $databasePath = getDatabasePath();
        if (!is_file($databasePath) || !is_readable($databasePath)) {
            throw new RuntimeException('Database unavailable.');
        }

        $db = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $db->busyTimeout(5000);
        $db->exec('PRAGMA query_only = ON');

        $hasMissions = (int)$db->querySingle(
            "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'tbl_owner_cheese_hunt_missions'"
        ) === 1;

        $requiredEggs = 3;
        $activeMission = null;
        if ($hasMissions) {
            $m = $db->querySingle(
                "SELECT mission_id, required_clicks
                 FROM tbl_owner_cheese_hunt_missions
                 WHERE status = 'active'
                   AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
                 ORDER BY mission_id DESC
                 LIMIT 1",
                true
            );
            if (is_array($m)) {
                $activeMission = $m;
                $requiredEggs = (int)$m['required_clicks'];
            }
        }

        if (!$authenticated || $playerId === null) {
            echo json_encode([
                'success' => true,
                'authenticated' => false,
                'total_clicks' => 0,
                'quest_clicks' => 0,
                'required_eggs' => $requiredEggs,
                'quest_completed' => false,
                'claim_status' => null,
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }

        $totalClicks = 0;
        if ($hasMissions) {
            $stmt = $db->prepare('SELECT COALESCE(SUM(click_count), 0) AS c FROM tbl_owner_cheese_hunt_progress WHERE player_id = :player_id');
            $stmt->bindValue(':player_id', $playerId, SQLITE3_TEXT);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            $totalClicks += (int)($row['c'] ?? 0);
        }

        $hasLegacyClicks = (int)$db->querySingle(
            "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'tbl_cheese_clicks'"
        ) === 1;
        if ($hasLegacyClicks) {
            $stmt = $db->prepare('SELECT COUNT(*) AS c FROM tbl_cheese_clicks WHERE user_wallet = :player_id');
            $stmt->bindValue(':player_id', $playerId, SQLITE3_TEXT);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            $totalClicks += (int)($row['c'] ?? 0);
        }

        $questClicks = 0;
        $questCompleted = false;
        $claimStatus = null;

        if ($activeMission !== null) {
            $missionId = (int)$activeMission['mission_id'];

            $pStmt = $db->prepare(
                'SELECT click_count, completed_at
                 FROM tbl_owner_cheese_hunt_progress
                 WHERE mission_id = :mission_id AND player_id = :player_id'
            );
            $pStmt->bindValue(':mission_id', $missionId, SQLITE3_INTEGER);
            $pStmt->bindValue(':player_id', $playerId, SQLITE3_TEXT);
            $pRow = $pStmt->execute()->fetchArray(SQLITE3_ASSOC);

            if (is_array($pRow)) {
                $questClicks = (int)$pRow['click_count'];
                $questCompleted = $pRow['completed_at'] !== null;
            }

            $cStmt = $db->prepare(
                'SELECT status
                 FROM tbl_owner_cheese_hunt_claims
                 WHERE mission_id = :mission_id AND player_id = :player_id'
            );
            $cStmt->bindValue(':mission_id', $missionId, SQLITE3_INTEGER);
            $cStmt->bindValue(':player_id', $playerId, SQLITE3_TEXT);
            $cRow = $cStmt->execute()->fetchArray(SQLITE3_ASSOC);

            if (is_array($cRow)) {
                $claimStatus = (string)$cRow['status'];
                $questCompleted = true;
            }
        }

        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'total_clicks' => $totalClicks,
            'quest_clicks' => $questClicks,
            'required_eggs' => $requiredEggs,
            'quest_completed' => $questCompleted,
            'claim_status' => $claimStatus,
        ], JSON_UNESCAPED_SLASHES);
    } catch (Throwable) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'stats_unavailable',
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
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDatabaseConnection();

    // Get user_id from query parameter
    $userId = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;
    
    if (!$userId || $userId === '' || $userId === 'null' || $userId === 'undefined') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing or invalid user_id parameter'
        ]);
        exit;
    }

    // Get total cheese hunt clicks (all time, not just quest)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_clicks FROM tbl_cheese_clicks WHERE user_wallet = ?");
    $stmt->execute([$userId]);
    $totalClicks = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_clicks'];

    // Get active cheese hunt quest to determine quest progress
    $stmt = $pdo->prepare("
        SELECT quest_id, description, reward, expires_at, cheese_config 
        FROM tbl_quests 
        WHERE type = 'cheese_hunt' AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $quest = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'total_clicks' => $totalClicks,
        'quest_clicks' => 0,
        'required_eggs' => 3, // Default fallback
        'quest_completed' => false
    ];

    if ($quest) {
        // Parse cheese config if it exists
        $cheese_config = null;
        if ($quest['cheese_config']) {
            $cheese_config = json_decode($quest['cheese_config'], true);
        }

        // CRITICAL: Always use actual cheese_count from quest config (not hardcoded or old values)
        $requiredEggs = $cheese_config['cheese_count'] ?? 3;
        $response['required_eggs'] = $requiredEggs; // Always set from actual quest config

        // Check if user has already claimed this quest
        $stmt = $pdo->prepare("SELECT * FROM tbl_quest_claims WHERE quest_id = ? AND user_id = ?");
        $stmt->execute([$quest['quest_id'], $userId]);
        $existing_claim = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_claim) {
            // Quest already completed by this user
            $response['quest_completed'] = true;
            $response['quest_clicks'] = $requiredEggs; // Show required eggs as clicks when completed
        } else {
            // Count how many different cheese eggs this user has clicked for this quest
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT egg_id) as unique_eggs 
                                   FROM tbl_cheese_clicks 
                                   WHERE quest_id = ? AND user_wallet = ?");
            $stmt->execute([$quest['quest_id'], $userId]);
            $egg_count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['unique_eggs'];

            $response['quest_clicks'] = $egg_count;
            $response['quest_completed'] = ($egg_count >= $requiredEggs);
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Cheese hunt stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
