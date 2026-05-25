<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $dir = '../slider-motion/';
    $videos = [];

    if (!is_dir($dir)) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'videos' => [],
            'count' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit();
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
            'title' => 'Motion Art protocol unlocked.',
            'caption' => 'The hidden Samuzi Phase 3 motion preview is now active.'
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