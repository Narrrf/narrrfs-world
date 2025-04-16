<?php
session_start();

// Set JSON response header
header('Content-Type: application/json');

// ✅ Ensure user is logged in
if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '❌ User not logged in.']);
    exit;
}

$discord_id = $_SESSION['discord_id'];

try {
    // ✅ SQLite path (relative)
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ✅ Query traits for user
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
