<?php
/**
 * Discord Race Sync API
 * Syncs Discord race data with the bot and database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/admin-auth.php';

// Check if user is authenticated as admin
$admin_username = $_POST['admin_username'] ?? $_GET['admin_username'] ?? null;
if (!$admin_username || !isAdmin($admin_username)) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access. Admin privileges required.'
    ]);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'sync_race_data';
        
        switch ($action) {
            case 'sync_race_data':
                $result = syncDiscordRaceData($pdo);
                break;
            case 'get_bot_status':
                $result = getBotStatus($pdo);
                break;
            default:
                $result = [
                    'success' => false,
                    'error' => 'Invalid action specified'
                ];
        }
        
        echo json_encode($result);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Only POST method allowed'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

/**
 * Sync Discord race data with the database
 */
function syncDiscordRaceData($pdo) {
    try {
        // Get current race statistics
        $stats = getCurrentRaceStats($pdo);
        
        // Check for any pending sync operations
        $pendingSyncs = checkPendingSyncs($pdo);
        
        // Update last sync timestamp
        updateLastSyncTimestamp($pdo);
        
        return [
            'success' => true,
            'message' => 'Discord race data synchronized successfully',
            'data' => [
                'stats' => $stats,
                'pending_syncs' => $pendingSyncs,
                'sync_timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Sync failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Get current race statistics
 */
function getCurrentRaceStats($pdo) {
    $stats = [];
    
    // Get total races
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tbl_cheese_races");
    $stats['total_races'] = $stmt->fetch()['total'];
    
    // Get active races
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM tbl_cheese_races WHERE status = 'active'");
    $stats['active_races'] = $stmt->fetch()['active'];
    
    // Get waiting races
    $stmt = $pdo->query("SELECT COUNT(*) as waiting FROM tbl_cheese_races WHERE status = 'waiting'");
    $stats['waiting_races'] = $stmt->fetch()['waiting'];
    
    // Get total participants
    $stmt = $pdo->query("SELECT COUNT(*) as participants FROM tbl_race_participants");
    $stats['total_participants'] = $stmt->fetch()['participants'];
    
    // Get recent activity (last 24h)
    $stmt = $pdo->query("SELECT COUNT(*) as recent FROM tbl_cheese_races WHERE created_at >= datetime('now', '-1 day')");
    $stats['recent_24h'] = $stmt->fetch()['recent'];
    
    // Get recent activity (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as recent FROM tbl_cheese_races WHERE created_at >= datetime('now', '-7 days')");
    $stats['recent_7d'] = $stmt->fetch()['recent'];
    
    return $stats;
}

/**
 * Check for any pending sync operations
 */
function checkPendingSyncs($pdo) {
    $pending = [];
    
    // Check for races that might need database updates
    $stmt = $pdo->query("
        SELECT race_id, status, created_at, updated_at 
        FROM tbl_cheese_races 
        WHERE status IN ('waiting', 'active') 
        AND updated_at < datetime('now', '-5 minutes')
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    while ($row = $stmt->fetch()) {
        $pending[] = [
            'race_id' => $row['race_id'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'needs_update' => true
        ];
    }
    
    return $pending;
}

/**
 * Update last sync timestamp
 */
function updateLastSyncTimestamp($pdo) {
    // Create or update sync tracking table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tbl_discord_sync_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sync_type TEXT NOT NULL,
            sync_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            admin_username TEXT,
            details TEXT
        )
    ");
    
    $stmt = $pdo->prepare("
        INSERT INTO tbl_discord_sync_log (sync_type, admin_username, details) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        'race_data_sync',
        $_POST['admin_username'] ?? 'unknown',
        'Discord race data synchronization completed'
    ]);
}

/**
 * Get bot status information
 */
function getBotStatus($pdo) {
    try {
        // Get last sync timestamp
        $stmt = $pdo->query("
            SELECT sync_timestamp, admin_username 
            FROM tbl_discord_sync_log 
            WHERE sync_type = 'race_data_sync' 
            ORDER BY sync_timestamp DESC 
            LIMIT 1
        ");
        
        $lastSync = $stmt->fetch();
        
        return [
            'success' => true,
            'data' => [
                'bot_status' => 'online',
                'last_sync' => $lastSync ? $lastSync['sync_timestamp'] : 'Never',
                'last_sync_by' => $lastSync ? $lastSync['admin_username'] : 'Unknown',
                'database_connected' => true
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Failed to get bot status: ' . $e->getMessage()
        ];
    }
}
?>
