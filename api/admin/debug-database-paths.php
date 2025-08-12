<?php
// Debug script to see exactly what database paths are being detected
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo "=== DATABASE PATH DEBUG ===\n\n";

// Show current working directory
echo "Current working directory: " . getcwd() . "\n";
echo "Script location: " . __FILE__ . "\n";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "\n";
echo "PHP OS: " . PHP_OS_FAMILY . "\n\n";

// Check various possible database paths
$possible_paths = [
    'Current dir + ../db/narrrf_world.sqlite' => dirname(__DIR__) . '/db/narrrf_world.sqlite',
    'Current dir + ../../db/narrrf_world.sqlite' => dirname(dirname(__DIR__)) . '/db/narrrf_world.sqlite',
    'Document root + /db/narrrf_world.sqlite' => ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/db/narrrf_world.sqlite',
    'Document root + /narrrfs-world/db/narrrf_world.sqlite' => ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/narrrfs-world/db/narrrf_world.sqlite',
    'Absolute path /var/www/html/db/narrrf_world.sqlite' => '/var/www/html/db/narrrf_world.sqlite',
    'Absolute path /data/narrrf_world.sqlite' => '/data/narrrf_world.sqlite'
];

echo "=== CHECKING POSSIBLE PATHS ===\n";
foreach ($possible_paths as $description => $path) {
    $exists = file_exists($path) ? 'EXISTS' : 'NOT FOUND';
    $readable = is_readable($path) ? 'READABLE' : 'NOT READABLE';
    $size = file_exists($path) ? filesize($path) . ' bytes' : 'N/A';
    echo "$description: $path\n";
    echo "  Status: $exists | $readable | Size: $size\n\n";
}

// Check if we're in production or local
$is_local = PHP_OS_FAMILY === 'Windows' || strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false;
echo "Environment detection: " . ($is_local ? 'LOCAL' : 'PRODUCTION') . "\n";

// Check current user and permissions
echo "Current user: " . get_current_user() . "\n";
if (function_exists('posix_getpwuid')) {
    echo "PHP user: " . (posix_getpwuid(posix_geteuid())['name'] ?? 'Unknown') . "\n";
}
echo "Web server user: " . ($_SERVER['USER'] ?? 'Unknown') . "\n";

// Check if we can write to /data
if (is_dir('/data')) {
    echo "/data directory exists and is " . (is_writable('/data') ? 'writable' : 'NOT writable') . "\n";
} else {
    echo "/data directory does not exist\n";
}

// Check if we can write to current directory
echo "Current directory is " . (is_writable('.') ? 'writable' : 'NOT writable') . "\n";
?>
