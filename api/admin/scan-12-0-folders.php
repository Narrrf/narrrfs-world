<?php
// Disable error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check if user is authenticated
session_start();

// For local development, allow access without authentication
$isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);

// Check for multiple authentication methods (admin interface, 12.0 system, profile page)
$isAuthenticated = false;

if ($isLocal) {
    $isAuthenticated = true; // Local bypass
} else {
    // Check admin interface authentication
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        $isAuthenticated = true;
    }
    
    // Check 12.0 system authentication (discord_id from profile page)
    if (!$isAuthenticated && isset($_SESSION['discord_id']) && !empty($_SESSION['discord_id'])) {
        $isAuthenticated = true;
    }
    
    // Check for Bearer token in headers (for API calls)
    if (!$isAuthenticated && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (strpos($authHeader, 'Bearer ') === 0) {
            $isAuthenticated = true; // Assume valid for now
        }
    }
}

if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit();
}

// Get database connection
function getSQLite3Connection() {
    $dbPath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../db/narrrf_world.sqlite' 
        : '/var/www/html/db/narrrf_world.sqlite';
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

// Function to scan directory recursively
function scanDirectory($dir, $basePath = '') {
    $items = [];
    
    if (!is_dir($dir)) {
        return $items;
    }
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $fullPath = $dir . '/' . $file;
        $relativePath = $basePath ? $basePath . '/' . $file : $file;
        
        if (is_dir($fullPath)) {
            // Recursively scan subdirectories
            $subItems = scanDirectory($fullPath, $relativePath);
            $items = array_merge($items, $subItems);
        } else {
            // Add file
            $items[] = [
                'name' => $file,
                'path' => $relativePath,
                'type' => 'file',
                'size' => filesize($fullPath),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
        }
    }
    
    return $items;
}

// Function to get folder structure
function getFolderStructure($baseDir) {
    $structure = [];
    
    if (!is_dir($baseDir)) {
        return $structure;
    }
    
    $files = scandir($baseDir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $fullPath = $baseDir . '/' . $file;
        
        if (is_dir($fullPath)) {
            $structure[$file] = [
                'type' => 'folder',
                'path' => $file,
                'children' => getFolderStructure($fullPath)
            ];
        } else {
            $structure[$file] = [
                'type' => 'file',
                'path' => $file,
                'size' => filesize($fullPath),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
        }
    }
    
    return $structure;
}

try {
    // Determine base path
    $basePath = $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false 
        ? '../../12.0' 
        : '/var/www/html/12.0';
    
    if (!is_dir($basePath)) {
        throw new Exception("12.0 directory not found at: " . $basePath);
    }
    
    // Scan different categories
    $categories = [
        'active_status' => 'ACTIVE_STATUS',
        'lab_notes' => 'LAB_NOTES',
        'llm_sync' => 'LLM_SYNC_SYSTEM',
        'technical_docs' => 'TECHNICAL_DOCUMENTATION',
        'milestones' => 'MILESTONE_DOCUMENTATION',
        'deployment' => 'DEPLOYMENT_HISTORY',
        'dev_tools' => 'DEVELOPMENT_TOOLS',
        'archive' => 'ARCHIVE'
    ];
    
    $scanResults = [];
    
    foreach ($categories as $key => $folderName) {
        $folderPath = $basePath . '/' . $folderName;
        
        if (is_dir($folderPath)) {
            $scanResults[$key] = [
                'folder_name' => $folderName,
                'folder_path' => $folderName,
                'exists' => true,
                'structure' => getFolderStructure($folderPath),
                'files' => scanDirectory($folderPath, $folderName)
            ];
        } else {
            $scanResults[$key] = [
                'folder_name' => $folderName,
                'folder_path' => $folderName,
                'exists' => false,
                'structure' => [],
                'files' => []
            ];
        }
    }
    
    // Get recent files (last 30 days) with priority for today's date
    $recentFiles = [];
    $allFiles = [];
    $today = date('Y-m-d');
    
    foreach ($scanResults as $category => $data) {
        if ($data['exists']) {
            foreach ($data['files'] as $file) {
                $allFiles[] = array_merge($file, ['category' => $category]);
                
                // Check if file is recent (last 30 days)
                $fileTime = strtotime($file['modified']);
                $thirtyDaysAgo = strtotime('-30 days');
                
                if ($fileTime > $thirtyDaysAgo) {
                    $fileWithCategory = array_merge($file, ['category' => $category]);
                    
                    // Prioritize files from today
                    if (strpos($file['name'], $today) !== false) {
                        $fileWithCategory['priority'] = 1; // Highest priority
                    } else {
                        $fileWithCategory['priority'] = 2; // Normal priority
                    }
                    
                    $recentFiles[] = $fileWithCategory;
                }
            }
        }
    }
    
    // Sort recent files by priority (today's files first) then by modification date
    usort($recentFiles, function($a, $b) {
        // First sort by priority (1 = today's files, 2 = other recent files)
        if ($a['priority'] !== $b['priority']) {
            return $a['priority'] - $b['priority'];
        }
        // Then sort by modification date (newest first)
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
    
    // Sort all files by modification date
    usort($allFiles, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
    
    echo json_encode([
        'success' => true,
        'scan_results' => $scanResults,
        'recent_files' => array_slice($recentFiles, 0, 20), // Top 20 recent files
        'all_files' => array_slice($allFiles, 0, 100), // Top 100 files
        'total_files' => count($allFiles),
        'scan_timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'scan_results' => [],
        'recent_files' => [],
        'all_files' => [],
        'total_files' => 0
    ]);
}
?>
