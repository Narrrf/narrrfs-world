<?php
// Admin authentication functions
function checkAdminAuthentication() {
    // 🔧 CRITICAL FIX: Local development bypass (SAFE - only for localhost)
    $isLocalDevelopment = $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1';
    if ($isLocalDevelopment) {
        // Skip authentication for local development only
        return true;
    }
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is authenticated
    if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
        return true;
    }
    
    // If not authenticated, return error response
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Admin access required'
    ]);
    return false;
}

function isAdminOrMod() {
    if (!isset($_SESSION['discord_id'])) {
        return false;
    }

    $dbPath = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false))
        ? __DIR__ . '/../../db/narrrf_world.sqlite'  // Local development
        : (file_exists(__DIR__ . '/../../db/narrrf_world.sqlite') 
            ? __DIR__ . '/../../db/narrrf_world.sqlite'  // Local fallback
            : '/data/narrrf_world.sqlite');              // Render production

    try {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check user roles - only allow Admin (Founder) and Moderator
        $stmt = $db->prepare("
            SELECT role_name 
            FROM tbl_user_roles 
            WHERE user_id = ? 
            AND role_name IN ('Founder', 'Moderator')
        ");
        $stmt->execute([$_SESSION['discord_id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return !empty($roles);
    } catch (Exception $e) {
        error_log("Admin verification error: " . $e->getMessage());
        return false;
    }
}
?>
