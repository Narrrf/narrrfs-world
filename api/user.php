<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
  'username' => $_SESSION['username'] ?? 'Guest',
  'discriminator' => $_SESSION['discriminator'] ?? '',
  'avatarUrl' => $_SESSION['avatar_url'] ?? '',
  'email' => $_SESSION['email'] ?? '',
  'guilds' => $_SESSION['guilds'] ?? []
]);
