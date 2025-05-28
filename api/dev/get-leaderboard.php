<?php
header('Content-Type: application/json');

try {
  $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
  if (!file_exists($dbPath)) {
    throw new Exception("Database not found");
  }

  $db = new PDO("sqlite:$dbPath");
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $stmt = $db->prepare("
    SELECT wallet, score, discord_id, discord_name, timestamp
    FROM tbl_tetris_scores
    ORDER BY score DESC, timestamp ASC
    LIMIT 10
  ");
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'leaderboard' => $results
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Leaderboard error: ' . $e->getMessage()
  ]);
}
