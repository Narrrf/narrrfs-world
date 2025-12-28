<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Local development fallback
$isLocalDevelopment = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                      strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

$userId = $_GET['user_id'] ?? '';
$game = $_GET['game'] ?? '';

// For local development, use Narrrf's account if no user_id provided
if (!$userId && $isLocalDevelopment) {
    $userId = $LOCAL_TEST_DISCORD_ID;
}

if (empty($userId) || empty($game)) {
    echo json_encode([
        'success' => false,
        'error' => 'user_id and game are required'
    ]);
    exit;
}

try {
    $db = getSQLite3Connection();

    // Ensure settings table exists
    $db->exec('
        CREATE TABLE IF NOT EXISTS tbl_user_store_settings (
            user_id TEXT NOT NULL,
            game TEXT NOT NULL,
            setting_key TEXT NOT NULL,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, game, setting_key)
        )
    ');

    $stmt = $db->prepare('
        SELECT setting_key, setting_value
        FROM tbl_user_store_settings
        WHERE user_id = ? AND game = ?
    ');
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $stmt->bindValue(2, $game, SQLITE3_TEXT);
    $result = $stmt->execute();

    $settings = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}


