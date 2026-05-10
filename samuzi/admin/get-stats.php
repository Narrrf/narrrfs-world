<?php
/**
 * Samuzi Admin - Get Statistics
 * Returns website statistics for the admin dashboard.
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

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
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
    $email_stats = getEmailStats();
    $website_stats = getWebsiteStats();

    ob_clean();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'email_stats' => $email_stats,
        'website_stats' => $website_stats,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading statistics: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

function getEmailStats(): array
{
    $stats = [
        'total' => 0,
        'today' => 0,
        'week' => 0,
        'month' => 0
    ];

    // Legacy optional file. It may not exist in the clean Samuzi setup.
    $email_log_file = '../api/email_log.txt';

    if (!file_exists($email_log_file)) {
        return $stats;
    }

    $lines = file($email_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $stats;
    }

    $stats['total'] = count($lines);

    $today = date('Y-m-d');
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $month_ago = date('Y-m-d', strtotime('-30 days'));

    foreach ($lines as $line) {
        if (!preg_match('/(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
            continue;
        }

        $line_date = $matches[1];

        if ($line_date === $today) {
            $stats['today']++;
        }

        if ($line_date >= $week_ago) {
            $stats['week']++;
        }

        if ($line_date >= $month_ago) {
            $stats['month']++;
        }
    }

    return $stats;
}

function getWebsiteStats(): array
{
    return [
        'pages' => countPublicPages(),
        'projects' => countProjects(),
        'slider_photos' => countImagesInDirectory('../slider-photos/'),
        'project_images' => countProjectImages(),
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

function countPublicPages(): int
{
    $pages = [
        '../index.html',
        '../projects.html',
        '../about.html',
        '../contact.html',
        '../legal.html'
    ];

    $count = 0;

    foreach ($pages as $page) {
        if (file_exists($page)) {
            $count++;
        }
    }

    return $count;
}

function countProjects(): int
{
    $projects_file = 'projects.json';

    if (!file_exists($projects_file)) {
        return 0;
    }

    $projects = json_decode(file_get_contents($projects_file), true);

    return is_array($projects) ? count($projects) : 0;
}

function countProjectImages(): int
{
    $projects_file = 'projects.json';

    if (!file_exists($projects_file)) {
        return 0;
    }

    $projects = json_decode(file_get_contents($projects_file), true);
    if (!is_array($projects)) {
        return 0;
    }

    $count = 0;

    foreach ($projects as $project) {
        if (isset($project['photos']) && is_array($project['photos'])) {
            $count += count($project['photos']);
        }
    }

    return $count;
}

function countImagesInDirectory(string $directory): int
{
    if (!is_dir($directory)) {
        return 0;
    }

    $photo_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $files = scandir($directory);

    if (!is_array($files)) {
        return 0;
    }

    $count = 0;

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (in_array($extension, $photo_extensions, true)) {
            $count++;
        }
    }

    return $count;
}
?>