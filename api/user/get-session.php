<?php
session_start();

header('Content-Type: application/json');

echo json_encode([
  'success' => true,
  'discord_id' => $_SESSION['discord_id'] ?? '',
  'discord_username' => $_SESSION['discord_username'] ?? ''
]);