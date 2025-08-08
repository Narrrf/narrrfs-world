<?php
$source = "/var/www/html/db/narrrf_world.sqlite";

// �� Secret token check - Use environment variable
$secret = $_GET['secret'] ?? '';
$expected_secret = getenv('DB_DOWNLOAD_SECRET') ?: 'MyUltraSecretKey123'; // Fallback for development

if ($secret !== $expected_secret) {
  http_response_code(403);
  exit('❌ Forbidden');
}

// ✅ Check if DB exists
if (!file_exists($source)) {
  http_response_code(404);
  exit('❌ Database not found.');
}

// 📦 Serve file for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="narrrf_world.sqlite"');
readfile($source);
exit;
?>
