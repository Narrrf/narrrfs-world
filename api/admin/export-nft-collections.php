<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="nft-collections-' . date('Y-m-d') . '.csv"');

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

    // Get all collections
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
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output CSV headers
    echo "ID,Name,Description,Price (SOL),NFT Count,Total Value (SOL),Status,Collection ID,Created At,Updated At\n";

    // Output data
    foreach ($collections as $collection) {
        $totalValue = ($collection['nft_count'] ?? 0) * ($collection['price_sol'] ?? 0);
        
        echo sprintf(
            "%d,\"%s\",\"%s\",%.6f,%d,%.6f,\"%s\",\"%s\",\"%s\",\"%s\"\n",
            $collection['id'],
            str_replace('"', '""', $collection['name']),
            str_replace('"', '""', $collection['description'] ?? ''),
            $collection['price_sol'] ?? 0,
            $collection['nft_count'] ?? 0,
            $totalValue,
            $collection['status'] ?? 'active',
            $collection['collection_id'] ?? '',
            $collection['created_at'],
            $collection['updated_at']
        );
    }

} catch (Exception $e) {
    // If there's an error, output it as CSV
    echo "Error,Message\n";
    echo "1,\"Failed to export collections: " . str_replace('"', '""', $e->getMessage()) . "\"\n";
}
?>
