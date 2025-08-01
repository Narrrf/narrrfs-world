<?php
// === GUARANTEED CLEAN JSON-ONLY OUTPUT ===
if (function_exists('ob_end_clean')) @ob_end_clean();
if (function_exists('ob_clean')) @ob_clean();
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// --- COUNCIL-LEVEL AUTH ---
function get_auth_token() {
    $headers = [];
    if (function_exists('getallheaders')) $headers = getallheaders();
    foreach (['Authorization', 'authorization'] as $key) {
        if (isset($headers[$key])) return $headers[$key];
    }
    return '';
}

$auth_token = get_auth_token();
$valid_token = $_ENV['DISCORD_BOT_SECRET'] ?? getenv('DISCORD_BOT_SECRET');
if ($auth_token !== $valid_token) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// --- DB PATH & OPEN ---
$db_path = __DIR__ . '/../../db/narrrf_world.sqlite';
if (!file_exists($db_path)) {
    echo json_encode(['success' => false, 'error' => 'Database not found at classic HTML path: ' . $db_path]);
    exit;
}

try {
    $db = new SQLite3($db_path);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Could not connect to database']);
    exit;
}

// --- PARSE INPUT ---
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$query_type = $data['query_type'] ?? '';
$params = $data['params'] ?? [];

// --- ACTION SAFETY ---
if ($action !== 'query') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    $db->close();
    exit;
}

// --- WHITELISTED QUERY SYSTEM (SECURITY FIX) ---
$allowed_queries = [
    'get_user_by_id' => 'SELECT * FROM tbl_users WHERE discord_id = ?',
    'get_user_scores' => 'SELECT * FROM tbl_user_scores WHERE user_id = ?',
    'get_user_roles' => 'SELECT * FROM tbl_user_roles WHERE user_id = ?',
    'get_user_inventory' => 'SELECT * FROM tbl_user_inventory WHERE user_id = ?',
    'get_user_traits' => 'SELECT * FROM tbl_user_traits WHERE user_id = ?',
    'get_quests' => 'SELECT * FROM tbl_quests WHERE is_active = 1',
    'get_quest_claims' => 'SELECT * FROM tbl_quest_claims WHERE user_id = ?',
    'get_leaderboard' => 'SELECT * FROM tbl_leaderboard ORDER BY score DESC LIMIT 100',
    'get_store_items' => 'SELECT * FROM tbl_store_items WHERE is_active = 1',
    'insert_user' => 'INSERT OR REPLACE INTO tbl_users (discord_id, username, avatar_url) VALUES (?, ?, ?)',
    'insert_user_score' => 'INSERT OR REPLACE INTO tbl_user_scores (user_id, score, updated_at) VALUES (?, ?, ?)',
    'insert_quest_claim' => 'INSERT INTO tbl_quest_claims (quest_id, user_id, proof, status) VALUES (?, ?, ?, ?)',
    'update_user_score' => 'UPDATE tbl_user_scores SET score = ?, updated_at = ? WHERE user_id = ?',
    'update_quest_claim' => 'UPDATE tbl_quest_claims SET status = ?, reviewed_at = ? WHERE claim_id = ?'
];

if (!isset($allowed_queries[$query_type])) {
    echo json_encode(['success' => false, 'error' => 'Query type not allowed']);
    $db->close();
    exit;
}

$query = $allowed_queries[$query_type];

// --- EXECUTE ---
try {
    $stmt = $db->prepare($query);
    foreach ($params as $i => $value) {
        $stmt->bindValue($i + 1, $value);
    }
    $result = $stmt->execute();

    if (preg_match('/^\s*SELECT/i', $query)) {
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $rows]);
    }
    else if (preg_match('/^\s*INSERT/i', $query)) {
        $insertId = $db->lastInsertRowID();
        echo json_encode([
            'success' => true,
            'affectedRows' => $db->changes(),
            'insertId' => $insertId
        ]);
    }
    else {
        echo json_encode(['success' => true, 'affectedRows' => $db->changes()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . $e->getMessage()]);
}

$db->close();
?>
