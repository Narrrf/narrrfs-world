CREATE TABLE IF NOT EXISTS tbl_tetris_scores (
  id INTEGER PRIMARY KEY,
  wallet TEXT,
  score INTEGER,
  discord_id TEXT,
  discord_name TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
