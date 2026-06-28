<?php
/**
 * SPOINC Bridge Config API.
 *
 * DEVS FOR DECADES:
 * Frontend-safe config endpoint for swap-lab.html.
 * This endpoint does not expose API keys.
 * This endpoint does not execute swaps.
 * This endpoint does not move DSPOINC.
 */

require_once __DIR__ . '/bridge-helpers.php';

spoinc_bridge_boot_json_api(['GET', 'OPTIONS']);

try {
    $pdo = spoinc_bridge_open_database();
    $config = spoinc_bridge_load_config($pdo);
    $routes = spoinc_bridge_load_routes($pdo);

    spoinc_bridge_json_response([
        'success' => true,
        'data' => [
            'config' => spoinc_bridge_public_config_payload($config),
            'routes' => spoinc_bridge_public_routes_payload($routes),
            'safety' => [
                'public_execution_enabled' => $config ? (int)$config['public_enabled'] === 1 : false,
                'settlement_enabled' => $config ? (int)$config['settlement_enabled'] === 1 : false,
                'api_key_exposed' => false,
                'ledger_movement_enabled' => false,
                'message' => 'Bridge is in safe preparation mode. No DSPOINC ledger movement is enabled.'
            ]
        ]
    ]);
} catch (Throwable $e) {
    error_log('SPOINC Bridge Config ERROR: ' . $e->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Server error while loading bridge config'
    ], 500);
}