<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/admin-authorization.php';
$actor = narrrfs_require_admin_request('owner_control.lab_store_live_offers.commercial.v1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
try {
    $raw = file_get_contents('php://input');
    $input = json_decode(is_string($raw) ? $raw : '', true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new JsonException();
    require_once __DIR__ . '/../../../config/database.php';
    require_once __DIR__ . '/../../../lib/owner-lab-store-offer-commercial-v1.php';
    $db = new SQLite3(getDatabasePath(), SQLITE3_OPEN_READWRITE);
    $out = narrrfs_owner_lab_store_offer_commercial($db, $input, $actor);
    $db->close();
    echo json_encode(['success' => true, 'capability' => 'owner_control_lab_store_live_offers_commercial', 'writes' => true, 'idempotent_replay' => $out['replayed'], 'offer' => ['offer_id' => $out['offer_id'], 'owner_control_version' => $out['version']]]);
} catch (JsonException $e) {
    http_response_code(400); echo json_encode(['success' => false, 'error' => 'invalid_request']);
} catch (NarrrfsOwnerLabStoreOfferCommercialException $e) {
    if (isset($db) && $db instanceof SQLite3) $db->close();
    http_response_code($e->status()); echo json_encode(['success' => false, 'error' => $e->publicCode()]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof SQLite3) $db->close();
    http_response_code(500); echo json_encode(['success' => false, 'error' => 'commercial_update_failed']);
}
