<?php
session_start();
header("Content-Type: application/json");

// Database configuration - Use production path for live server
$db_path = "/var/www/html/db/narrrf_world.sqlite";
if (!file_exists($db_path)) {
    // Fallback to local path for development
    $db_path = __DIR__ . "/../db/narrrf_world.sqlite";
}

try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid JSON input"
    ]);
    exit;
}

// Extract data
$egg_id = $input["egg_id"] ?? null;
$quest_id = $input["quest_id"] ?? null;

// Get user ID from session (matches database column name)
$user_wallet = $_SESSION["discord_id"] ?? null;

if (!$user_wallet) {
    echo json_encode([
        "success" => false,
        "error" => "User not authenticated"
    ]);
    exit;
}

if (!$egg_id) {
    echo json_encode([
        "success" => false,
        "error" => "Egg ID is required"
    ]);
    exit;
}

try {
    // Insert cheese click into database (matches actual schema)
    $stmt = $db->prepare("
        INSERT INTO tbl_cheese_clicks (user_wallet, egg_id, quest_id)
        VALUES (?, ?, ?)
    ");
    
    $stmt->bindValue(1, $user_wallet, SQLITE3_TEXT);
    $stmt->bindValue(2, $egg_id, SQLITE3_TEXT);
    $stmt->bindValue(3, $quest_id, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    if ($result) {
        $click_id = $db->lastInsertRowID();
        
        echo json_encode([
            "success" => true,
            "message" => "Cheese click tracked successfully",
            "click_id" => $click_id,
            "egg_id" => $egg_id,
            "user_wallet" => $user_wallet
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "Failed to track cheese click"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>
