<?php
// 🧠 SQL Junior 5.0 – Matrix-Style Live Table Data API
header('Content-Type: application/json');

$path = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'DB Connection Failed: ' . $e->getMessage()
    ]);
    exit;
}

// Get the requested table
$tableName = $_GET['table'] ?? '';

if (empty($tableName)) {
    echo json_encode([
        'success' => false,
        'error' => 'No table specified'
    ]);
    exit;
}

// Get all tables for stats
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

try {
    // Get table stats
    $rowCount = $pdo->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
    $columns = $pdo->query("PRAGMA table_info($tableName)")->fetchAll(PDO::FETCH_ASSOC);
    $columnCount = count($columns);
    
    // Get table data
    $rows = $pdo->query("SELECT * FROM $tableName LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate HTML for table content
    $html = '';
    
    if (count($rows) === 0) {
        $html = '<div class="empty-message">No data found in this table.</div>';
    } else {
        $html = '<div class="table-title">📊 ' . htmlspecialchars($tableName) . '</div>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        
        foreach (array_keys($rows[0]) as $col) {
            $html .= '<th>' . htmlspecialchars($col) . '</th>';
        }
        
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $displayValue = $value ?? '';
                if (is_string($displayValue) && strlen($displayValue) > 50) {
                    $displayValue = substr($displayValue, 0, 47) . '...';
                }
                $html .= '<td>' . htmlspecialchars($displayValue) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        if (count($rows) >= 1000) {
            $html .= '<div style="text-align: center; margin-top: 15px; color: #00aa00; font-style: italic;">Showing first 1000 rows...</div>';
        }
    }
    
    echo json_encode([
        'success' => true,
        'tableName' => $tableName,
        'rowCount' => $rowCount,
        'columnCount' => $columnCount,
        'totalTables' => count($tables),
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error loading table: ' . $e->getMessage()
    ]);
}
?>