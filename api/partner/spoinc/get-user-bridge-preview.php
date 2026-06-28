<?php
/**
 * SPOINC User Bridge Preview API.
 *
 * DEVS FOR DECADES:
 * This endpoint gives the logged-in user a read-only bridge preview.
 * It calculates backend-authoritative DSPOINC availability.
 *
 * It must not:
 * - create swap intents
 * - call Gensuki execution routes
 * - deduct DSPOINC
 * - credit DSPOINC
 * - expose API keys
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

    $wallet = trim((string)($requestData['wallet'] ?? ''));

    if ($wallet !== '' && !spoinc_bridge_is_valid_solana_address($wallet)) {
        spoinc_bridge_json_response([
            'success' => false,
            'error' => 'Invalid Solana wallet address format'
        ], 400);
    }

    $pdo = spoinc_bridge_open_database();

    if ($wallet !== '') {
        $walletDiscordId = spoinc_bridge_resolve_discord_id_for_wallet($pdo, $wallet);

        if ($walletDiscordId !== null && $walletDiscordId !== $discordId) {
            spoinc_bridge_json_response([
                'success' => false,
                'error' => 'Wallet belongs to a different verified Discord user'
            ], 403);
        }
    }

    $config = spoinc_bridge_load_config($pdo);
    $routes = spoinc_bridge_load_routes($pdo);
    $balance = spoinc_bridge_calculate_dspoinc_balance($pdo, $discordId);

    spoinc_bridge_json_response([
        'success' => true,
        'data' => [
            'discord_id' => $discordId,
            'wallet' => $wallet,
            'balance' => $balance,
            'config' => spoinc_bridge_public_config_payload($config),
            'routes' => spoinc_bridge_public_routes_payload($routes),
            'preview_only' => true,
            'can_execute_bridge' => false,
            'safety' => [
                'public_execution_enabled' => $config ? (int)$config['public_enabled'] === 1 : false,
                'settlement_enabled' => $config ? (int)$config['settlement_enabled'] === 1 : false,
                'ledger_movement_enabled' => false,
                'message' => 'Preview only. Bridge execution waits for final Gensuki payloads and ledger trigger confirmation.'
            ]
        ]
    ]);
} catch (Throwable $e) {
    error_log('SPOINC User Bridge Preview ERROR: ' . $e->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Server error while loading user bridge preview'
    ], 500);
}