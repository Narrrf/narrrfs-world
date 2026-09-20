<?php
/**
 * Owned Genesis Selection V1
 *
 * This endpoint is the minimal read-only selection source for the Genesis
 * Player Bootstrap. It exposes only selectable, verified Genesis descriptors
 * for the authenticated session. It never persists a selection.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const OWNED_GENESIS_SELECTION_V1_CONTRACT_VERSION = 'owned_genesis_selection_v1';
const OWNED_GENESIS_SELECTION_V1_COLLECTION = 'genesis';

/**
 * Send one minimal selection response and stop execution.
 */
function owned_genesis_selection_v1_response(int $status, string $state, array $data = []): void
{
    http_response_code($status);
    echo json_encode(array_merge([
        'state' => $state,
        'contract_version' => OWNED_GENESIS_SELECTION_V1_CONTRACT_VERSION
    ], $data));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    owned_genesis_selection_v1_response(405, 'method_not_allowed');
}

$userId = trim((string)($_SESSION['discord_id'] ?? ''));
if ($userId === '') {
    owned_genesis_selection_v1_response(401, 'unauthenticated');
}

try {
    $pdo = getDatabaseConnection();
    // One deferred SQLite read transaction keeps this selection list isolated
    // from writes and makes no permanent selection, Lab, or ownership change.
    $pdo->exec('BEGIN DEFERRED TRANSACTION');

    $stmt = $pdo->prepare("
        SELECT token_id, nft_name, image_url
        FROM tbl_nft_ownership
        WHERE user_id = ?
          AND LOWER(COALESCE(collection, '')) = 'genesis'
          AND COALESCE(is_verified, 0) = 1
        ORDER BY token_id ASC
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $pdo->exec('COMMIT');
} catch (Throwable $error) {
    if (isset($pdo)) {
        try {
            $pdo->exec('ROLLBACK');
        } catch (Throwable $rollbackError) {
            // The original read failure determines the safe unavailable state.
        }
    }

    owned_genesis_selection_v1_response(503, 'ownership_verification_unavailable');
}

$genesis = [];
$seenTokenIds = [];
foreach ($rows as $row) {
    $tokenId = trim((string)($row['token_id'] ?? ''));
    if ($tokenId === '' || isset($seenTokenIds[$tokenId])) {
        continue;
    }

    $seenTokenIds[$tokenId] = true;
    $displayName = trim((string)($row['nft_name'] ?? ''));
    $imageUrl = trim((string)($row['image_url'] ?? ''));

    // Staked and frozen verified Genesis remain selectable: this route does
    // not join or filter on staking, Freezer, Lab, or temporary game state.
    $genesis[] = [
        'token_id' => $tokenId,
        'collection' => OWNED_GENESIS_SELECTION_V1_COLLECTION,
        'display_name' => $displayName !== '' ? $displayName : ('Genesis Mouse #' . $tokenId),
        'image_url' => $imageUrl !== '' ? $imageUrl : null
    ];
}

owned_genesis_selection_v1_response(200, $genesis === [] ? 'no_verified_genesis' : 'verified_genesis_available', [
    'genesis' => $genesis
]);
