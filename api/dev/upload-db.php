<?php
// 🔒 Secure DB Upload with Secret Key
$secret = $_GET['secret'] ?? '';
if ($secret !== 'MyUltraSecretKey123') {
  http_response_code(403);
  exit('❌ Forbidden');
}

// ✅ Allow only POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Method Not Allowed";
  exit;
}

$target = '/data/narrrf_world.sqlite';

// ✅ Check uploaded file
if (!isset($_FILES['dbfile']) || $_FILES['dbfile']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo "❌ Upload failed.";
  exit;
}

// 🚚 Move uploaded file
if (move_uploaded_file($_FILES['dbfile']['tmp_name'], $target)) {
  echo "✅ Uploaded successfully to $target";
} else {
  http_response_code(500);
  echo "❌ Failed to move uploaded file.";
}
?>
