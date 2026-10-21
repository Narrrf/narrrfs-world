<?php

declare(strict_types=1);

/**
 * Genetic Marketplace V1 protected player service.
 *
 * DEVS FOR DECADES:
 * This service restores authenticated player create/cancel/buy behavior without
 * reopening the contained /api/admin/* namespace.
 *
 * Public anonymous Marketplace presentation remains isolated in:
 * api/marketplace/v1/genetic-listings.php
 *
 * This service owns authenticated Marketplace state only:
 * - trusted PHP session identity;
 * - CSRF + same-origin mutation protection;
 * - server-owned listing price;
 * - Genetic Item ownership/listing state;
 * - max-2 exact Genetic trait rule;
 * - atomic DSPOINC buyer/seller settlement;
 * - atomic Genetic Item ownership transfer;
 * - durable marketplace/item history.
 *
 * It must never mutate Genesis NFT ownership, permanent NFT traits/abilities,
 * staking rows, MouseFight state, SPOINC, or unrelated economy systems.
 */

require_once __DIR__ . '/../config/database.php';

const GENETIC_MARKETPLACE_MAX_EXACT_COPIES = 2;
const GENETIC_MARKETPLACE_CSRF_SESSION_KEY = 'narrrfs_genetic_marketplace_csrf_v1';

final class GeneticMarketplaceV1Exception extends RuntimeException
{
    public int $httpStatus;
    public string $errorCode;
    public array $details;

    public function __construct(
        int $httpStatus,
        string $errorCode,
        string $message,
        array $details = []
    ) {
        parent::__construct($message);

        $this->httpStatus = $httpStatus;
        $this->errorCode = $errorCode;
        $this->details = $details;
    }
}

/** Throw one bounded Marketplace error. */
function genetic_marketplace_fail(
    int $status,
    string $code,
    string $message,
    array $details = []
): never {
    throw new GeneticMarketplaceV1Exception(
        $status,
        $code,
        $message,
        $details
    );
}

/** Emit one Marketplace JSON response. */
function genetic_marketplace_json_response(
    int $status,
    array $payload
): never {
    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');

    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

/** Convert internal exceptions to non-sensitive HTTP responses. */
function genetic_marketplace_emit_error(
    Throwable $error,
    string $context
): never {
    if ($error instanceof GeneticMarketplaceV1Exception) {
        $payload = [
            'success' => false,
            'code' => $error->errorCode,
            'error' => $error->getMessage(),
        ];

        if ($error->details !== []) {
            $payload['data'] = $error->details;
        }

        genetic_marketplace_json_response(
            $error->httpStatus,
            $payload
        );
    }

    error_log(
        '[GENETIC MARKETPLACE V1] ' .
        $context .
        ': ' .
        $error->getMessage()
    );

    genetic_marketplace_json_response(500, [
        'success' => false,
        'code' => 'marketplace_unavailable',
        'error' => 'Marketplace service is unavailable.',
    ]);
}

/** Return the authenticated Discord identity from the trusted PHP session. */
function genetic_marketplace_session_user(): string
{
    $userId = trim(
        (string)($_SESSION['discord_id'] ?? '')
    );

    if (!preg_match('/\A[0-9]{17,20}\z/', $userId)) {
        return '';
    }

    return $userId;
}

/** Require a valid authenticated Discord session. */
function genetic_marketplace_require_user(): string
{
    $userId = genetic_marketplace_session_user();

    if ($userId === '') {
        genetic_marketplace_fail(
            401,
            'authentication_required',
            'Authentication required.'
        );
    }

    return $userId;
}

/** Create or return the session-bound Marketplace CSRF token. */
function genetic_marketplace_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        genetic_marketplace_fail(
            500,
            'session_unavailable',
            'Marketplace session is unavailable.'
        );
    }

    $token = trim(
        (string)($_SESSION[GENETIC_MARKETPLACE_CSRF_SESSION_KEY] ?? '')
    );

    if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        $token = bin2hex(random_bytes(32));
        $_SESSION[GENETIC_MARKETPLACE_CSRF_SESSION_KEY] = $token;
    }

    return $token;
}

/** Validate a mutation CSRF token against the current authenticated session. */
function genetic_marketplace_verify_csrf(string $providedToken): void
{
    $providedToken = trim($providedToken);
    $expectedToken = genetic_marketplace_csrf_token();

    if (
        $providedToken === '' ||
        !hash_equals($expectedToken, $providedToken)
    ) {
        genetic_marketplace_fail(
            403,
            'csrf_rejected',
            'Marketplace security token is invalid.'
        );
    }
}

