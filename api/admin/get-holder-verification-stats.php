<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$dbPath = '/var/www/html/db/narrrf_world.sqlite';

try {
    // Connect to database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get holder verification statistics
    $stats = [];

    // Total verifications
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_holder_verifications");
    $stmt->execute();
    $stats['total_verifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Successful verifications (role granted)
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_holder_verifications WHERE role_granted = 1");
    $stmt->execute();
    $stats['successful_verifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total NFTs
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_nft_ownership");
    $stmt->execute();
    $stats['total_nfts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Active roles (unique users with roles)
    $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM tbl_role_grants WHERE revoked_at IS NULL");
    $stmt->execute();
    $stats['active_roles'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Recent verifications (last 24 hours)
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tbl_holder_verifications WHERE verified_at >= datetime('now', '-1 day')");
    $stmt->execute();
    $stats['recent_verifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Collection breakdown
    $stmt = $db->prepare("SELECT collection, COUNT(*) as count FROM tbl_holder_verifications GROUP BY collection");
    $stmt->execute();
    $stats['collection_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?> 