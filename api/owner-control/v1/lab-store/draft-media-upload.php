<?php
declare(strict_types=1);

// This route stores a validated private-draft image and returns its public
// reference. It deliberately does not mutate any SQLite row; the normal
// draft create/update transaction persists that reference with audit/receipt.
require_once __DIR__ . '/../../../config/admin-authorization.php';
narrrfs_require_admin_request('owner_control.lab_store_drafts.media_upload.v1');
require_once __DIR__ . '/../../../lib/owner-lab-store-draft-media-v1.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
try {
    $reference = narrrfs_lab_store_draft_media_store($_FILES['image'] ?? []);
    echo json_encode(['success' => true, 'capability' => 'owner_control_lab_store_draft_media_upload', 'writes' => true, 'database_writes' => false, 'media_reference' => $reference], JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException) {
    http_response_code(422); echo json_encode(['success' => false, 'error' => 'invalid_image']);
} catch (Throwable $error) {
    error_log('[OWNER CONTROL] Lab Store draft media upload unavailable: ' . $error->getMessage());
    http_response_code(503); echo json_encode(['success' => false, 'error' => 'draft_media_unavailable']);
}
