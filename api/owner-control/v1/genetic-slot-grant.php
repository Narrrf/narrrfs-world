<?php
declare(strict_types=1);

// Owner Control R5.4: mutation stays deny-by-default and always passes through trusted owner auth.
// DEVS FOR DECADES: CSRF is consumed and session unlocked by the guard before opening SQLite.
require_once __DIR__ . '/../../config/admin-authorization.php';
$ownerSubject = narrrfs_require_admin_request('owner_control.genetic_slot_grant.v1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$raw = file_get_contents('php://input');
try {
    if (!is_string($raw) || $raw === '') { throw new JsonException('empty JSON'); }
    $input = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($input) || array_is_list($input)) { throw new JsonException('expected JSON object'); }
} catch (JsonException) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_json']);
    exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/owner-genetic-slot-grant-v1.php';
$db = null;
try {
    $path = getDatabasePath();
    if (!is_file($path)) { throw new RuntimeException('Database missing.'); }
    $db = new SQLite3($path, SQLITE3_OPEN_READWRITE);
    $result = narrrfs_owner_grant_genetic_slot($db, $input, $ownerSubject);
    echo json_encode(['success' => true, 'capability' => 'owner_control_genetic_slot_grant',
        'writes' => true, 'grant' => $result], JSON_UNESCAPED_SLASHES);
} catch (NarrrfsOwnerGeneticSlotGrantException $error) {
    http_response_code($error->httpStatus());
    echo json_encode(['success' => false, 'error' => $error->publicCode()]);
} catch (Throwable $error) {
    error_log('[OWNER CONTROL] Genetic slot grant failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'grant_failed']);
} finally {
    if ($db instanceof SQLite3) { $db->close(); }
}
