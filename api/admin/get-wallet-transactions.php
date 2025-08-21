<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../config/database.php';

try {
    $pdo = getPDOConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get query parameters
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        // Validate parameters
        if ($limit > 10000) $limit = 10000; // Max 10,000 records
        if ($offset < 0) $offset = 0;
        
        // Get wallet transactions
        $stmt = $pdo->prepare("
            SELECT 
                txhash,
                block_time,
                change_type,
                change_amount,
                pre_balance,
                post_balance,
                fee_sol,
                token_account,
                token_address,
                created_at
            FROM tbl_wallet_transactions 
            ORDER BY block_time_unix DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM tbl_wallet_transactions");
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Format amounts for response (convert lamports to SOL)
        foreach ($transactions as &$tx) {
            if (isset($tx['change_amount'])) {
                $tx['amount_sol'] = round($tx['change_amount'] / 1000000000, 9);
            }
            if (isset($tx['pre_balance'])) {
                $tx['pre_balance_sol'] = round($tx['pre_balance'] / 1000000000, 9);
            }
            if (isset($tx['post_balance'])) {
                $tx['post_balance_sol'] = round($tx['post_balance'] / 1000000000, 9);
            }
        }
        
        echo json_encode([
            'success' => true,
            'transactions' => $transactions,
            'total_count' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'message' => 'Wallet transactions retrieved successfully'
        ]);
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
