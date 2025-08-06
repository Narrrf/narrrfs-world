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

    // Note: tbl_users doesn't have a wallet column, so we can't link NFT ownership to Discord users directly
    // User info would need to be stored in tbl_nft_ownership or a separate linking table

    echo json_encode([
        'success' => true,
        'nfts' => $nfts,
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