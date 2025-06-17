<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://narrrfs.world');
header('Access-Control-Allow-Credentials: true');

// Verify admin/mod status
require_once(__DIR__ . '/../auth/verify-admin.php');
if (!isAdminOrMod()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$search = $_GET['q'] ?? '';
if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Debug: Check if database exists and is readable
    if (!file_exists($dbPath)) {
        error_log("Search Debug: Database file not found at $dbPath");
    }

    // Debug: Log search parameters
    error_log("Search Debug: Searching for term: $search");

    // Search by username or Discord ID
    $stmt = $db->prepare("
        SELECT discord_id, username, avatar_url
        FROM tbl_users
        WHERE username LIKE ? COLLATE NOCASE 
        OR discord_id LIKE ? COLLATE NOCASE
        LIMIT 10
    ");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log results count
    error_log("Search Debug: Found " . count($users) . " results");
    error_log("Search Debug: Results: " . json_encode($users));

    echo json_encode($users);
} catch (Exception $e) {
    // Debug: Log any errors
    error_log("Search Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
} 