/** Return true only for approved same-site Marketplace request origins. */
function genetic_marketplace_origin_allowed(array $server): bool
{
    $origin = strtolower(
        rtrim(
            trim((string)($server['HTTP_ORIGIN'] ?? '')),
            '/'
        )
    );

    $allowedOrigins = [
        'https://narrrfs.world',
        'https://narrrfs-world.onrender.com',
        'http://localhost',
        'http://127.0.0.1',
    ];

    if (
        $origin !== '' &&
        !in_array($origin, $allowedOrigins, true)
    ) {
        return false;
    }

    $fetchSite = strtolower(
        trim((string)($server['HTTP_SEC_FETCH_SITE'] ?? ''))
    );

    if (
        $fetchSite !== '' &&
        !in_array(
            $fetchSite,
            ['same-origin', 'same-site', 'none'],
            true
        )
    ) {
        return false;
    }

    return true;
}

/** Enforce the common HTTP contract for Marketplace mutations. */
function genetic_marketplace_assert_mutation_request(
    array $server
): void {
    $method = strtoupper(
        (string)($server['REQUEST_METHOD'] ?? '')
    );

    if ($method !== 'POST') {
        genetic_marketplace_fail(
            405,
            'method_not_allowed',
            'POST is required.'
        );
    }

    $contentType = strtolower(
        trim(
            explode(
                ';',
                (string)($server['CONTENT_TYPE'] ?? ''),
                2
            )[0]
        )
    );

    if ($contentType !== 'application/json') {
        genetic_marketplace_fail(
            415,
            'unsupported_media_type',
            'application/json is required.'
        );
    }

    if (!genetic_marketplace_origin_allowed($server)) {
        genetic_marketplace_fail(
            403,
            'origin_rejected',
            'Marketplace request origin is not allowed.'
        );
    }
}

/** Decode one bounded JSON mutation body. */
function genetic_marketplace_json_input(): array
{
    $raw = file_get_contents('php://input');

    if (
        !is_string($raw) ||
        $raw === '' ||
        strlen($raw) > 8192
    ) {
        genetic_marketplace_fail(
            400,
            'invalid_request_body',
            'A bounded JSON object is required.'
        );
    }

    $decoded = json_decode($raw, true);

    if (
        !is_array($decoded) ||
        json_last_error() !== JSON_ERROR_NONE
    ) {
        genetic_marketplace_fail(
            400,
            'invalid_json',
            'A valid JSON object is required.'
        );
    }

    return $decoded;
}

/** Read one required positive integer from an API payload. */
function genetic_marketplace_positive_int(
    mixed $value,
    string $field
): int {
    if (is_int($value)) {
        $number = $value;
    } elseif (
        is_string($value) &&
        preg_match('/\A[1-9][0-9]{0,17}\z/', $value)
    ) {
        $number = (int)$value;
    } else {
        genetic_marketplace_fail(
            400,
            'invalid_request',
            'Invalid ' . $field . '.'
        );
    }

    if ($number < 1) {
        genetic_marketplace_fail(
            400,
            'invalid_request',
            'Invalid ' . $field . '.'
        );
    }

    return $number;
}

/** Prepare SQLite for bounded concurrent Marketplace work. */
function genetic_marketplace_prepare_pdo(PDO $pdo): void
{
    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    $pdo->exec('PRAGMA busy_timeout = 5000');
}

/** Acquire the Marketplace SQLite writer lock before authoritative rechecks. */
function genetic_marketplace_begin_immediate(PDO $pdo): void
{
    genetic_marketplace_prepare_pdo($pdo);
    $pdo->exec('BEGIN IMMEDIATE');
}

/** Commit one complete Marketplace mutation. */
function genetic_marketplace_commit(PDO $pdo): void
{
    $pdo->exec('COMMIT');
}

/** Roll back safely after any Marketplace mutation failure. */
function genetic_marketplace_rollback(PDO $pdo): void
{
    try {
        $pdo->exec('ROLLBACK');
    } catch (Throwable $ignored) {
    }
}

/**
 * Return spendable DSPOINC.
 *
 * Protected economy rule:
 * all ledger score minus every currently active/frozen stake.
 */
function genetic_marketplace_available_dspoinc(
    PDO $pdo,
    string $userId
): int {
    $stmt = $pdo->prepare(
        "SELECT
            COALESCE((
                SELECT SUM(score)
                FROM tbl_user_scores
                WHERE user_id = ?
            ), 0)
            -
            COALESCE((
                SELECT SUM(amount)
                FROM tbl_dspoinc_stakes
                WHERE user_id = ?
                  AND status IN ('active', 'frozen')
            ), 0)"
    );

    $stmt->execute([
        $userId,
        $userId,
    ]);

    return (int)$stmt->fetchColumn();
}

/** Count exact Genetic trait copies currently owned by one Discord user. */
function genetic_marketplace_exact_trait_count(
    PDO $pdo,
    string $userId,
    string $traitType,
    string $traitValue
): int {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM tbl_user_genetic_items
         WHERE user_id = ?
           AND trait_type = ?
           AND trait_value = ?'
    );

    $stmt->execute([
        $userId,
        $traitType,
        $traitValue,
    ]);

    return (int)$stmt->fetchColumn();
}

/** Insert one existing DSPOINC ledger movement. */
function genetic_marketplace_insert_dspoinc(
    PDO $pdo,
    string $userId,
    int $amount,
    string $game,
    string $source
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO tbl_user_scores (
            user_id,
            game,
            score,
            source,
            timestamp
         ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)'
    );

    $stmt->execute([
        $userId,
        $game,
        $amount,
        $source,
    ]);
}

