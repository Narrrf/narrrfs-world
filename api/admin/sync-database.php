<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once __DIR__ . '/../config/discord.php';

// Get authorization header
$auth_header = apache_request_headers()['Authorization'] ?? '';
if (!$auth_header || !preg_match('/^Bearer\s+(.+)$/', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$token = $matches[1];

// Verify bot token
if ($token !== DISCORD_BOT_SECRET) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
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

// Get all tables
$stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table'");
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle different actions
$action = $_GET['action'] ?? 'info';

switch($action) {
    case 'info':
        $database_info = [];
        foreach ($tables as $table) {
            $count = $db->query("SELECT COUNT(*) FROM " . $table)->fetchColumn();
            $database_info[$table] = [
                'row_count' => $count
            ];
        }
        
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
            'missing_tables' => $missing_tables,
            'available_tables' => $tables
        ]);
        break;

    case 'export':
        // Export database structure and data
        $export_data = [];
        foreach ($tables as $table) {
            $stmt = $db->prepare("SELECT * FROM " . $table);
            $stmt->execute();
            $export_data[$table] = [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
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