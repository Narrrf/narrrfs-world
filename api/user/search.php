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

// Get search term
$search = $_GET['q'] ?? '';
if (empty($search)) {
    echo json_encode(['error' => 'Search term required']);
    exit;
}

// Connect to database
$dbPath = '/var/www/html/db/narrrf_world.sqlite';
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Debug: Check if database exists and is readable
    if (!file_exists($dbPath)) {
        error_log("Search Debug: Database file not found at $dbPath");
    }

    // Debug: Check total users in database
    $stmt = $db->query("SELECT COUNT(*) FROM tbl_users");
    $totalUsers = $stmt->fetchColumn();
    error_log("Search Debug: Total users in database: " . $totalUsers);

    // Debug: Log search parameters
    error_log("Search Debug: Searching for term: $search");

    // Debug: Show some sample users
    $stmt = $db->query("SELECT discord_id, username FROM tbl_users LIMIT 5");
    $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Search Debug: Sample users: " . json_encode($sampleUsers));

    // Search for users by username or discord_id
    $stmt = $db->prepare("
        SELECT discord_id, username 
        FROM tbl_users 
        WHERE username LIKE ? OR discord_id LIKE ?
        LIMIT 10
    ");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm]);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log results count
    error_log("Search Debug: Found " . count($users) . " results");
    error_log("Search Debug: Results: " . json_encode($users));

    echo json_encode(['success' => true, 'users' => $users]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
} catch (Exception $e) {
    // Debug: Log any errors
    error_log("Search Error: " . $e->getMessage());
    echo json_encode(['error' => 'Search failed']);
} 