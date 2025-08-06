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
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $wallet = $input['wallet'] ?? '';
    $collection = $input['collection'] ?? '';

    if (empty($wallet)) {
        echo json_encode([
            'success' => false,
            'error' => 'Wallet address is required'
        ]);
        exit;
    }

    // Connect to database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build query
    $query = "SELECT * FROM tbl_nft_ownership WHERE wallet = ?";
    $params = [$wallet];

    if (!empty($collection)) {
        $query .= " AND collection = ?";
        $params[] = $collection;
    }

    $query .= " ORDER BY acquired_at DESC";

    // Execute query
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $nfts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user info if available
    $userInfo = null;
    $userStmt = $db->prepare("SELECT discord_id, discord_name FROM tbl_users WHERE wallet = ?");
    $userStmt->execute([$wallet]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userInfo = [
            'discord_id' => $user['discord_id'],
            'discord_name' => $user['discord_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'nfts' => $nfts,
        'user_info' => $userInfo,
        'count' => count($nfts)
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