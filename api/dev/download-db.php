<?php
$source = "/var/www/html/db/narrrf_world.sqlite";

if (!file_exists($source)) {
    http_response_code(404);
    echo "❌ Database not found.";
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="narrrf_world.sqlite"');
readfile($source);
