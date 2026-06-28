<?php
/**
 * SPOINC Bridge Tester Access Check API.
 *
 * Plain language for DEVS:
 * This endpoint only confirms whether the current user is allowed to see
 * private SPOINC bridge test tools.
 *
 * This endpoint must never:
 * - call Gensuki transaction endpoints
 * - create bridge transactions
 * - deduct DSPOINC
 * - credit DSPOINC
 * - settle intents
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/bridge-tester-helpers.php';

$userId = spoinc_bridge_tester_require_access();

spoinc_bridge_tester_json_response([
    'success' => true,
    'data' => [
        'tester_enabled' => true,
        'user_id' => $userId,
        'allowed_tester_ids' => SPOINC_BRIDGE_TESTER_IDS,
        'safety' => [
            'transaction_created' => false,
            'ledger_movement_enabled' => false,
            'api_key_exposed' => false,
            'message' => 'Private tester access confirmed. No bridge movement was executed.'
        ]
    ]
]);