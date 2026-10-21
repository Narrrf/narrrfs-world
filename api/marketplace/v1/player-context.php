<?php

declare(strict_types=1);

/**
 * Authenticated Genetic Marketplace player context.
 *
 * Returns real listing IDs, server-owned prices, seller presentation,
 * current listing state and the session-bound CSRF token required for writes.
 *
 * This endpoint is read-only with respect to application/database state.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../lib/genetic-marketplace-v1.php';

try {
    if (
        strtoupper(
            (string)($_SERVER['REQUEST_METHOD'] ?? '')
        ) !== 'GET'
    ) {
        header('Allow: GET');

        genetic_marketplace_fail(
            405,
            'method_not_allowed',
            'GET is required.'
        );
    }

    $userId = genetic_marketplace_require_user();

    $context =
        genetic_marketplace_player_context(
            getDatabaseConnection(),
            $userId
        );

    $context['csrf_token'] =
        genetic_marketplace_csrf_token();

    genetic_marketplace_json_response(200, [
        'success' => true,
        'data' => $context,
    ]);
} catch (Throwable $error) {
    genetic_marketplace_emit_error(
        $error,
        'player-context'
    );
}
