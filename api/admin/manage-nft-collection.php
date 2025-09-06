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

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'add':
            // Add new collection
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');
            $priceSol = floatval($input['price_sol'] ?? 0);
            $nftCount = intval($input['nft_count'] ?? 0);
            $status = $input['status'] ?? 'active';
            
            if (empty($name)) {
                throw new Exception('Collection name is required');
            }
            
            $stmt = $db->prepare("
                INSERT INTO tbl_nft_collections (name, description, price_sol, nft_count, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $priceSol, $nftCount, $status]);
            
            $collectionId = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Collection added successfully',
                'collection_id' => $collectionId
            ]);
            break;
            
        case 'edit':
            // Edit existing collection
            $id = intval($input['id'] ?? 0);
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');
            $priceSol = floatval($input['price_sol'] ?? 0);
            $nftCount = intval($input['nft_count'] ?? 0);
            $status = $input['status'] ?? 'active';
            
            if (!$id) {
                throw new Exception('Collection ID is required');
            }
            
            if (empty($name)) {
                throw new Exception('Collection name is required');
            }
            
            $stmt = $db->prepare("
                UPDATE tbl_nft_collections 
                SET name = ?, description = ?, price_sol = ?, nft_count = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $priceSol, $nftCount, $status, $id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Collection not found or no changes made');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Collection updated successfully'
            ]);
            break;
            
        case 'delete':
            // Delete collection
            $id = intval($input['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('Collection ID is required');
            }
            
            $stmt = $db->prepare("DELETE FROM tbl_nft_collections WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Collection not found');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Collection deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action. Use add, edit, or delete');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
