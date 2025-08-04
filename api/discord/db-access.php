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
$query = $data['query'] ?? '';
$params = $data['params'] ?? [];

// --- ACTION SAFETY ---
if ($action !== 'query') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    $db->close();
    exit;
}

// --- SQL SAFETY ---
if (!preg_match('/^\s*(SELECT|INSERT|UPDATE)\s/i', $query)) {
    echo json_encode(['success' => false, 'error' => 'Query not allowed: Only SELECT, INSERT, UPDATE permitted.']);
    $db->close();
    exit;
}

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
