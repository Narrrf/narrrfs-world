<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get all community collections ordered by name
    $stmt = $pdo->prepare("
        SELECT id, collection_name, collection_symbol, total_supply, floor_price_sol, total_volume_sol, description, created_at, updated_at 
        FROM tbl_community_collections 
        ORDER BY collection_name ASC
    ");
    $stmt->execute();
    
    $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get NFT count for each collection
    foreach ($collections as &$collection) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as nft_count FROM tbl_community_nfts WHERE collection_id = ?");
        $stmt->execute([$collection['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $collection['nft_count'] = intval($result['nft_count']);
        
        // Calculate total value
        $stmt = $pdo->prepare("SELECT SUM(current_value_sol) as total_value FROM tbl_community_nfts WHERE collection_id = ?");
        $stmt->execute([$collection['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $collection['total_value_sol'] = floatval($result['total_value'] ?? 0);
    }
    
    echo json_encode([
        'success' => true,
        'collections' => $collections,
        'total_collections' => count($collections)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
