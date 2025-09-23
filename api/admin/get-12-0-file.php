<?php
/**
 * 12.0 File Access API
 * Secure access to 12.0 folder system files
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is authenticated (basic check for local development)
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

try {
    // Get the requested path
    $requestedPath = $_GET['path'] ?? '';
    
    if (empty($requestedPath)) {
        throw new Exception('No path specified');
    }
    
    // Security: Ensure path is within 12.0 directory
    $basePath = realpath(__DIR__ . '/../../12.0/');
    $fullPath = realpath($basePath . '/' . $requestedPath);
    
    // Debug logging for both local and production
    error_log("=== 12.0 File API Debug ===");
    error_log("Requested path: " . $requestedPath);
    error_log("Base path: " . $basePath);
    error_log("Full path: " . $fullPath);
    error_log("Base path normalized: " . str_replace('\\', '/', $basePath));
    error_log("Full path normalized: " . str_replace('\\', '/', $fullPath));
    error_log("Path exists: " . (file_exists($fullPath) ? 'YES' : 'NO'));
    error_log("Is directory: " . (is_dir($fullPath) ? 'YES' : 'NO'));
    error_log("strpos result: " . strpos(str_replace('\\', '/', $fullPath), str_replace('\\', '/', $basePath)));
    error_log("==========================");
    
    // More robust path validation - normalize paths for comparison
    $basePathNormalized = str_replace('\\', '/', $basePath);
    $fullPathNormalized = str_replace('\\', '/', $fullPath);
    
    // Enhanced path validation with better error reporting
    if (!$basePath) {
        throw new Exception('Base 12.0 directory not found at: ' . __DIR__ . '/../../12.0/');
    }
    
    if (!$fullPath) {
        throw new Exception('Requested path does not exist: ' . $requestedPath . ' (Base: ' . $basePath . ')');
    }
    
    if (strpos($fullPathNormalized, $basePathNormalized) !== 0) {
        throw new Exception('Invalid path - access denied. Requested: ' . $requestedPath . ' (Full: ' . $fullPathNormalized . ', Base: ' . $basePathNormalized . ')');
    }
    
    // Check if file/directory exists
    if (!file_exists($fullPath)) {
        throw new Exception('File or directory not found');
    }
    
    // If it's a directory, return directory listing
    if (is_dir($fullPath)) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            // Handle both Windows and Unix path separators
            $filePath = str_replace('\\', '/', $file->getPathname());
            $basePathNormalized = str_replace('\\', '/', $fullPath);
            $relativePath = str_replace($basePathNormalized . '/', '', $filePath);
            
            // For files, we need the full path from the 12.0 root
            $fullRelativePath = $requestedPath . '/' . $relativePath;
            
            $files[] = [
                'name' => $file->getFilename(),
                'path' => $fullRelativePath,
                'type' => $file->isDir() ? 'directory' : 'file',
                'size' => $file->isFile() ? $file->getSize() : 0,
                'modified' => date('Y-m-d H:i:s', $file->getMTime())
            ];
        }
        
        echo json_encode([
            'success' => true,
            'type' => 'directory',
            'path' => $requestedPath,
            'files' => $files
        ]);
    } else {
        // It's a file, return file content
        $content = file_get_contents($fullPath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        
        echo json_encode([
            'success' => true,
            'type' => 'file',
            'path' => $requestedPath,
            'content' => $content,
            'extension' => $extension,
            'size' => filesize($fullPath),
            'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
