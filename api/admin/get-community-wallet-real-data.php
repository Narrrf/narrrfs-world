<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $pdo = getPDOConnection();
    
    // Get current wallet balance from imported data
    $balanceStmt = $pdo->prepare("
        SELECT * FROM tbl_wallet_balance_history 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $balanceStmt->execute();
    $currentBalance = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentBalance) {
        // No imported data yet
        echo json_encode([
            'success' => true,
            'wallet' => '62DpHkt3h7r6CJECJtRQUoMnm3SJxUTzF5kNUGJs2325',
            'balance' => [
                'sol' => 0.0,
                'lamports' => 0,
                'status' => 'no_data'
            ],
            'nfts' => [
                'total' => 0,
                'list' => [],
                'collections' => [],
                'status' => 'no_data'
            ],
            'transactions' => [
                'total' => 0,
                'recent' => []
            ],
            'timestamp' => date('c'),
            'method' => 'csv_import',
            'note' => 'No wallet data imported yet. Import CSV to see real balance.'
        ]);
        exit;
    }
    
                    // Get all transactions (not limited to 20)
                $transactionStmt = $pdo->prepare("
                    SELECT 
                        txhash,
                        block_time,
                        change_type,
                        change_amount,
                        pre_balance,
                        post_balance,
                        fee_sol
                    FROM tbl_wallet_transactions 
                    ORDER BY block_time_unix DESC
                ");
    $transactionStmt->execute();
    $recentTransactions = $transactionStmt->execute() ? $transactionStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Get transaction summary
    $summaryStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN change_type = 'inc' THEN change_amount ELSE 0 END) as total_incoming,
            SUM(CASE WHEN change_type = 'dec' THEN change_amount ELSE 0 END) as total_outgoing,
            SUM(fee_sol) as total_fees
        FROM tbl_wallet_transactions
    ");
    $summaryStmt->execute();
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    // Format recent transactions
    $formattedTransactions = [];
    foreach ($recentTransactions as $tx) {
        $formattedTransactions[] = [
            'hash' => $tx['txhash'],
            'time' => $tx['block_time'],
            'type' => $tx['change_type'],
            'amount_sol' => round($tx['change_amount'] / 1000000000, 9),
            'fee_sol' => $tx['fee_sol'],
            'pre_balance_sol' => round($tx['pre_balance'] / 1000000000, 9),
            'post_balance_sol' => round($tx['post_balance'] / 1000000000, 9)
        ];
    }
    
    // Calculate 24h change
    $dayAgo = time() - (24 * 60 * 60);
    $dayAgoStmt = $pdo->prepare("
        SELECT post_balance FROM tbl_wallet_transactions 
        WHERE block_time_unix <= ? 
        ORDER BY block_time_unix DESC 
        LIMIT 1
    ");
    $dayAgoStmt->execute([$dayAgo]);
    $dayAgoBalance = $dayAgoStmt->fetch(PDO::FETCH_ASSOC);
    
    $dayChange = 0;
    if ($dayAgoBalance) {
        $dayChange = $currentBalance['balance_lamports'] - $dayAgoBalance['post_balance'];
    }
    
    echo json_encode([
        'success' => true,
        'wallet' => '62DpHkt3h7r6CJECJtRQUoMnm3SJxUTzF5kNUGJs2325',
        'balance' => [
            'sol' => $currentBalance['balance_sol'],
            'lamports' => $currentBalance['balance_lamports'],
            'status' => 'active',
            '24h_change' => round($dayChange / 1000000000, 9),
            'last_updated' => $currentBalance['timestamp']
        ],
        'nfts' => [
            'total' => 0,
            'list' => [],
            'collections' => [],
            'status' => 'not_tracked',
            'note' => 'NFT tracking not available in CSV data'
        ],
        'transactions' => [
            'total' => $summary['total_transactions'],
            'recent' => $formattedTransactions,
            'summary' => [
                'total_incoming_sol' => round($summary['total_incoming'] / 1000000000, 9),
                'total_outgoing_sol' => round($summary['total_outgoing'] / 1000000000, 9),
                'total_fees_sol' => $summary['total_fees']
            ]
        ],
        'timestamp' => date('c'),
        'method' => 'csv_import',
        'note' => 'Real-time data from Solscan CSV import',
        'import_time' => $currentBalance['created_at']
    ]);
    
} catch (Exception $e) {
    error_log("Community wallet real data error: " . $e->getMessage());
    
    // Return fallback data on error
    echo json_encode([
        'success' => true,
        'wallet' => '62DpHkt3h7r6CJECJtRQUoMnm3SJxUTzF5kNUGJs2325',
        'balance' => [
            'sol' => 0.0,
            'lamports' => 0,
            'status' => 'error_fallback'
        ],
        'nfts' => [
            'total' => 0,
            'list' => [],
            'collections' => [],
            'status' => 'error_fallback'
        ],
        'transactions' => [
            'total' => 0,
            'recent' => []
        ],
        'timestamp' => date('c'),
        'method' => 'error_fallback',
        'note' => 'Error loading wallet data'
    ]);
}
?>
