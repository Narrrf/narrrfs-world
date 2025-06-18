<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

require_once __DIR__ . '/../config/discord.php';

// Load role map
$roleMap = require __DIR__ . '/../../discord-tools/role_map.php';
$roleIdToName = array_flip($roleMap);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

// Connect to database with fallback
try {
    // Try production path first
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
    if (!file_exists($dbPath)) {
        error_log("❌ Production database not found at $dbPath");
        // Fallback to development path
        $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
        if (!file_exists($dbPath)) {
            throw new Exception("Database not found at $dbPath");
        }
        error_log("✅ Using development database at $dbPath");
    }
    
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
    case 'sync_users':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $users = json_decode(file_get_contents('php://input'), true);
        if (!$users || !is_array($users)) {
            error_log("Invalid user data received: " . file_get_contents('php://input'));
            http_response_code(400);
            echo json_encode(['error' => 'Invalid user data']);
            exit;
        }

        try {
            error_log("Starting user sync with " . count($users) . " users");
            $db->beginTransaction();

            // Prepare statements
            $checkUser = $db->prepare("SELECT 1 FROM tbl_users WHERE discord_id = ?");
            $insertUser = $db->prepare("INSERT INTO tbl_users (discord_id, username, discriminator, avatar_url) VALUES (?, ?, ?, ?)");
            $updateUser = $db->prepare("UPDATE tbl_users SET username = ?, discriminator = ?, avatar_url = ? WHERE discord_id = ?");
            
            // Delete old roles
            $deleteRoles = $db->prepare("DELETE FROM tbl_user_roles WHERE discord_id = ?");
            // Insert new roles
            $insertRole = $db->prepare("INSERT INTO tbl_user_roles (discord_id, role_id) VALUES (?, ?)");

            $synced = 0;
            $updated = 0;
            $errors = [];

            foreach ($users as $user) {
                try {
                    error_log("Processing user: " . json_encode($user));
                    // Check if user exists
                    $checkUser->execute([$user['id']]);
                    $exists = $checkUser->fetchColumn();

                    if ($exists) {
                        // Update existing user
                        $updateUser->execute([
                            $user['username'],
                            $user['discriminator'] ?? '0',
                            $user['avatar_url'] ?? null,
                            $user['id']
                        ]);
                        $updated++;
                        error_log("Updated user: " . $user['id']);
                    } else {
                        // Insert new user
                        $insertUser->execute([
                            $user['id'],
                            $user['username'],
                            $user['discriminator'] ?? '0',
                            $user['avatar_url'] ?? null
                        ]);
                        $synced++;
                        error_log("Inserted new user: " . $user['id']);
                    }

                    // Sync roles - using role IDs directly
                    if (isset($user['roles']) && is_array($user['roles'])) {
                        $deleteRoles->execute([$user['id']]);
                        foreach ($user['roles'] as $roleId) {
                            if (isset($roleMap[$roleId])) {
                                $insertRole->execute([$user['id'], $roleId]);
                                error_log("Added role for user " . $user['id'] . ": " . $roleMap[$roleId] . " ($roleId)");
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Error processing user " . $user['id'] . ": " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    $errors[] = [
                        'user_id' => $user['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            $db->commit();
            error_log("Sync completed. Synced: $synced, Updated: $updated, Errors: " . count($errors));

            echo json_encode([
                'success' => true,
                'synced_users' => $synced,
                'updated_users' => $updated,
                'errors' => $errors
            ]);
        } catch (PDOException $e) {
            error_log("Database transaction error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $db->rollBack();
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
        break;

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