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

  // Get current season
  $seasonStmt = $db->prepare("SELECT MAX(CAST(SUBSTR(season, 8) AS INTEGER)) as max_season FROM tbl_tetris_scores WHERE season LIKE 'season_%' AND season NOT LIKE '%_historical'");
  $seasonStmt->execute();
  $current_season_result = $seasonStmt->fetch(PDO::FETCH_ASSOC);
  $current_season = $current_season_result['max_season'] ?? 1;
  $current_season_name = "season_$current_season";

  // Fetch top 10 Tetris scores for CURRENT SEASON only, one per user (discord_id)
  $tetrisStmt = $db->prepare("
    SELECT
      discord_id,
      discord_name,
      MAX(score) AS score,
      MIN(timestamp) AS timestamp
    FROM tbl_tetris_scores
    WHERE game = 'tetris' AND season = ? AND is_current_season = 1
    GROUP BY discord_id, discord_name
    ORDER BY score DESC, timestamp ASC
    LIMIT 10
  ");
  $tetrisStmt->execute([$current_season_name]);
  $tetrisRows = $tetrisStmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch top 10 Snake scores for CURRENT SEASON only, one per user (discord_id)
  $snakeStmt = $db->prepare("
    SELECT
      discord_id,
      discord_name,
      MAX(score) AS score,
      MIN(timestamp) AS timestamp
    FROM tbl_tetris_scores
    WHERE game = 'snake' AND season = ? AND is_current_season = 1
    GROUP BY discord_id, discord_name
    ORDER BY score DESC, timestamp ASC
    LIMIT 10
  ");
  $snakeStmt->execute([$current_season_name]);
  $snakeRows = $snakeStmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'current_season' => $current_season_name,
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
?>
