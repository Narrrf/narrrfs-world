<?php
/**
 * Centralized Database Configuration
 * Uses environment variables for secure database access
 */

// Database configuration using environment variables
function getDatabasePath() {
    // Check if we're running locally (XAMPP) or on production (Render)
    // Use a more reliable method to detect local environment
    $is_local = PHP_OS_FAMILY === 'Windows' || strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false;
    
    if ($is_local) {
        // Local development - use relative path from API directory
        $api_dir = __DIR__; // Current directory (api/config)
        $db_path = dirname(dirname($api_dir)) . '/db/narrrf_world.sqlite'; // Go up from api/config to project root, then to db
        
        // Debug logging for local development
        error_log("Local development detected. Database path: " . $db_path);
        error_log("API directory: " . $api_dir);
        error_log("File exists: " . (file_exists($db_path) ? 'YES' : 'NO'));
        error_log("PHP OS: " . PHP_OS_FAMILY);
        error_log("Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET'));
    } else {
        // Production - use environment variable or default
        $db_path = getenv('DB_PATH') ?: '/data/narrrf_world.sqlite';
        error_log("Production environment detected. Database path: " . $db_path);
    }
    
    // Validate path for security
    if (strpos($db_path, '..') !== false || strpos($db_path, '//') !== false) {
        error_log('Invalid database path detected: ' . $db_path);
        throw new Exception('Invalid database path');
    }
    
    return $db_path;
}

// Get PDO connection with error handling
function getDatabaseConnection() {
    $db_path = getDatabasePath();
    
    if (!file_exists($db_path)) {
        error_log('Database not found at: ' . $db_path);
        throw new Exception('Database not found');
    }
    
    try {
        $pdo = new PDO("sqlite:$db_path");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new Exception('Database connection failed');
    }
}

// Get SQLite3 connection (for backward compatibility)
function getSQLite3Connection() {
    $db_path = getDatabasePath();
    
    if (!file_exists($db_path)) {
        error_log('Database not found at: ' . $db_path);
        throw new Exception('Database not found');
    }
    
    try {
        $db = new SQLite3($db_path);
        return $db;
    } catch (Exception $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new Exception('Database connection failed');
    }
}

// Validate database path for security
function validateDatabasePath($path) {
    // Check for directory traversal attempts
    if (strpos($path, '..') !== false || strpos($path, '//') !== false) {
        return false;
    }
    
    // Check if path is within allowed directory
    $real_path = realpath($path);
    $allowed_dir = realpath('/data');
    
    if ($real_path === false || $allowed_dir === false) {
        return false;
    }
    
    return strpos($real_path, $allowed_dir) === 0;
}
?>
