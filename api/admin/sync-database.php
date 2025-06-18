<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../config/discord.php';

// Check if user is admin
if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Connect to database
$dbPath = '/var/www/html/db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database Error: Connection failed - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get user roles
$stmt = $db->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
$stmt->execute([$_SESSION['discord_id']]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Check if user is moderator or founder
if (!in_array('Moderator', $roles) && !in_array('Founder', $roles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Function to get table schema
function getTableSchema($db, $table) {
    $stmt = $db->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name=?");
    $stmt->execute([$table]);
    return $stmt->fetchColumn();
}

// Function to get table data
function getTableData($db, $table) {
    $stmt = $db->prepare("SELECT * FROM " . $table);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all tables
$stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table'");
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$database_info = [];
foreach ($tables as $table) {
    $database_info[$table] = [
        'schema' => getTableSchema($db, $table),
        'row_count' => $db->query("SELECT COUNT(*) FROM " . $table)->fetchColumn()
    ];
}

// Handle different actions
$action = $_GET['action'] ?? 'info';

switch($action) {
    case 'info':
        echo json_encode([
            'success' => true,
            'database_path' => $dbPath,
            'tables' => $database_info
        ]);
        break;

    case 'verify_tables':
        $missing_tables = [];
        $expected_tables = [
            'tbl_users',
            'tbl_user_roles',
            'tbl_user_traits',
            'tbl_cheese_clicks',
            'tbl_rewards',
            'tbl_store_items',
            'tbl_user_inventory',
            'tbl_purchase_history',
            'tbl_user_scores',
            'tbl_tetris_scores',
            'leaderboard'
        ];

        foreach ($expected_tables as $table) {
            if (!in_array($table, $tables)) {
                $missing_tables[] = $table;
            }
        }

        echo json_encode([
            'success' => true,
            'all_tables_present' => empty($missing_tables),
            'missing_tables' => $missing_tables
        ]);
        break;

    case 'export':
        // Export database structure and data
        $export_data = [];
        foreach ($tables as $table) {
            $export_data[$table] = [
                'schema' => getTableSchema($db, $table),
                'data' => getTableData($db, $table)
            ];
        }

        $filename = 'database_export_' . date('Y-m-d_H-i-s') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo json_encode($export_data);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?> 