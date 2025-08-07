<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dbPath = '/var/www/html/db/narrrf_world.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = [];
    $missingTables = [];
    
    // Check if required tables exist
    $requiredTables = [
        'tbl_holder_verifications' => [
            'id INTEGER PRIMARY KEY AUTOINCREMENT',
            'user_id TEXT NOT NULL',
            'username TEXT',
            'wallet TEXT NOT NULL',
            'collection TEXT NOT NULL',
            'nft_count INTEGER DEFAULT 0',
            'role_granted INTEGER DEFAULT 0',
            'verified_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'signature TEXT',
            'message TEXT',
            'status TEXT DEFAULT "pending"'
        ],
        'tbl_nft_ownership' => [
            'id INTEGER PRIMARY KEY AUTOINCREMENT',
            'wallet TEXT NOT NULL',
            'collection TEXT NOT NULL',
            'nft_count INTEGER DEFAULT 0',
            'verified_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'last_checked DATETIME DEFAULT CURRENT_TIMESTAMP'
        ],
        'tbl_role_grants' => [
            'id INTEGER PRIMARY KEY AUTOINCREMENT',
            'user_id TEXT NOT NULL',
            'username TEXT',
            'role_id TEXT NOT NULL',
            'role_name TEXT NOT NULL',
            'granted_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'reason TEXT DEFAULT "system"',
            'granted_by TEXT DEFAULT "system"'
        ]
    ];
    
    foreach ($requiredTables as $tableName => $columns) {
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$tableName]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $tables[$tableName] = 'EXISTS';
        } else {
            $tables[$tableName] = 'MISSING';
            $missingTables[] = $tableName;
        }
    }
    
    // Create missing tables
    $createdTables = [];
    foreach ($missingTables as $tableName) {
        $columns = $requiredTables[$tableName];
        $createSQL = "CREATE TABLE $tableName (" . implode(', ', $columns) . ")";
        
        $db->exec($createSQL);
        $createdTables[] = $tableName;
        $tables[$tableName] = 'CREATED';
    }
    
    // Create indexes for better performance
    $indexes = [
        'idx_holder_verifications_user' => 'CREATE INDEX IF NOT EXISTS idx_holder_verifications_user ON tbl_holder_verifications(user_id)',
        'idx_holder_verifications_wallet' => 'CREATE INDEX IF NOT EXISTS idx_holder_verifications_wallet ON tbl_holder_verifications(wallet)',
        'idx_holder_verifications_collection' => 'CREATE INDEX IF NOT EXISTS idx_holder_verifications_collection ON tbl_holder_verifications(collection)',
        'idx_holder_verifications_verified_at' => 'CREATE INDEX IF NOT EXISTS idx_holder_verifications_verified_at ON tbl_holder_verifications(verified_at DESC)',
        'idx_nft_ownership_wallet' => 'CREATE INDEX IF NOT EXISTS idx_nft_ownership_wallet ON tbl_nft_ownership(wallet)',
        'idx_nft_ownership_collection' => 'CREATE INDEX IF NOT EXISTS idx_nft_ownership_collection ON tbl_nft_ownership(collection)',
        'idx_role_grants_user' => 'CREATE INDEX IF NOT EXISTS idx_role_grants_user ON tbl_role_grants(user_id)',
        'idx_role_grants_role' => 'CREATE INDEX IF NOT EXISTS idx_role_grants_role ON tbl_role_grants(role_id)',
        'idx_role_grants_granted_at' => 'CREATE INDEX IF NOT EXISTS idx_role_grants_granted_at ON tbl_role_grants(granted_at DESC)'
    ];
    
    $createdIndexes = [];
    foreach ($indexes as $indexName => $indexSQL) {
        try {
            $db->exec($indexSQL);
            $createdIndexes[] = $indexName;
        } catch (Exception $e) {
            // Index might already exist
        }
    }
    
    echo json_encode([
        'success' => true,
        'tables' => $tables,
        'created_tables' => $createdTables,
        'created_indexes' => $createdIndexes,
        'message' => count($missingTables) > 0 ? 'Missing tables created successfully' : 'All required tables exist'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