/** Insert the matching existing Profile/audit adjustment. */
function genetic_marketplace_insert_adjustment(
    PDO $pdo,
    string $userId,
    string $counterpartyUserId,
    int $amount,
    string $action,
    string $reason
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO tbl_score_adjustments (
            user_id,
            admin_id,
            amount,
            action,
            reason,
            timestamp
         ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)'
    );

    $stmt->execute([
        $userId,
        $counterpartyUserId,
        $amount,
        $action,
        $reason,
    ]);
}

/** Preserve one Genetic Item Marketplace audit-history event. */
function genetic_marketplace_insert_history(
    PDO $pdo,
    int $geneticItemId,
    int $listingId,
    string $userId,
    string $actionType,
    array $oldValue,
    array $newValue
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO tbl_genetic_item_history (
            genetic_item_id,
            listing_id,
            user_id,
            action_type,
            old_value_json,
            new_value_json,
            admin_user_id,
            created_at
         ) VALUES (?, ?, ?, ?, ?, ?, NULL, CURRENT_TIMESTAMP)'
    );

    $stmt->execute([
        $geneticItemId,
        $listingId,
        $userId,
        $actionType,
        json_encode(
            $oldValue,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ),
        json_encode(
            $newValue,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ),
    ]);
}

/** Keep authenticated Marketplace media inside the same verified Genetic asset namespaces. */
function genetic_marketplace_safe_asset(string $value): string
{
    $value = trim($value);

    if (
        $value === '' ||
        !preg_match('/^[\x20-\x7E]+$/', $value) ||
        str_contains($value, '%') ||
        str_contains($value, '://') ||
        str_contains($value, '..') ||
        str_contains($value, '\\') ||
        str_contains($value, '?') ||
        str_contains($value, '#') ||
        str_contains($value, '//')
    ) {
        return '';
    }

    if (str_starts_with($value, 'img/genetic/')) {
        $value = '/public/' . $value;
    }

    $safePart =
        '[A-Za-z0-9][A-Za-z0-9._-]*' .
        '(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*';

    $patterns = [
        '#^/public/img/genetic/glb/' .
            $safePart .
            '\.glb$#i',

        '#^/public/img/genetic/previews/' .
            $safePart .
            '\.(?:png|jpe?g|webp|gif)$#i',

        '#^/public/three\.js/public/genetic/(?!previews/)' .
            $safePart .
            '\.glb$#i',

        '#^/public/three\.js/public/genetic/previews/' .
            $safePart .
            '\.(?:png|jpe?g|webp|gif)$#i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return $value;
        }
    }

    return '';
}

/** Convert one active row to the authenticated Lab Marketplace contract. */
function genetic_marketplace_project_player_listing(
    array $row,
    string $viewerUserId
): array {
    return [
        'listing_id' =>
            (int)($row['listing_id'] ?? 0),

        'is_own_listing' =>
            hash_equals(
                (string)($row['seller_user_id'] ?? ''),
                $viewerUserId
            ),

        'genetic_item_id' =>
            (int)($row['genetic_item_id'] ?? 0),

        'catalog_id' =>
            (int)($row['catalog_id'] ?? 0),

        'trait_type' =>
            (string)($row['trait_type'] ?? ''),

        'trait_value' =>
            (string)($row['trait_value'] ?? ''),

        'display_title' =>
            (string)(
                $row['display_title'] ??
                $row['trait_value'] ??
                'Genetic Item'
            ),

        'rarity_tier' =>
            (string)($row['rarity_tier'] ?? 'common'),

        'item_level_snapshot' =>
            max(1, (int)($row['item_level_snapshot'] ?? 1)),

        'current_level' =>
            max(1, (int)($row['item_level_snapshot'] ?? 1)),

        'price_dspoinc' =>
            max(0, (int)($row['price_dspoinc'] ?? 0)),

        'status' =>
            (string)($row['listing_status'] ?? 'active'),

        'created_at' =>
            (string)($row['created_at'] ?? ''),

        'updated_at' =>
            (string)($row['updated_at'] ?? ''),

        'seller_username' =>
            (string)($row['seller_username'] ?? 'Holder'),

        'seller_avatar_url' =>
            (string)($row['seller_avatar_url'] ?? ''),

        'image_type' =>
            (string)($row['image_type'] ?? ''),

        'image_path' =>
            genetic_marketplace_safe_asset(
                (string)($row['image_path'] ?? '')
            ),

        'preview_path' =>
            genetic_marketplace_safe_asset(
                (string)($row['preview_path'] ?? '')
            ),
    ];
}

