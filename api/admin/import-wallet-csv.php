<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../config/database.php';

try {
    $pdo = getPDOConnection();
    
    // Create wallet transactions table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tbl_wallet_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            txhash TEXT NOT NULL,
            block_time_unix INTEGER,
            block_time TEXT,
            fee_sol DECIMAL(10,9),
            token_account TEXT,
            change_type TEXT,
            change_amount DECIMAL(20,0),
            pre_balance DECIMAL(20,0),
            post_balance DECIMAL(20,0),
            token_address TEXT,
            token_decimals INTEGER,
            token_multiplier DECIMAL(10,1),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create wallet balance history table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tbl_wallet_balance_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp TEXT,
            balance_lamports DECIMAL(20,0),
            balance_sol DECIMAL(15,9),
            transaction_count INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Import CSV data
        $csvFile = __DIR__ . '/../../private/SOL-Community-wallet.csv';
        
        if (!file_exists($csvFile)) {
            throw new Exception('CSV file not found');
        }
        
        // Clear existing data
        $pdo->exec('DELETE FROM tbl_wallet_transactions');
        $pdo->exec('DELETE FROM tbl_wallet_balance_history');
        
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            throw new Exception('Cannot open CSV file');
        }
        
        // Skip header row
        fgetcsv($handle);
        
        $transactionCount = 0;
        $latestBalance = 0;
        $latestTimestamp = '';
        
        $stmt = $pdo->prepare("
            INSERT INTO tbl_wallet_transactions 
            (txhash, block_time_unix, block_time, fee_sol, token_account, change_type, change_amount, pre_balance, post_balance, token_address, token_decimals, token_multiplier)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 12) {
                $stmt->execute([
                    trim($data[0]), // txhash
                    (int)$data[1],  // block_time_unix
                    $data[2],       // block_time
                    (float)$data[3], // fee_sol
                    trim($data[4]), // token_account
                    trim($data[5]), // change_type
                    (float)$data[6], // change_amount
                    (float)$data[7], // pre_balance
                    (float)$data[8], // post_balance
                    trim($data[9]), // token_address
                    (int)$data[10],  // token_decimals
                    (float)$data[11] // token_multiplier
                ]);
                
                $transactionCount++;
                
                // Track latest balance and timestamp
                if ($data[8] > $latestBalance) {
                    $latestBalance = (float)$data[8];
                    $latestTimestamp = $data[2];
                }
            }
        }
        
        fclose($handle);
        
        // Calculate current balance in SOL
        $currentBalanceSol = $latestBalance / 1000000000; // Convert lamports to SOL
        
        // Insert current balance into history
        $balanceStmt = $pdo->prepare("
            INSERT INTO tbl_wallet_balance_history (timestamp, balance_lamports, balance_sol, transaction_count)
            VALUES (?, ?, ?, ?)
        ");
        $balanceStmt->execute([$latestTimestamp, $latestBalance, $currentBalanceSol, $transactionCount]);
        
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
        
        echo json_encode([
            'success' => true,
            'message' => 'Wallet CSV imported successfully',
            'data' => [
                'current_balance' => [
                    'lamports' => $latestBalance,
                    'sol' => round($currentBalanceSol, 9),
                    'timestamp' => $latestTimestamp
                ],
                'transactions' => [
                    'total' => $transactionCount,
                    'incoming' => $summary['total_incoming'] / 1000000000,
                    'outgoing' => $summary['total_outgoing'] / 1000000000,
                    'fees' => $summary['total_fees']
                ],
                'import_time' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } else {
        // GET request - return current wallet status
        $balanceStmt = $pdo->prepare("
            SELECT * FROM tbl_wallet_balance_history 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $balanceStmt->execute();
        $currentBalance = $balanceStmt->fetch(PDO::FETCH_ASSOC);
        
        $transactionStmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM tbl_wallet_transactions
        ");
        $transactionStmt->execute();
        $transactionCount = $transactionStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($currentBalance) {
            echo json_encode([
                'success' => true,
                'wallet_status' => 'imported',
                'current_balance' => [
                    'sol' => $currentBalance['balance_sol'],
                    'lamports' => $currentBalance['balance_lamports'],
                    'timestamp' => $currentBalance['timestamp']
                ],
                'transactions' => $transactionCount,
                'last_import' => $currentBalance['created_at']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'wallet_status' => 'not_imported',
                'message' => 'No wallet data imported yet. Use POST to import CSV.'
            ]);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
