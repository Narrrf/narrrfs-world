<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../config/database.php';

try {
    $pdo = getPDOConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Validate required fields
        $txhash = $input['txhash'] ?? null;
        $type = $input['type'] ?? null;
        $amount = $input['amount'] ?? null;
        $balance = $input['balance'] ?? null;
        $fee = $input['fee'] ?? null;
        
        if (!$txhash || !$type || $amount === null || $balance === null || $fee === null) {
            throw new Exception('Missing required fields: txhash, type, amount, balance, fee');
        }
        
        // Validate type
        if (!in_array($type, ['inc', 'dec'])) {
            throw new Exception('Invalid type. Must be "inc" or "dec"');
        }
        
        // Validate amounts
        if (!is_numeric($amount) || $amount < 0) {
            throw new Exception('Amount must be a non-negative number');
        }
        if (!is_numeric($balance) || $balance < 0) {
            throw new Exception('Balance must be a non-negative number');
        }
        if (!is_numeric($fee) || $fee < 0) {
            throw new Exception('Fee must be a non-negative number');
        }
        
        // Check if transaction exists
        $checkStmt = $pdo->prepare("SELECT id FROM tbl_wallet_transactions WHERE txhash = ?");
        $checkStmt->execute([$txhash]);
        
        if (!$checkStmt->fetch()) {
            throw new Exception('Wallet transaction not found');
        }
        
        // Convert SOL amounts to lamports for storage
        $amountLamports = round($amount * 1000000000);
        $balanceLamports = round($balance * 1000000000);
        
        // Update the transaction
        $updateStmt = $pdo->prepare("
            UPDATE tbl_wallet_transactions 
            SET change_type = ?, change_amount = ?, post_balance = ?, fee_sol = ?
            WHERE txhash = ?
        ");
        
        $result = $updateStmt->execute([$type, $amountLamports, $balanceLamports, $fee, $txhash]);
        
        if ($result) {
            // Update the balance history if this is the latest transaction
            $latestStmt = $pdo->prepare("
                SELECT MAX(block_time_unix) as latest_time 
                FROM tbl_wallet_transactions
            ");
            $latestStmt->execute();
            $latestTime = $latestStmt->fetch(PDO::FETCH_ASSOC)['latest_time'];
            
            $currentStmt = $pdo->prepare("
                SELECT block_time_unix FROM tbl_wallet_transactions WHERE txhash = ?
            ");
            $currentStmt->execute([$txhash]);
            $currentTime = $currentStmt->fetch(PDO::FETCH_ASSOC)['block_time_unix'];
            
            if ($currentTime == $latestTime) {
                // This is the latest transaction, update balance history
                $balanceHistoryStmt = $pdo->prepare("
                    UPDATE tbl_wallet_balance_history 
                    SET balance_lamports = ?, balance_sol = ?
                    WHERE id = (SELECT MAX(id) FROM tbl_wallet_balance_history)
                ");
                $balanceHistoryStmt->execute([$balanceLamports, $balance]);
            }
            
            // Get updated transaction for response
            $getStmt = $pdo->prepare("
                SELECT 
                    txhash,
                    block_time,
                    change_type,
                    change_amount,
                    post_balance,
                    fee_sol
                FROM tbl_wallet_transactions 
                WHERE txhash = ?
            ");
            $getStmt->execute([$txhash]);
            $updatedTransaction = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            // Format amounts for response
            $updatedTransaction['amount_sol'] = round($updatedTransaction['change_amount'] / 1000000000, 9);
            $updatedTransaction['balance_sol'] = round($updatedTransaction['post_balance'] / 1000000000, 9);
            
            echo json_encode([
                'success' => true,
                'message' => 'Wallet transaction updated successfully',
                'transaction' => $updatedTransaction
            ]);
        } else {
            throw new Exception('Failed to update transaction');
        }
        
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
