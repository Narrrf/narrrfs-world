<?php
/**
 * 🔒 SET ADMIN SESSION FOR PARTNER PORTAL ACCESS
 * 
 * Sets $_SESSION['admin_logged_in'] = true for authenticated admin users
 * This fixes the Partner Portal "Admin access required" issue
 * 
 * Created: October 29, 2025
 */

// Start session
session_start();

header('Content-Type: application/json');

try {
    // Get Discord ID from request
    $data = json_decode(file_get_contents('php://input'), true);
    $discordId = $data['discord_id'] ?? null;
    $discordName = $data['discord_name'] ?? null;
    
    // Verify this is narrrf (owner) or admin role
    if ($discordName === 'narrrf' || $discordId === '328601656659017732') {
        // Set admin session flag
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['discord_id'] = $discordId;
        $_SESSION['discord_name'] = $discordName;
        
        error_log("✅ Admin session set for: $discordName ($discordId)");
        
        echo json_encode([
            'success' => true,
            'message' => 'Admin session set successfully',
            'admin_logged_in' => $_SESSION['admin_logged_in'],
            'discord_id' => $_SESSION['discord_id'],
            'discord_name' => $_SESSION['discord_name']
        ]);
    } else {
        error_log("❌ Unauthorized admin session attempt: $discordName ($discordId)");
        
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
    }
    
} catch (Exception $e) {
    error_log("❌ Admin session error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

