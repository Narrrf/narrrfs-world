<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    ob_end_flush();
    exit();
}

try {
    $dir = '../slider-motion/';
    $videos = [];

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($extension !== 'mp4') {
            continue;
        }

        $path = $dir . $file;

        $videos[] = [
            'filename' => $file,
            'name' => pathinfo($file, PATHINFO_FILENAME),
            'extension' => $extension,
            'size' => filesize($path),
            'date' => date('Y-m-d H:i:s', filemtime($path)),
            'url' => '../slider-motion/' . $file
        ];
    }

    usort($videos, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    ob_clean();
    echo json_encode([
        'success' => true,
        'videos' => $videos,
        'count' => count($videos),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading motion videos: ' . $e->getMessage(),
        'videos' => []
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}