<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No CSV file uploaded or upload error occurred');
    }
    
    $csvFile = $_FILES['csv_file'];
    $fileName = $csvFile['name'];
    $fileSize = $csvFile['size'];
    $fileType = $csvFile['type'];
    
    // Validate file type
    if ($fileType !== 'text/csv' && $fileType !== 'application/vnd.ms-excel' && !str_ends_with($fileName, '.csv')) {
        throw new Exception('Invalid file type. Please upload a CSV file.');
    }
    
    // Validate file size (max 5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }
    
    // Read CSV file
    $handle = fopen($csvFile['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception('Could not read uploaded file');
    }
    
    // Parse CSV header
    $header = fgetcsv($handle);
    if (!$header) {
        throw new Exception('Could not read CSV header');
    }
    
    // Expected columns: type, description, amount, date
    $expectedColumns = ['type', 'description', 'amount', 'date'];
    $columnIndexes = [];
    
    foreach ($expectedColumns as $column) {
        $index = array_search($column, $header);
        if ($index === false) {
            throw new Exception("Missing required column: $column");
        }
        $columnIndexes[$column] = $index;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    $importedCount = 0;
    $skippedCount = 0;
    $errors = [];
    $rowNumber = 1; // Start from 1 since header is row 0
    
    // Process CSV rows
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        
        try {
            // Extract data from row
            $type = trim($row[$columnIndexes['type']] ?? '');
            $description = trim($row[$columnIndexes['description']] ?? '');
            $amount = trim($row[$columnIndexes['amount']] ?? '');
            $date = trim($row[$columnIndexes['date']] ?? '');
            
            // Skip empty rows
            if (empty($type) && empty($description) && empty($amount)) {
                continue;
            }
            
            // Validate data
            if (empty($type) || empty($description) || empty($amount)) {
                $errors[] = "Row $rowNumber: Missing required data (type, description, or amount)";
                $skippedCount++;
                continue;
            }
            
            // Validate type
            if (!in_array(strtolower($type), ['expense', 'income'])) {
                $errors[] = "Row $rowNumber: Invalid type '$type'. Must be 'expense' or 'income'";
                $skippedCount++;
                continue;
            }
            
            // Validate amount
            $amountValue = floatval($amount);
            if ($amountValue <= 0) {
                $errors[] = "Row $rowNumber: Invalid amount '$amount'. Must be greater than 0";
                $skippedCount++;
                continue;
            }
            
            // Validate and format date
            $dateValue = null;
            if (!empty($date)) {
                $parsedDate = date_parse($date);
                if ($parsedDate['error_count'] > 0 || !checkdate($parsedDate['month'], $parsedDate['day'], $parsedDate['year'])) {
                    $errors[] = "Row $rowNumber: Invalid date format '$date'. Use YYYY-MM-DD format";
                    $skippedCount++;
                    continue;
                }
                $dateValue = $parsedDate['year'] . '-' . str_pad($parsedDate['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parsedDate['day'], 2, '0', STR_PAD_LEFT);
            } else {
                $dateValue = date('Y-m-d');
            }
            
            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO tbl_community_funds (type, description, amount, date, created_at) 
                VALUES (?, ?, ?, ?, datetime('now'))
            ");
            
            $stmt->execute([$type, $description, $amountValue, $dateValue]);
            $importedCount++;
            
        } catch (Exception $e) {
            $errors[] = "Row $rowNumber: " . $e->getMessage();
            $skippedCount++;
        }
    }
    
    fclose($handle);
    
    // Commit transaction if successful
    if (empty($errors) || $importedCount > 0) {
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "CSV import completed successfully",
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
            'total_rows' => $rowNumber - 1
        ]);
    } else {
        // Rollback if no successful imports
        $pdo->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'No valid entries found in CSV',
            'imported' => 0,
            'skipped' => $skippedCount,
            'errors' => $errors,
            'total_rows' => $rowNumber - 1
        ]);
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Import failed: ' . $e->getMessage(),
        'imported' => 0,
        'skipped' => 0,
        'errors' => [$e->getMessage()]
    ]);
}
?>