/** Load the authenticated active Marketplace projection used by the Lab trading UI. */
function genetic_marketplace_player_context(
    PDO $pdo,
    string $viewerUserId
): array {
    genetic_marketplace_prepare_pdo($pdo);

    $stmt = $pdo->query(
        "SELECT
            l.listing_id,
            l.seller_user_id,
            l.genetic_item_id,
            l.catalog_id,
            l.trait_type,
            l.trait_value,
            l.item_level_snapshot,
            l.price_dspoinc,
            l.listing_status,
            l.created_at,
            l.updated_at,
            c.display_title,
            c.rarity_tier,
            c.image_type,
            c.image_path,
            c.preview_path,
            u.username AS seller_username,
            u.avatar_url AS seller_avatar_url
         FROM tbl_genetic_market_listings AS l
         INNER JOIN tbl_user_genetic_items AS i
            ON i.genetic_item_id = l.genetic_item_id
           AND CAST(i.user_id AS TEXT) =
               CAST(l.seller_user_id AS TEXT)
           AND COALESCE(i.is_listed_for_sale, 0) = 1
           AND COALESCE(i.listed_listing_id, 0) =
               l.listing_id
         LEFT JOIN tbl_genetic_trait_catalog AS c
            ON c.catalog_id = l.catalog_id
         LEFT JOIN tbl_users AS u
            ON u.discord_id = l.seller_user_id
         WHERE l.listing_status = 'active'
         ORDER BY l.created_at DESC, l.listing_id DESC
         LIMIT 200"
    );

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $listings = array_map(
        static fn(array $row): array =>
            genetic_marketplace_project_player_listing(
                $row,
                $viewerUserId
            ),
        $rows
    );

    $prices = array_values(
        array_filter(
            array_map(
                static fn(array $listing): int =>
                    (int)$listing['price_dspoinc'],
                $listings
            ),
            static fn(int $price): bool => $price > 0
        )
    );

    return [
        'listings' => $listings,
        'summary' => [
            'total_active_listings' => count($listings),
            'highest_price_dspoinc' =>
                $prices !== [] ? max($prices) : 0,
            'lowest_price_dspoinc' =>
                $prices !== [] ? min($prices) : 0,
            'average_price_dspoinc' =>
                $prices !== []
                    ? (int)round(
                        array_sum($prices) /
                        count($prices)
                    )
                    : 0,
        ],
    ];
}

/** Create one active listing for one authenticated owned idle Genetic Item. */
function genetic_marketplace_create_listing(
    PDO $pdo,
    string $sellerUserId,
    array $input
): array {
    $geneticItemId =
        genetic_marketplace_positive_int(
            $input['genetic_item_id'] ?? null,
            'genetic_item_id'
        );

    $price =
        genetic_marketplace_positive_int(
            $input['price_dspoinc'] ?? null,
            'price_dspoinc'
        );

    genetic_marketplace_begin_immediate($pdo);

    try {
        $itemStmt = $pdo->prepare(
            'SELECT *
             FROM tbl_user_genetic_items
             WHERE genetic_item_id = ?
             LIMIT 1'
        );

        $itemStmt->execute([$geneticItemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            genetic_marketplace_fail(
                404,
                'genetic_item_not_found',
                'Genetic item not found.'
            );
        }

        if (
            !hash_equals(
                (string)$item['user_id'],
                $sellerUserId
            )
        ) {
            genetic_marketplace_fail(
                403,
                'ownership_required',
                'You do not own this Genetic Item.'
            );
        }

        $upgradeStatus = strtolower(
            trim((string)($item['upgrade_status'] ?? 'idle'))
        );

        $upgradeStartedAt = trim(
            (string)($item['upgrade_started_at'] ?? '')
        );

        $upgradeEndsAt = trim(
            (string)($item['upgrade_ends_at'] ?? '')
        );

        if (
            $upgradeStatus !== 'idle' ||
            (
                $upgradeStartedAt !== '' &&
                $upgradeEndsAt !== ''
            )
        ) {
            genetic_marketplace_fail(
                409,
                'item_not_idle',
                'Only idle Genetic Items can be listed.'
            );
        }

        $existingStmt = $pdo->prepare(
            "SELECT
                listing_id,
                seller_user_id,
                price_dspoinc
             FROM tbl_genetic_market_listings
             WHERE genetic_item_id = ?
               AND listing_status = 'active'
             LIMIT 1"
        );

        $existingStmt->execute([$geneticItemId]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $sameSeller =
                hash_equals(
                    (string)$existing['seller_user_id'],
                    $sellerUserId
                );

            $samePrice =
                (int)$existing['price_dspoinc'] === $price;

            $sameItemState =
                (int)($item['is_listed_for_sale'] ?? 0) === 1 &&
                (int)($item['listed_listing_id'] ?? 0) ===
                    (int)$existing['listing_id'];

            if (
                $sameSeller &&
                $samePrice &&
                $sameItemState
            ) {
                genetic_marketplace_commit($pdo);

                return [
                    'replayed' => true,
                    'listing_id' =>
                        (int)$existing['listing_id'],
                    'genetic_item_id' => $geneticItemId,
                    'price_dspoinc' => $price,
                    'status' => 'active',
                ];
            }

            genetic_marketplace_fail(
                409,
                'already_listed',
                'Item is already listed for sale.'
            );
        }

        /*
         * Preserve the verified legacy stale-flag repair:
         * no active listing exists, so stale item listing flags may be cleared
         * before creating the new authoritative listing.
         */
        if ((int)($item['is_listed_for_sale'] ?? 0) === 1) {
            $healStmt = $pdo->prepare(
                'UPDATE tbl_user_genetic_items
                 SET
                    is_listed_for_sale = 0,
                    listed_listing_id = NULL,
                    updated_at = CURRENT_TIMESTAMP
                 WHERE genetic_item_id = ?
                   AND user_id = ?'
            );

            $healStmt->execute([
                $geneticItemId,
                $sellerUserId,
            ]);

            if ($healStmt->rowCount() !== 1) {
                genetic_marketplace_fail(
                    409,
                    'item_state_conflict',
                    'Genetic Item listing state changed.'
                );
            }
        }

        $insertStmt = $pdo->prepare(
            "INSERT INTO tbl_genetic_market_listings (
                seller_user_id,
                genetic_item_id,
                catalog_id,
                trait_type,
                trait_value,
                item_level_snapshot,
                price_dspoinc,
                listing_status,
                created_at,
                updated_at
             ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, 'active',
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
             )"
        );

        $insertStmt->execute([
            $sellerUserId,
            $geneticItemId,
            (int)$item['catalog_id'],
            (string)$item['trait_type'],
            (string)$item['trait_value'],
            max(1, (int)$item['current_level']),
            $price,
        ]);

        $listingId = (int)$pdo->lastInsertId();

        $markStmt = $pdo->prepare(
            'UPDATE tbl_user_genetic_items
             SET
                is_listed_for_sale = 1,
                listed_listing_id = ?,
                updated_at = CURRENT_TIMESTAMP
             WHERE genetic_item_id = ?
               AND user_id = ?'
        );

        $markStmt->execute([
            $listingId,
            $geneticItemId,
            $sellerUserId,
        ]);

        if ($markStmt->rowCount() !== 1) {
            genetic_marketplace_fail(
                409,
                'item_state_conflict',
                'Genetic Item listing state changed.'
            );
        }

        genetic_marketplace_commit($pdo);

        return [
            'replayed' => false,
            'listing_id' => $listingId,
            'genetic_item_id' => $geneticItemId,
            'price_dspoinc' => $price,
            'status' => 'active',
        ];
    } catch (Throwable $error) {
        genetic_marketplace_rollback($pdo);
        throw $error;
    }
}

