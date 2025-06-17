<?php
function isAdminOrMod() {
    if (!isset($_SESSION['discord_id'])) {
        return false;
    }

    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
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