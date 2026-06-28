<?php
/**
 * SPOINC User Bridge History API.
 *
 * DEVS FOR DECADES:
 * Read-only history endpoint for the logged-in user's bridge intents and
 * transaction records.
 *
 * This endpoint does not create intents.
 * This endpoint does not settle DSPOINC.
 * This endpoint does not call Gensuki.
 */

require_once __DIR__ . '/bridge-helpers.php';

spoinc_bridge_boot_json_api(['GET', 'POST', 'OPTIONS']);

try {
    $requestData = spoinc_bridge_get_request_data();
    $discordId = spoinc_bridge_resolve_session_user_id($requestData);

    if ($discordId === '') {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Not logged in'
        ], 401);
    }

    $limit = isset($requestData['limit']) ? (int)$requestData['limit'] : 20;
    $limit = max(1, min(50, $limit));

    $pdo = spoinc_bridge_open_database();

    $stmt = $pdo->prepare("
        SELECT
            i.intent_id,
            i.idempotency_id,
            i.wallet,
            i.route_key,
            i.direction,
            i.input_token,
            i.output_token,
            i.input_amount,
            i.expected_output_amount,
            i.dspoinc_amount,
            i.spoinc_amount,
            i.status,
            i.gensuki_status,
            i.narrrfs_status,
            i.created_at,
            i.updated_at,
            i.confirmed_at,
            i.settled_at,
            i.failed_at,
            t.transaction_hash,
            t.signature,
            t.gensuki_status AS transaction_gensuki_status,
            t.narrrfs_status AS transaction_narrrfs_status
        FROM tbl_spoinc_bridge_intents i
        LEFT JOIN tbl_spoinc_bridge_transactions t
            ON t.intent_id = i.intent_id
        WHERE i.discord_id = ?
        ORDER BY i.intent_id DESC
        LIMIT {$limit}
    ");
    $stmt->execute([$discordId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    spoinc_bridge_json_response([
        'success' => true,
        'data' => [
            'discord_id' => $discordId,
            'history' => $rows,
            'limit' => $limit,
            'preview_only' => true
        ]
    ]);
} catch (Throwable $e) {
    error_log('SPOINC User Bridge History ERROR: ' . $e->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Server error while loading bridge history'
    ], 500);
}