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

$uploadPath = __DIR__ . '/../../db/narrrf_world.sqlite';
if (move_uploaded_file($_FILES['sqlite']['tmp_name'], $uploadPath)) {
    echo "✅ DB uploaded successfully";
} else {
    http_response_code(500);
    echo "❌ Failed to save DB";
}
