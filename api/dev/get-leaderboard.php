<?php
header('Content-Type: application/json');

try {
  $dbPath = file_exists('/data/narrrf_world.sqlite')
    ? '/data/narrrf_world.sqlite'
    : __DIR__ . '/../../db/narrrf_world.sqlite';
  if (!file_exists($dbPath)) {
    throw new Exception("Database not found");
  }

  $db = new PDO("sqlite:$dbPath");
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "
  SELECT 
    wallet, 
    MAX(score) as score,
    discord_id,
    discord_name,
    MIN(timestamp) as timestamp
  FROM tbl_tetris_scores
  GROUP BY wallet, discord_id, discord_name
  ORDER BY score DESC, timestamp ASC
  LIMIT 10
";

  $stmt = $db->prepare($query);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'leaderboard' => $rows ?? []
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'leaderboard' => [],
    'error' => 'Leaderboard error: ' . $e->getMessage()
  ]);
}
