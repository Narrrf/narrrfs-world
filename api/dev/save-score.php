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

echo json_encode(['success' => true, 'message' => "Score saved for $game"]);
?>
