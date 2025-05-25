<?php
// Path to SQLite DB
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);

// Decode JSON POST data
$data = json_decode(file_get_contents('php://input'), true);

// Required fields
$wallet = $data['wallet'] ?? null;
$score = $data['score'] ?? null;
$discord_id = $data['discord_id'] ?? null;
$discord_name = $data['discord_name'] ?? null;

// Basic validation
if (!$wallet || !$score) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing wallet or score']);
    exit;
}

// Insert into DB
$stmt = $db->prepare("INSERT INTO tbl_tetris_scores (wallet, score, discord_id, discord_name) VALUES (?, ?, ?, ?)");
$stmt->execute([$wallet, $score, $discord_id, $discord_name]);

echo json_encode(['success' => true, 'message' => 'Score saved']);
?>
