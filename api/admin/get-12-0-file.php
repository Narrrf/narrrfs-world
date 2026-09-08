<?php
/**
 * Legacy online developer-workspace viewer retired in the local-only migration.
 * DEVS FOR DECADES: return a static response without opening files, sessions,
 * or databases. Developer continuity must never depend on production storage.
 */
http_response_code(410);
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'error' => 'The legacy development workspace viewer has been retired.'
]);
