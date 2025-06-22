<?php
// 🔐 Secure Upload Endpoint (POST only)
$secret = $_GET['secret'] ?? '';
if ($secret !== 'MyUltraSecretKey123') {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

if (!isset($_FILES['sqlite'])) {
    http_response_code(400);
    echo "Missing file";
    exit;
}

// === CHANGED: Save to /data/narrrf_world.sqlite (Render persistent storage) ===
$uploadPath = '/data/narrrf_world.sqlite';

// Optionally, backup the old file before overwrite (uncomment if you want backups)
// if (file_exists($uploadPath)) {
//     rename($uploadPath, $uploadPath . '.bak_' . date('Ymd_His'));
// }

if (move_uploaded_file($_FILES['sqlite']['tmp_name'], $uploadPath)) {
    echo "✅ DB uploaded successfully to /data/";
} else {
    http_response_code(500);
    echo "❌ Failed to save DB to /data/";
}
