<?php
header('Content-Type: application/json');

// Council-level: Get Authorization header, support lowercase variants
function get_auth_token() {
    foreach (['Authorization', 'authorization'] as $key) {
        $headers = getallheaders();
        if (isset($headers[$key])) return $headers[$key];
    }
    return '';
}

// Token check: supports $_ENV and getenv fallback
$auth_token = get_auth_token();
$valid_token = $_ENV['DISCORD_BOT_SECRET'] ?? getenv('DISCORD_BOT_SECRET');
if ($auth_token !== $valid_token) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Use the classic HTML (public) path — relative to this script
$db_path = __DIR__ . '/../../db/narrrf_world.sqlite';
if (!file_exists($db_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database not found at classic HTML path: ' . $db_path]);
    exit;
}

$db = new SQLite3($db_path);

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

// Allow only safe actions
if ($action !== 'query') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    $db->close();
    exit;
}

$query = $data['query'] ?? '';
$params = $data['params'] ?? [];

// SQL safety: only allow SELECT, INSERT, UPDATE (no DELETE, DROP, etc.)
if (!preg_match('/^\s*(SELECT|INSERT|UPDATE)\s/i', $query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Query not allowed: Only SELECT, INSERT, UPDATE permitted.']);
    $db->close();
    exit;
}

try {
    $stmt = $db->prepare($query);
    foreach ($params as $i => $value) {
        $stmt->bindValue($i + 1, $value);
    }
    $result = $stmt->execute();

if (preg_match('/^\s*SELECT/i', $query)) {
    // SELECT queries: Return data rows
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $rows]);
} else if (preg_match('/^\s*INSERT/i', $query)) {
    // INSERT queries: Return affectedRows and the insertId
    $insertId = $db->lastInsertRowID();
    echo json_encode([
        'success' => true,
        'affectedRows' => $db->changes(),
        'insertId' => $insertId
    ]);
} else {
    // UPDATE queries: Just affectedRows
    echo json_encode(['success' => true, 'affectedRows' => $db->changes()]);
}

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$db->close();
