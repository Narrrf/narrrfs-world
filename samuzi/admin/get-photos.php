<?php
/**
 * Samuzi Admin - Get Slider Artwork
 * Returns the list of slider artwork images for the admin dashboard.
 */

// Suppress all output and warnings to ensure clean JSON response
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

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit();
}

try {
    $photos = getSliderArtworkList();

    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'photos' => $photos,
        'count' => count($photos),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading slider artwork: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function getSliderArtworkList(): array
{
    $photos = [];
    $slider_dir = '../slider-photos/';

    if (!is_dir($slider_dir)) {
        return $photos;
    }

    $photo_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $files = scandir($slider_dir);

    if (!is_array($files)) {
        return $photos;
    }

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        // Skip logo or system files.
        if (strpos(strtolower($file), 'logo') !== false) {
            continue;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!in_array($extension, $photo_extensions, true)) {
            continue;
        }

        $file_path = $slider_dir . $file;
        $file_size = is_file($file_path) ? filesize($file_path) : 0;
        $file_date = is_file($file_path) ? filemtime($file_path) : time();

        $photos[] = [
            'filename' => $file,
            'name' => pathinfo($file, PATHINFO_FILENAME),
            'extension' => $extension,
            'size' => $file_size,
            'size_formatted' => formatFileSize($file_size),
            'date' => date('Y-m-d H:i:s', $file_date),
            'is_samuzi_art' => true
        ];
    }

    usort($photos, function ($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    return $photos;
}

function formatFileSize(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }

    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' bytes';
}
?>