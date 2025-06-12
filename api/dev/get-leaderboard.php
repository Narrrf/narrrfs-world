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

  // Fetch top 10 Tetris scores
  $tetrisStmt = $db->prepare("
    SELECT wallet, MAX(score) as score, discord_id, discord_name, MIN(timestamp) as timestamp
    FROM tbl_tetris_scores
    WHERE game = 'tetris'
    GROUP BY wallet, discord_id, discord_name
    ORDER BY score DESC, timestamp ASC
    LIMIT 10
  ");
  $tetrisStmt->execute();
  $tetrisRows = $tetrisStmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch top 10 Snake scores
  $snakeStmt = $db->prepare("
    SELECT wallet, MAX(score) as score, discord_id, discord_name, MIN(timestamp) as timestamp
    FROM tbl_tetris_scores
    WHERE game = 'snake'
    GROUP BY wallet, discord_id, discord_name
    ORDER BY score DESC, timestamp ASC
    LIMIT 10
  ");
  $snakeStmt->execute();
  $snakeRows = $snakeStmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'tetris' => $tetrisRows,
    'snake' => $snakeRows
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Leaderboard error: ' . $e->getMessage()
  ]);
}
