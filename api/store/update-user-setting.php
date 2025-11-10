<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = $input['user_id'] ?? '';
$game = $input['game'] ?? '';
$settingKey = $input['setting_key'] ?? '';
$settingValue = $input['setting_value'] ?? '';

if (empty($userId) || empty($game) || empty($settingKey)) {
    echo json_encode([
        'success' => false,
        'error' => 'user_id, game, and setting_key are required'
    ]);
    exit;
}

try {
    $db = getSQLite3Connection();

    $db->exec('BEGIN TRANSACTION');

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
        INSERT INTO tbl_user_store_settings (user_id, game, setting_key, setting_value, updated_at)
        VALUES (?, ?, ?, ?, datetime("now"))
        ON CONFLICT(user_id, game, setting_key) DO UPDATE SET
            setting_value = excluded.setting_value,
            updated_at = datetime("now")
    ');
    $stmt->bindValue(1, $userId, SQLITE3_TEXT);
    $stmt->bindValue(2, $game, SQLITE3_TEXT);
    $stmt->bindValue(3, $settingKey, SQLITE3_TEXT);
    $stmt->bindValue(4, $settingValue, SQLITE3_TEXT);
    $stmt->execute();

    $db->exec('COMMIT');

    echo json_encode([
        'success' => true,
        'message' => 'Setting saved'
    ]);
} catch (Exception $e) {
    if (isset($db)) {
        $db->exec('ROLLBACK');
    }

    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}


