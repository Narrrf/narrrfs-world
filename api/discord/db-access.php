<?php
// === GUARANTEED CLEAN JSON-ONLY OUTPUT ===
if (function_exists('ob_end_clean')) @ob_end_clean();
if (function_exists('ob_clean')) @ob_clean();
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Function to safely output JSON and exit
function outputJson($data) {
    if (function_exists('ob_end_clean')) @ob_end_clean();
    if (function_exists('ob_clean')) @ob_clean();
    echo json_encode($data);
    exit;
}

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
    outputJson(['success' => false, 'error' => 'Unauthorized']);
}

// --- DB PATH & OPEN ---
$db_path = __DIR__ . '/../../db/narrrf_world.sqlite';
if (!file_exists($db_path)) {
    outputJson(['success' => false, 'error' => 'Database not found at path: ' . $db_path]);
}

try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
} catch (Exception $e) {
    outputJson(['success' => false, 'error' => 'Could not connect to database: ' . $e->getMessage()]);
}

// --- PARSE INPUT ---
$input = file_get_contents('php://input');
if (!$input) {
    outputJson(['success' => false, 'error' => 'No input received']);
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    outputJson(['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()]);
}

$action = $data['action'] ?? '';
$query = $data['query'] ?? '';
$params = $data['params'] ?? [];

// --- ACTION SAFETY ---
if ($action !== 'query') {
    outputJson(['success' => false, 'error' => 'Invalid action: ' . $action]);
}

// --- SQL SAFETY ---
if (!preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE)\s/i', $query)) {
    outputJson(['success' => false, 'error' => 'Query not allowed: Only SELECT, INSERT, UPDATE, DELETE permitted.']);
}

// --- EXECUTE ---
try {
    $stmt = $db->prepare($query);
    if (!$stmt) {
        outputJson(['success' => false, 'error' => 'Failed to prepare statement: ' . $db->lastErrorMsg()]);
    }
    
    foreach ($params as $i => $value) {
        $stmt->bindValue($i + 1, $value);
    }
    
    $result = $stmt->execute();
    if (!$result) {
        outputJson(['success' => false, 'error' => 'Failed to execute statement: ' . $db->lastErrorMsg()]);
    }

    if (preg_match('/^\s*SELECT/i', $query)) {
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        outputJson(['success' => true, 'data' => $rows]);
    }
    else if (preg_match('/^\s*INSERT/i', $query)) {
        $insertId = $db->lastInsertRowID();
        outputJson([
            'success' => true,
            'affectedRows' => $db->changes(),
            'insertId' => $insertId
        ]);
    }
    else {
        outputJson(['success' => true, 'affectedRows' => $db->changes()]);
    }
} catch (Exception $e) {
    outputJson(['success' => false, 'error' => 'Query failed: ' . $e->getMessage()]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}
?>
