<?php
// 🧩 Riddle Trait Unlock API — unlocks traits for Three.js Dimension riddles
// CORS handled by .htaccess - no duplicate headers here

session_start();

// Set JSON response header (must be before any output)
header('Content-Type: application/json');

// Handle preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$trait_name = $input['trait_name'] ?? null;
$trait_value = $input['trait_value'] ?? 'true';
$user_id = $input['user_id'] ?? null; // Allow user_id from JSON body for local testing

// Get Discord ID from session (production) or JSON body (local testing)
$discord_id = null;
if (isset($_SESSION['discord_id'])) {
    // Production: Use session Discord ID
    $discord_id = $_SESSION['discord_id'];
} elseif ($user_id) {
    // Local testing: Use user_id from JSON body (LOCAL_TEST_DISCORD)
    $discord_id = $user_id;
} else {
    // No Discord ID found
    http_response_code(401);
    echo json_encode(['error' => '❌ User not logged in. No Discord ID found in session or request body.']);
    exit;
}

if (!$trait_name) {
    http_response_code(400);
    echo json_encode(['error' => '❌ Trait name is required.']);
    exit;
}

try {
    // ✅ SQLite path (relative)
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable foreign keys (SQLite requires this per connection)
    $pdo->exec("PRAGMA foreign_keys = ON");
    
    // 🧪 LOCAL TESTING: Auto-create test user if it doesn't exist (for LOCAL_TEST_DISCORD)
    // This ensures foreign key constraint is satisfied before inserting trait
    if ($discord_id === 'LOCAL_TEST_DISCORD') {
        $userCheckStmt = $pdo->prepare("SELECT discord_id FROM tbl_users WHERE discord_id = ?");
        $userCheckStmt->execute([$discord_id]);
        $userExists = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userExists) {
            // Create test user for local testing
            try {
                $createUserStmt = $pdo->prepare("INSERT INTO tbl_users (discord_id, username, created_at) VALUES (?, 'LocalTester', CURRENT_TIMESTAMP)");
                $createUserStmt->execute([$discord_id]);
                error_log("🧪 [LOCAL TEST] Auto-created test user: $discord_id");
            } catch (Exception $e) {
                error_log("⚠️ [LOCAL TEST] Failed to create test user: " . $e->getMessage());
                // Continue anyway - if foreign keys aren't enforced, trait insert might still work
            }
        }
    }

    // Check if trait already exists (table uses 'trait' column, not 'trait_name')
    $checkStmt = $pdo->prepare("SELECT trait FROM tbl_user_traits WHERE user_id = ? AND trait = ?");
    $checkStmt->execute([$discord_id, $trait_name]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Trait already exists - update timestamp
        $stmt = $pdo->prepare("UPDATE tbl_user_traits SET timestamp = CURRENT_TIMESTAMP WHERE user_id = ? AND trait = ?");
        $stmt->execute([$discord_id, $trait_name]);
        echo json_encode([
            'success' => true,
            'message' => '✅ Trait updated successfully',
            'trait_name' => $trait_name,
            'trait_value' => $trait_value,
            'action' => 'updated'
        ]);
    } else {
        // Insert new trait (table structure: user_id, trait, timestamp)
        $stmt = $pdo->prepare("INSERT INTO tbl_user_traits (user_id, trait, timestamp) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$discord_id, $trait_name]);
        echo json_encode([
            'success' => true,
            'message' => '✅ Trait unlocked successfully',
            'trait_name' => $trait_name,
            'trait_value' => $trait_value,
            'action' => 'unlocked'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage();
    error_log("❌ [TRAIT UNLOCK] Database error: $errorMessage (User: $discord_id, Trait: $trait_name)");
    echo json_encode([
        'error' => '❌ DB error',
        'details' => $errorMessage,
        'user_id' => $discord_id,
        'trait_name' => $trait_name
    ]);
}
?>

