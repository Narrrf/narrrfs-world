<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Admin authentication check
require_once '../auth/auth.php';
if (!checkAdminAuthentication()) {
    exit; 
}

// Database configuration - Environment aware
$dbPath = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false))
    ? __DIR__ . '/../../db/narrrf_world.sqlite'  // Local development
    : (file_exists(__DIR__ . '/../../db/narrrf_world.sqlite') 
        ? __DIR__ . '/../../db/narrrf_world.sqlite'  // Local fallback
        : '/data/narrrf_world.sqlite');              // Render production

try {
    // Connect to database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create NFT collections table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS tbl_nft_collections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price_sol DECIMAL(10,6) DEFAULT 0,
            nft_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active',
            collection_id TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $db->exec($createTableSQL);

    // Get collection ID from query parameter
    $collectionId = $_GET['id'] ?? null;
    
    if (!$collectionId) {
        throw new Exception('Collection ID is required');
    }

    // Get specific collection
    $stmt = $db->prepare("
        SELECT 
            id,
            name,
            description,
            price_sol,
            nft_count,
            status,
            collection_id,
            created_at,
            updated_at
        FROM tbl_nft_collections 
        WHERE id = ?
    ");
    $stmt->execute([$collectionId]);
    $collection = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$collection) {
        throw new Exception('Collection not found');
    }

    echo json_encode([
        'success' => true,
        'collection' => $collection
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load NFT collection: ' . $e->getMessage()
    ]);
}
?>
