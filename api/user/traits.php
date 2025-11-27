<?php
// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS for local development
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Credentials: true");
}

session_start();

// For POST requests (saving traits), allow JSON body or session
$discord_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (is_array($data) && isset($data['user_id'])) {
        // Allow POST with user_id in JSON body (for game API calls)
        $discord_id = trim((string)$data['user_id']);
    } else if (isset($_SESSION['discord_id'])) {
        // Fallback to session if available
        $discord_id = $_SESSION['discord_id'];
    }
} else if (isset($_SESSION['discord_id'])) {
    // GET requests require session
    $discord_id = $_SESSION['discord_id'];
}

// ✅ Ensure we have a discord_id
if (!$discord_id) {
    http_response_code(401);
    echo json_encode(['error' => '❌ User not logged in or missing user_id.']);
    exit;
}

try {
    // ✅ SQLite path (relative)
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle POST requests (saving traits)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            exit;
        }
        
        $traitKey = isset($data['trait_key']) ? trim((string)$data['trait_key']) : '';
        $traitValue = isset($data['trait_value']) ? trim((string)$data['trait_value']) : '1';
        
        if (empty($traitKey)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing trait_key']);
            exit;
        }
        
        // Ensure tbl_user_traits table exists with correct schema
        // First, try to create the table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tbl_user_traits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL,
                trait_name TEXT NOT NULL,
                trait_value TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, trait_name)
            )
        ");
        
        // Check if table exists and has correct column name
        // If table exists with old schema (trait_key instead of trait_name), we need to handle it
        try {
            $checkStmt = $pdo->query("PRAGMA table_info(tbl_user_traits)");
            $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            $hasTraitName = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'trait_name') {
                    $hasTraitName = true;
                    break;
                }
            }
            
            // If table exists but doesn't have trait_name column, rename trait_key to trait_name
            if (!$hasTraitName) {
                $hasTraitKey = false;
                foreach ($columns as $column) {
                    if ($column['name'] === 'trait_key') {
                        $hasTraitKey = true;
                        break;
                    }
                }
                
                if ($hasTraitKey) {
                    // SQLite doesn't support ALTER TABLE RENAME COLUMN in older versions
                    // So we need to recreate the table with correct schema
                    $pdo->exec("
                        CREATE TABLE tbl_user_traits_new (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            user_id TEXT NOT NULL,
                            trait_name TEXT NOT NULL,
                            trait_value TEXT NOT NULL,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE(user_id, trait_name)
                        )
                    ");
                    
                    // Copy data from old table to new table (renaming trait_key to trait_name)
                    $pdo->exec("
                        INSERT INTO tbl_user_traits_new (id, user_id, trait_name, trait_value, updated_at)
                        SELECT id, user_id, trait_key AS trait_name, trait_value, updated_at
                        FROM tbl_user_traits
                    ");
                    
                    // Drop old table and rename new one
                    $pdo->exec("DROP TABLE tbl_user_traits");
                    $pdo->exec("ALTER TABLE tbl_user_traits_new RENAME TO tbl_user_traits");
                }
            }
        } catch (Exception $e) {
            // If table doesn't exist or error checking, it will be created with correct schema
            // This is fine, just continue
        }
        
        // Insert or update trait
        $stmt = $pdo->prepare("
            INSERT OR REPLACE INTO tbl_user_traits (user_id, trait_name, trait_value, updated_at)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$discord_id, $traitKey, $traitValue]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Trait saved successfully',
            'trait_key' => $traitKey,
            'trait_value' => $traitValue
        ]);
        exit;
    }

    // ✅ GET requests: Query traits for user
    $stmt = $pdo->prepare("SELECT trait_name, trait_value FROM tbl_user_traits WHERE user_id = ?");
    $stmt->execute([$discord_id]);
    $traits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['traits' => $traits]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => '❌ DB error',
        'details' => $e->getMessage()
    ]);
}
