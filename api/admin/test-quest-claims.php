<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Use centralized database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getPDOConnection();
    
    // Test basic table access
    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%quest%'");
    $stmt->execute();
    $quest_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check quest_claims table structure
    $stmt = $pdo->prepare("PRAGMA table_info(tbl_quest_claims)");
    $stmt->execute();
    $quest_claims_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check quests table structure
    $stmt = $pdo->prepare("PRAGMA table_info(tbl_quests)");
    $stmt->execute();
    $quests_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count records in each table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_quest_claims");
    $stmt->execute();
    $quest_claims_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tbl_quests");
    $stmt->execute();
    $quests_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get a sample of quest claims
    $stmt = $pdo->prepare("SELECT * FROM tbl_quest_claims LIMIT 5");
    $stmt->execute();
    $sample_claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get a sample of quests
    $stmt = $pdo->prepare("SELECT * FROM tbl_quests LIMIT 5");
    $stmt->execute();
    $sample_quests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Quest claims table test completed',
        'timestamp' => date('Y-m-d H:i:s'),
        'quest_tables' => $quest_tables,
        'quest_claims_structure' => $quest_claims_structure,
        'quests_structure' => $quests_structure,
        'counts' => [
            'quest_claims' => (int)$quest_claims_count,
            'quests' => (int)$quests_count
        ],
        'sample_claims' => $sample_claims,
        'sample_quests' => $sample_quests
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Test failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
