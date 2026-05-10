<?php
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

narrrfs_touch_session();

echo json_encode([
  'success' => true,
  'discord_id' => $_SESSION['discord_id'] ?? '',
  'discord_username' => $_SESSION['discord_username'] ?? ($_SESSION['user']['username'] ?? ''),
  'last_seen_at' => $_SESSION['narrrfs_last_seen_at'] ?? null
]);