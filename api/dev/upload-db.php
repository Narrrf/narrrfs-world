<?php
// ⚠️ TEMPORARY UPLOAD ENDPOINT — REMOVE AFTER USE
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Method Not Allowed";
  exit;
}

$target = '/data/narrrf_world.sqlite';

if (!isset($_FILES['dbfile']) || $_FILES['dbfile']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo "Upload failed.";
  exit;
}

if (move_uploaded_file($_FILES['dbfile']['tmp_name'], $target)) {
  echo "✅ Uploaded successfully to $target";
} else {
  http_response_code(500);
  echo "❌ Failed to move uploaded file.";
}
