<?php

declare(strict_types=1);

/**
 * Buy one authenticated Genetic Marketplace listing.
 *
 * The server re-reads and owns:
 * listing status, seller identity, price, DSPOINC availability,
 * duplicate-trait limit, Genetic Item identity, and transfer state.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../lib/genetic-marketplace-v1.php';

try {
    genetic_marketplace_assert_mutation_request($_SERVER);

    $userId = genetic_marketplace_require_user();

    genetic_marketplace_verify_csrf(
        (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')
    );

    $result =
        genetic_marketplace_buy_listing(
            getDatabaseConnection(),
            $userId,
            genetic_marketplace_json_input()
        );

    genetic_marketplace_json_response(200, [
        'success' => true,
        'data' => $result,
    ]);
} catch (Throwable $error) {
    genetic_marketplace_emit_error(
        $error,
        'buy-listing'
    );
}
