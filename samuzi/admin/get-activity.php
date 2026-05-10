<?php
/**
 * Samuzi Admin - Get Recent Activity
 * Returns recent admin activities and website changes.
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
    $activities = getRecentActivity();

    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading activity: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function getRecentActivity(): array
{
    $activities = [];

    $activities = array_merge($activities, readActivityLog('../api/admin_log.txt', 'admin'));
    $activities = array_merge($activities, readActivityLog('../api/email_log.txt', 'email'));

    usort($activities, function ($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    return array_slice($activities, 0, 10);
}

function readActivityLog(string $file, string $type): array
{
    if (!file_exists($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    $recent_lines = array_slice(array_reverse($lines), 0, 10);
    $activities = [];

    foreach ($recent_lines as $line) {
        if (!preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) - (.+)/', $line, $matches)) {
            continue;
        }

        $time = $matches[1];
        $message = sanitizeActivityMessage($matches[2], $type);

        $activities[] = [
            'time' => $time,
            'message' => $message,
            'type' => $type
        ];
    }

    return $activities;
}

function sanitizeActivityMessage(string $message, string $type): string
{
    // Remove old template/customer wording from legacy logs before displaying them.
    $replacements = [
        'Pool Website' => 'Samuzi',
        'Pool Admin' => 'Samuzi Admin',
        'Pool-Fotos' => 'Slider artwork',
        'Pool photos' => 'Slider artwork',
        'Photo uploaded' => 'Slider artwork uploaded',
        'Photo deleted' => 'Slider artwork deleted',
        'E-Mail erhalten' => 'Email received',
        'pooladmin' => 'admin'
    ];

    $message = str_replace(array_keys($replacements), array_values($replacements), $message);

    if ($type === 'email' && stripos($message, 'Email received') !== 0) {
        $message = 'Email received: ' . $message;
    }

    return $message;
}
?>