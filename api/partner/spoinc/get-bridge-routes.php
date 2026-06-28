<?php
/**
 * SPOINC Bridge Routes API.
 *
 * DEVS FOR DECADES:
 * Returns route toggle state for frontend display.
 * This endpoint does not execute routes.
 * This endpoint does not call Gensuki.
 * This endpoint does not touch the DSPOINC ledger.
 */

require_once __DIR__ . '/bridge-helpers.php';

spoinc_bridge_boot_json_api(['GET', 'OPTIONS']);

try {
    $pdo = spoinc_bridge_open_database();
    $routes = spoinc_bridge_load_routes($pdo);

    spoinc_bridge_json_response([
        'success' => true,
        'data' => [
            'routes' => spoinc_bridge_public_routes_payload($routes),
            'route_count' => count($routes)
        ]
    ]);
} catch (Throwable $e) {
    error_log('SPOINC Bridge Routes ERROR: ' . $e->getMessage());

    spoinc_bridge_json_response([
        'success' => false,
        'error' => 'Server error while loading bridge routes'
    ], 500);
}