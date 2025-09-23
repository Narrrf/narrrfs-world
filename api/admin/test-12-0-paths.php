<?php
/**
 * Test script to check 12.0 directory paths on Render
 */

header('Content-Type: application/json');

echo "=== 12.0 Path Test ===\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";

// Test different possible paths
$possiblePaths = [
    __DIR__ . '/../../12.0/',
    $_SERVER['DOCUMENT_ROOT'] . '/12.0/',
    '/var/www/html/12.0/',
    dirname(__DIR__, 2) . '/12.0/'
];

echo "\n=== Testing Possible 12.0 Paths ===\n";
foreach ($possiblePaths as $path) {
    $realPath = realpath($path);
    echo "Path: $path\n";
    echo "Real path: " . ($realPath ?: 'NOT FOUND') . "\n";
    echo "Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
    echo "Is dir: " . (is_dir($path) ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

// Test specific file
$testFile = 'ACTIVE_STATUS/QUICK_STATUS_12.0.md';
echo "\n=== Testing Specific File ===\n";
foreach ($possiblePaths as $basePath) {
    $fullPath = $basePath . $testFile;
    $realPath = realpath($fullPath);
    echo "Base: $basePath\n";
    echo "Full: $fullPath\n";
    echo "Real: " . ($realPath ?: 'NOT FOUND') . "\n";
    echo "Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

// List directory contents
echo "\n=== Directory Contents ===\n";
$docRoot = $_SERVER['DOCUMENT_ROOT'];
echo "Document root contents:\n";
if (is_dir($docRoot)) {
    $contents = scandir($docRoot);
    foreach ($contents as $item) {
        if ($item !== '.' && $item !== '..') {
            echo "- $item\n";
        }
    }
}
?>
