<?php
// 🧠 Cheese Architect API — Switch Active Season
header('Content-Type: application/json');

// Use the correct database path
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $newSeason = $input['new_season'] ?? '';
    $adminId = $input['admin_id'] ?? '';
    
    if (empty($newSeason)) {
        throw new Exception('New season is required');
    }
    
    if (empty($adminId)) {
        throw new Exception('Admin ID is required');
    }
    
    // Validate season format
    if (!in_array($newSeason, ['season_1', 'season_2'])) {
        throw new Exception('Invalid season format. Must be season_1 or season_2');
    }
    
    // Update the seasons table to mark the new season as active
    $updateStmt = $db->prepare("
        UPDATE tbl_seasons 
        SET is_active = CASE 
            WHEN season_name = ? THEN 1 
            ELSE 0 
        END
    ");
    $updateStmt->execute([$newSeason]);
    
    // Update season settings to use the new season as default
    $updateSettingsStmt = $db->prepare("
        UPDATE tbl_season_settings 
        SET is_active = CASE 
            WHEN season_name = ? THEN 1 
            ELSE 0 
        END
    ");
    $updateSettingsStmt->execute([$newSeason]);
    
    // Log the season switch
    $logStmt = $db->prepare("
        INSERT INTO tbl_role_grants (user_id, username, role_id, role_name, reason, granted_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $adminId,
        'Admin',
        'season_switch',
        'Season Switch',
        "Switched active season to {$newSeason}",
        $adminId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully switched to {$newSeason}",
        'new_season' => $newSeason,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
