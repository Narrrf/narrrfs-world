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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    ob_end_flush();
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $filename = basename($input['filename'] ?? '');

    if ($filename === '') {
        throw new Exception('Missing filename');
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($extension !== 'mp4') {
        throw new Exception('Only MP4 motion videos can be deleted here');
    }

    $path = '../slider-motion/' . $filename;

    if (!file_exists($path)) {
        throw new Exception('Motion video not found');
    }

    unlink($path);

    $log_entry = date('Y-m-d H:i:s') . " - Motion video deleted: " . $filename . " by " . ($_SESSION['admin_username'] ?? 'admin') . "\n";
    file_put_contents('../api/admin_log.txt', $log_entry, FILE_APPEND | LOCK_EX);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Motion video deleted successfully'
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}