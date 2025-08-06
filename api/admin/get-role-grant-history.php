<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$dbPath = '/var/www/html/db/narrrf_world.sqlite';

try {
    // Get query parameters
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    $userId = $_GET['user_id'] ?? '';
    $roleId = $_GET['role_id'] ?? '';
    $action = $_GET['action'] ?? ''; // 'granted' or 'revoked'

    // Connect to database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Build query
    $query = "SELECT * FROM tbl_role_grants WHERE 1=1";
    $params = [];

    if (!empty($userId)) {
        $query .= " AND user_id = ?";
        $params[] = $userId;
    }

    if (!empty($roleId)) {
        $query .= " AND role_id = ?";
        $params[] = $roleId;
    }

    if ($action === 'granted') {
        $query .= " AND revoked_at IS NULL";
    } elseif ($action === 'revoked') {
        $query .= " AND revoked_at IS NOT NULL";
    }

    $query .= " ORDER BY granted_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Execute query
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM tbl_role_grants WHERE 1=1";
    $countParams = [];

    if (!empty($userId)) {
        $countQuery .= " AND user_id = ?";
        $countParams[] = $userId;
    }

    if (!empty($roleId)) {
        $countQuery .= " AND role_id = ?";
        $countParams[] = $roleId;
    }

    if ($action === 'granted') {
        $countQuery .= " AND revoked_at IS NULL";
    } elseif ($action === 'revoked') {
        $countQuery .= " AND revoked_at IS NOT NULL";
    }

    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'history' => $history,
        'total_count' => $totalCount,
        'limit' => $limit,
        'offset' => $offset
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?> 