/** Cancel one seller-owned active Marketplace listing. */
function genetic_marketplace_cancel_listing(
    PDO $pdo,
    string $sellerUserId,
    array $input
): array {
    $listingId =
        genetic_marketplace_positive_int(
            $input['listing_id'] ?? null,
            'listing_id'
        );

    genetic_marketplace_begin_immediate($pdo);

    try {
        $listingStmt = $pdo->prepare(
            'SELECT
                listing_id,
                seller_user_id,
                genetic_item_id,
                trait_type,
                trait_value,
                item_level_snapshot,
                price_dspoinc,
                listing_status
             FROM tbl_genetic_market_listings
             WHERE listing_id = ?
             LIMIT 1'
        );

        $listingStmt->execute([$listingId]);
        $listing = $listingStmt->fetch(PDO::FETCH_ASSOC);

        if (!$listing) {
            genetic_marketplace_fail(
                404,
                'listing_not_found',
                'Marketplace listing not found.'
            );
        }

        if (
            !hash_equals(
                (string)$listing['seller_user_id'],
                $sellerUserId
            )
        ) {
            genetic_marketplace_fail(
                403,
                'seller_required',
                'You do not own this Marketplace listing.'
            );
        }

        $status = strtolower(
            trim((string)$listing['listing_status'])
        );

        if ($status === 'cancelled') {
            genetic_marketplace_commit($pdo);

            return [
                'replayed' => true,
                'listing_id' => $listingId,
                'genetic_item_id' =>
                    (int)$listing['genetic_item_id'],
                'status' => 'cancelled',
            ];
        }

        if ($status !== 'active') {
            genetic_marketplace_fail(
                409,
                'listing_not_active',
                'Only active listings can be cancelled.'
            );
        }

        $geneticItemId =
            (int)$listing['genetic_item_id'];

        $itemStmt = $pdo->prepare(
            'SELECT
                genetic_item_id,
                user_id,
                is_listed_for_sale,
                listed_listing_id
             FROM tbl_user_genetic_items
             WHERE genetic_item_id = ?
             LIMIT 1'
        );

        $itemStmt->execute([$geneticItemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        /*
         * Preserve the existing recovery-safe ghost-listing rule:
         * do not recreate a missing Genetic Item without evidence.
         * Cancel only the seller-owned stale listing row.
         */
        if (!$item) {
            $staleStmt = $pdo->prepare(
                "UPDATE tbl_genetic_market_listings
                 SET
                    listing_status = 'cancelled',
                    updated_at = CURRENT_TIMESTAMP,
                    cancelled_at = CURRENT_TIMESTAMP
                 WHERE listing_id = ?
                   AND seller_user_id = ?
                   AND listing_status = 'active'"
            );

            $staleStmt->execute([
                $listingId,
                $sellerUserId,
            ]);

            if ($staleStmt->rowCount() !== 1) {
                genetic_marketplace_fail(
                    409,
                    'listing_state_conflict',
                    'Stale Marketplace listing could not be cancelled.'
                );
            }

            genetic_marketplace_commit($pdo);

            return [
                'replayed' => false,
                'listing_id' => $listingId,
                'genetic_item_id' => $geneticItemId,
                'status' => 'cancelled',
                'stale_cleanup' => true,
            ];
        }

        if (
            !hash_equals(
                (string)$item['user_id'],
                $sellerUserId
            )
        ) {
            genetic_marketplace_fail(
                409,
                'item_owner_mismatch',
                'Owned Genetic Item no longer belongs to this seller.'
            );
        }

        if (
            (int)$item['is_listed_for_sale'] !== 1 ||
            (int)($item['listed_listing_id'] ?? 0) !==
                $listingId
        ) {
            genetic_marketplace_fail(
                409,
                'item_listing_mismatch',
                'Genetic Item listing state does not match this listing.'
            );
        }

        $cancelStmt = $pdo->prepare(
            "UPDATE tbl_genetic_market_listings
             SET
                listing_status = 'cancelled',
                updated_at = CURRENT_TIMESTAMP,
                cancelled_at = CURRENT_TIMESTAMP
             WHERE listing_id = ?
               AND seller_user_id = ?
               AND listing_status = 'active'"
        );

        $cancelStmt->execute([
            $listingId,
            $sellerUserId,
        ]);

        if ($cancelStmt->rowCount() !== 1) {
            genetic_marketplace_fail(
                409,
                'listing_state_conflict',
                'Marketplace listing could not be cancelled.'
            );
        }

        $unlistStmt = $pdo->prepare(
            'UPDATE tbl_user_genetic_items
             SET
                is_listed_for_sale = 0,
                listed_listing_id = NULL,
                updated_at = CURRENT_TIMESTAMP
             WHERE genetic_item_id = ?
               AND user_id = ?
               AND is_listed_for_sale = 1
               AND listed_listing_id = ?'
        );

        $unlistStmt->execute([
            $geneticItemId,
            $sellerUserId,
            $listingId,
        ]);

        if ($unlistStmt->rowCount() !== 1) {
            genetic_marketplace_fail(
                409,
                'item_state_conflict',
                'Genetic Item could not be restored to inventory.'
            );
        }

        genetic_marketplace_commit($pdo);

        return [
            'replayed' => false,
            'listing_id' => $listingId,
            'genetic_item_id' => $geneticItemId,
            'trait_type' =>
                (string)$listing['trait_type'],
            'trait_value' =>
                (string)$listing['trait_value'],
            'current_level' =>
                max(
                    1,
                    (int)$listing['item_level_snapshot']
                ),
            'price_dspoinc' =>
                (int)$listing['price_dspoinc'],
            'status' => 'cancelled',
            'is_listed_for_sale' => 0,
        ];
    } catch (Throwable $error) {
        genetic_marketplace_rollback($pdo);
        throw $error;
    }
}

/** Buy one active listing and atomically settle DSPOINC + Genetic ownership. */
function genetic_marketplace_buy_listing(
    PDO $pdo,
    string $buyerUserId,
    array $input
): array {
    $listingId =
        genetic_marketplace_positive_int(
            $input['listing_id'] ?? null,
            'listing_id'
        );

    genetic_marketplace_begin_immediate($pdo);

    try {
        $listingStmt = $pdo->prepare(
            'SELECT
                l.listing_id,
                l.genetic_item_id,
                l.seller_user_id,
                l.buyer_user_id,
                l.catalog_id,
                l.trait_type,
                l.trait_value,
                l.item_level_snapshot,
                l.price_dspoinc,
                l.listing_status,
                l.created_at,
                l.updated_at,
                l.sold_at,
                c.display_title
             FROM tbl_genetic_market_listings AS l
             LEFT JOIN tbl_genetic_trait_catalog AS c
                ON c.catalog_id = l.catalog_id
             WHERE l.listing_id = ?
             LIMIT 1'
        );

        $listingStmt->execute([$listingId]);
        $listing = $listingStmt->fetch(PDO::FETCH_ASSOC);

        if (!$listing) {
            genetic_marketplace_fail(
                404,
                'listing_not_found',
                'Marketplace listing not found.'
            );
        }

        $status = strtolower(
            trim((string)$listing['listing_status'])
        );

        if (
            $status === 'sold' &&
            hash_equals(
                (string)($listing['buyer_user_id'] ?? ''),
                $buyerUserId
            )
        ) {
            $remaining =
                genetic_marketplace_available_dspoinc(
                    $pdo,
                    $buyerUserId
                );

            genetic_marketplace_commit($pdo);

            return [
                'replayed' => true,
                'listing_id' => $listingId,
                'genetic_item_id' =>
                    (int)$listing['genetic_item_id'],
                'seller_user_id' =>
                    (string)$listing['seller_user_id'],
                'buyer_user_id' => $buyerUserId,
                'price_paid_dspoinc' =>
                    (int)$listing['price_dspoinc'],
                'remaining_balance_dspoinc' =>
                    max(0, $remaining),
                'listing_status' => 'sold',
            ];
        }

        if ($status !== 'active') {
            genetic_marketplace_fail(
                409,
                'listing_not_active',
                'This Marketplace listing is no longer active.'
            );
        }

        $sellerUserId = trim(
            (string)$listing['seller_user_id']
        );

        $geneticItemId =
            (int)$listing['genetic_item_id'];

        $catalogId =
            (int)$listing['catalog_id'];

        $traitType = trim(
            (string)$listing['trait_type']
        );

        $traitValue = trim(
            (string)$listing['trait_value']
        );

        $displayTitle = trim(
            (string)(
                $listing['display_title'] ??
                $traitValue
            )
        );

        $price =
            (int)$listing['price_dspoinc'];

        if (
            $sellerUserId === '' ||
            $geneticItemId < 1 ||
            $traitType === '' ||
            $traitValue === '' ||
            $price < 1
        ) {
            genetic_marketplace_fail(
                500,
                'listing_malformed',
                'Marketplace listing is malformed.'
            );
        }

        if (hash_equals($sellerUserId, $buyerUserId)) {
            genetic_marketplace_fail(
                409,
                'own_listing',
                'You cannot buy your own Marketplace listing.'
            );
        }

        if (
            genetic_marketplace_exact_trait_count(
                $pdo,
                $buyerUserId,
                $traitType,
                $traitValue
            ) >= GENETIC_MARKETPLACE_MAX_EXACT_COPIES
        ) {
            genetic_marketplace_fail(
                409,
                'exact_trait_limit',
                'You already own the maximum 2 copies of this Genetic trait.',
                [
                    'trait_type' => $traitType,
                    'trait_value' => $traitValue,
                    'listing_id' => $listingId,
                ]
            );
        }

        $available =
            genetic_marketplace_available_dspoinc(
                $pdo,
                $buyerUserId
            );

        if ($available < $price) {
            genetic_marketplace_fail(
                409,
                'insufficient_dspoinc',
                'Not enough DSPOINC.',
                [
                    'price_dspoinc' => $price,
                    'balance_dspoinc' =>
                        max(0, $available),
                    'missing_dspoinc' =>
                        max(0, $price - $available),
                ]
            );
        }

        $itemStmt = $pdo->prepare(
            'SELECT
                genetic_item_id,
                user_id,
                catalog_id,
                trait_type,
                trait_value,
                current_level,
                upgrade_status,
                upgrade_started_at,
                upgrade_ends_at,
                acquired_method,
                is_listed_for_sale,
                listed_listing_id
             FROM tbl_user_genetic_items
             WHERE genetic_item_id = ?
             LIMIT 1'
        );

        $itemStmt->execute([$geneticItemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            genetic_marketplace_fail(
                404,
                'marketplace_item_missing',
                'Marketplace item no longer exists.'
            );
        }

        if (
            !hash_equals(
                (string)$item['user_id'],
                $sellerUserId
            )
        ) {
            genetic_marketplace_fail(
                409,
                'item_owner_mismatch',
                'Marketplace item owner mismatch detected.'
            );
        }

        if (
            (int)$item['catalog_id'] !== $catalogId ||
            (string)$item['trait_type'] !== $traitType ||
            (string)$item['trait_value'] !== $traitValue
        ) {
            genetic_marketplace_fail(
                409,
                'item_identity_mismatch',
                'Marketplace item identity mismatch detected.'
            );
        }

        if (
            (int)$item['is_listed_for_sale'] !== 1 ||
            (int)($item['listed_listing_id'] ?? 0) !==
                $listingId
        ) {
            genetic_marketplace_fail(
                409,
                'item_listing_mismatch',
                'Marketplace item listing reference mismatch detected.'
            );
        }

        $currentLevel =
            max(1, (int)$item['current_level']);

        $upgradeStatus =
            trim((string)$item['upgrade_status']);

        $upgradeStartedAt =
            (string)($item['upgrade_started_at'] ?? '');

        $upgradeEndsAt =
            (string)($item['upgrade_ends_at'] ?? '');

        genetic_marketplace_insert_dspoinc(
            $pdo,
            $sellerUserId,
            $price,
            'genetic_marketplace_sale',
            'marketplace_sale [listing_id=' .
                $listingId .
                ']'
        );

        genetic_marketplace_insert_adjustment(
            $pdo,
            $sellerUserId,
            $buyerUserId,
            $price,
            'add',
            'Marketplace sale: ' .
                $displayTitle .
                ' (listing_id: ' .
                $listingId .
                ', buyer: ' .
                $buyerUserId .
                ')'
        );

        genetic_marketplace_insert_dspoinc(
            $pdo,
            $buyerUserId,
            -$price,
            'genetic_marketplace_purchase',
            'marketplace_purchase [listing_id=' .
                $listingId .
                ']'
        );

        genetic_marketplace_insert_adjustment(
            $pdo,
            $buyerUserId,
            $sellerUserId,
            -$price,
            'remove',
            'Marketplace purchase: ' .
                $displayTitle .
                ' (listing_id: ' .
                $listingId .
                ', seller: ' .
                $sellerUserId .
                ')'
        );

        $transferStmt = $pdo->prepare(
            "UPDATE tbl_user_genetic_items
             SET
                user_id = ?,
                is_listed_for_sale = 0,
                listed_listing_id = NULL,
                last_transfer_at = CURRENT_TIMESTAMP,
                last_owner_user_id = ?,
                acquired_method = 'marketplace',
                updated_at = CURRENT_TIMESTAMP
             WHERE genetic_item_id = ?
               AND user_id = ?
               AND is_listed_for_sale = 1
               AND listed_listing_id = ?"
        );

        $transferStmt->execute([
            $buyerUserId,
            $sellerUserId,
            $geneticItemId,
            $sellerUserId,
            $listingId,
        ]);

        if ($transferStmt->rowCount() !== 1) {
            genetic_marketplace_fail(
                409,
                'ownership_transfer_failed',
                'Ownership transfer failed.'
            );
        }

        $finishStmt = $pdo->prepare(
            "UPDATE tbl_genetic_market_listings
             SET
                buyer_user_id = ?,
                listing_status = 'sold',
                sold_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
             WHERE listing_id = ?
               AND seller_user_id = ?
               AND listing_status = 'active'"
        );

        $finishStmt->execute([
            $buyerUserId,
            $listingId,
            $sellerUserId,
        ]);

        if ($finishStmt->rowCount() !== 1) {
            genetic_marketplace_fail(
                409,
                'listing_finalization_failed',
                'Listing finalization failed.'
            );
        }

        $remaining =
            genetic_marketplace_available_dspoinc(
                $pdo,
                $buyerUserId
            );

        if ($remaining < 0) {
            genetic_marketplace_fail(
                500,
                'purchase_verification_failed',
                'Purchase verification failed.'
            );
        }

        genetic_marketplace_insert_history(
            $pdo,
            $geneticItemId,
            $listingId,
            $sellerUserId,
            'marketplace_sale',
            [
                'user_id' => $sellerUserId,
                'listing_status' => 'active',
                'is_listed_for_sale' => 1,
                'listed_listing_id' => $listingId,
                'current_level' => $currentLevel,
                'upgrade_status' => $upgradeStatus,
                'upgrade_started_at' =>
                    $upgradeStartedAt,
                'upgrade_ends_at' =>
                    $upgradeEndsAt,
            ],
            [
                'user_id' => $buyerUserId,
                'listing_status' => 'sold',
                'price_dspoinc' => $price,
                'current_level' => $currentLevel,
                'upgrade_status' => $upgradeStatus,
                'upgrade_started_at' =>
                    $upgradeStartedAt,
                'upgrade_ends_at' =>
                    $upgradeEndsAt,
            ]
        );

        genetic_marketplace_insert_history(
            $pdo,
            $geneticItemId,
            $listingId,
            $buyerUserId,
            'marketplace_purchase',
            [
                'seller_user_id' =>
                    $sellerUserId,
                'price_dspoinc' => $price,
            ],
            [
                'buyer_user_id' =>
                    $buyerUserId,
                'catalog_id' => $catalogId,
                'trait_type' => $traitType,
                'trait_value' => $traitValue,
                'display_title' => $displayTitle,
                'price_paid_dspoinc' => $price,
                'remaining_balance_dspoinc' =>
                    $remaining,
                'current_level' => $currentLevel,
                'upgrade_status' => $upgradeStatus,
                'upgrade_started_at' =>
                    $upgradeStartedAt,
                'upgrade_ends_at' =>
                    $upgradeEndsAt,
            ]
        );

        genetic_marketplace_commit($pdo);

        return [
            'replayed' => false,
            'listing_id' => $listingId,
            'genetic_item_id' => $geneticItemId,
            'catalog_id' => $catalogId,
            'seller_user_id' => $sellerUserId,
            'buyer_user_id' => $buyerUserId,
            'trait_type' => $traitType,
            'trait_value' => $traitValue,
            'display_title' => $displayTitle,
            'price_paid_dspoinc' => $price,
            'remaining_balance_dspoinc' =>
                $remaining,
            'current_level' => $currentLevel,
            'upgrade_status' => $upgradeStatus,
            'upgrade_started_at' =>
                $upgradeStartedAt,
            'upgrade_ends_at' =>
                $upgradeEndsAt,
            'listing_status' => 'sold',
        ];
    } catch (Throwable $error) {
        genetic_marketplace_rollback($pdo);
        throw $error;
    }
}
