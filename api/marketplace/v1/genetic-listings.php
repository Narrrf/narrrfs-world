<?php
/**
 * Public Genetic Marketplace metadata catalog.
 *
 * DEVS FOR DECADES: this endpoint is deliberately isolated from authenticated
 * Marketplace actions. It exposes a fixed presentation projection and opens
 * SQLite read-only only after the request method and query are accepted.
 */

declare(strict_types=1);

const GENETIC_LISTINGS_DEFAULT_LIMIT = 24;
const GENETIC_LISTINGS_MAX_LIMIT = 48;
const GENETIC_LISTINGS_MAX_OFFSET = 10000;

/** Return the generic error envelope without internal diagnostic details. */
function genetic_listings_error(string $code, string $message): array
{
    return [
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message,
        ],
    ];
}

/** Encode a bounded catalog offset as an opaque pagination cursor. */
function genetic_listings_encode_cursor(int $offset): string
{
    return rtrim(strtr(base64_encode('v1:' . $offset), '+/', '-_'), '=');
}

/** Decode and strictly validate a pagination cursor. */
function genetic_listings_decode_cursor(string $cursor): ?int
{
    if ($cursor === '' || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $cursor)) {
        return null;
    }

    $padding = (4 - (strlen($cursor) % 4)) % 4;
    $decoded = base64_decode(strtr($cursor . str_repeat('=', $padding), '-_', '+/'), true);
    if (!is_string($decoded) || !preg_match('/^v1:(0|[1-9][0-9]{0,4})$/', $decoded, $matches)) {
        return null;
    }

    $offset = (int)$matches[1];
    return $offset <= GENETIC_LISTINGS_MAX_OFFSET ? $offset : null;
}

/** Validate the only two supported query parameters. */
function genetic_listings_parse_query(array $query): array
{
    $unknown = array_diff(array_keys($query), ['limit', 'cursor']);
    if ($unknown !== []) {
        throw new InvalidArgumentException('unknown_parameter');
    }

    $limit = GENETIC_LISTINGS_DEFAULT_LIMIT;
    if (array_key_exists('limit', $query)) {
        if (is_array($query['limit']) || !preg_match('/^(?:[1-9]|[1-3][0-9]|4[0-8])$/', (string)$query['limit'])) {
            throw new InvalidArgumentException('invalid_parameter');
        }
        $limit = (int)$query['limit'];
    }

    $offset = 0;
    if (array_key_exists('cursor', $query)) {
        if (is_array($query['cursor'])) {
            throw new InvalidArgumentException('invalid_parameter');
        }
        $decodedOffset = genetic_listings_decode_cursor((string)$query['cursor']);
        if ($decodedOffset === null) {
            throw new InvalidArgumentException('invalid_parameter');
        }
        $offset = $decodedOffset;
    }

    return ['limit' => $limit, 'offset' => $offset];
}

/**
 * Keep only verified Genetic catalog asset namespaces and media extensions.
 * Encoded or ambiguous paths are rejected instead of being normalized.
 */
function genetic_listings_public_asset(string $value): string
{
    $value = trim($value);
    if ($value === '' || !preg_match('/^[\x20-\x7E]+$/', $value) ||
        str_contains($value, '%') || str_contains($value, '://') || str_contains($value, '..') ||
        str_contains($value, '\\') || str_contains($value, '?') || str_contains($value, '#') ||
        str_contains($value, '//')) {
        return '';
    }

    if (str_starts_with($value, 'img/genetic/')) {
        $value = '/public/' . $value;
    }

    $safePathPart = '[A-Za-z0-9][A-Za-z0-9._-]*(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*';
    $allowedPatterns = [
        '#^/public/img/genetic/glb/' . $safePathPart . '\.glb$#i',
        '#^/public/img/genetic/previews/' . $safePathPart . '\.(?:png|jpe?g|webp|gif)$#i',
        '#^/public/three\.js/public/genetic/(?!previews/)' . $safePathPart . '\.glb$#i',
        '#^/public/three\.js/public/genetic/previews/' . $safePathPart . '\.(?:png|jpe?g|webp|gif)$#i',
    ];

    foreach ($allowedPatterns as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return $value;
        }
    }

    return '';
}

/** Convert one internal query row to the exact public field allowlist. */
function genetic_listings_project_row(array $row): array
{
    $listingId = (string)($row['listing_id'] ?? '');

    return [
        'listing_ref' => 'gl_' . substr(hash('sha256', 'genetic-listing-v1:' . $listingId), 0, 20),
        'display_title' => (string)($row['display_title'] ?? $row['trait_value'] ?? ''),
        'trait_type' => (string)($row['trait_type'] ?? ''),
        'trait_value' => (string)($row['trait_value'] ?? ''),
        'rarity_tier' => (string)($row['rarity_tier'] ?? 'common'),
        'image_type' => (string)($row['image_type'] ?? ''),
        'image_path' => genetic_listings_public_asset((string)($row['image_path'] ?? '')),
        'preview_path' => genetic_listings_public_asset((string)($row['preview_path'] ?? '')),
    ];
}

/** Resolve the existing SQLite file without creating or changing it. */
function genetic_listings_database_path(): string
{
    $candidates = [
        dirname(__DIR__, 3) . '/db/narrrf_world.sqlite',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/db/narrrf_world.sqlite',
        '/var/www/html/db/narrrf_world.sqlite',
        '/data/narrrf_world.sqlite',
    ];

    foreach ($candidates as $candidate) {
        if ($candidate !== '' && is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }

    throw new RuntimeException('catalog unavailable');
}

/** Execute the endpoint's single bounded, read-only catalog projection. */
function genetic_listings_load_rows(int $offset, int $fetchLimit): array
{
    $database = new SQLite3(genetic_listings_database_path(), SQLITE3_OPEN_READONLY);
    $statement = $database->prepare(<<<'SQL'
SELECT
    l.listing_id,
    l.trait_type,
    l.trait_value,
    c.display_title,
    c.rarity_tier,
    c.image_type,
    c.image_path,
    c.preview_path
FROM tbl_genetic_market_listings AS l
INNER JOIN tbl_user_genetic_items AS i
    ON i.genetic_item_id = l.genetic_item_id
    AND CAST(i.user_id AS TEXT) = CAST(l.seller_user_id AS TEXT)
    AND COALESCE(i.is_listed_for_sale, 0) = 1
    AND COALESCE(i.listed_listing_id, 0) = l.listing_id
LEFT JOIN tbl_genetic_trait_catalog AS c
    ON c.catalog_id = l.catalog_id
WHERE l.listing_status = 'active'
ORDER BY l.created_at DESC, l.listing_id DESC
LIMIT :fetch_limit OFFSET :row_offset
SQL);

    if (!$statement) {
        $database->close();
        throw new RuntimeException('catalog unavailable');
    }

    $statement->bindValue(':fetch_limit', $fetchLimit, SQLITE3_INTEGER);
    $statement->bindValue(':row_offset', $offset, SQLITE3_INTEGER);
    $result = $statement->execute();
    if (!$result) {
        $statement->close();
        $database->close();
        throw new RuntimeException('catalog unavailable');
    }

    $rows = [];
    while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
        $rows[] = $row;
    }

    $result->finalize();
    $statement->close();
    $database->close();
    return $rows;
}

/** Build a response while keeping rejected methods ahead of data dispatch. */
function genetic_listings_handle(string $method, array $query, callable $loader): array
{
    if ($method !== 'GET') {
        return [
            'status' => 405,
            'headers' => ['Allow: GET'],
            'payload' => genetic_listings_error('method_not_allowed', 'Method not allowed.'),
        ];
    }

    try {
        $request = genetic_listings_parse_query($query);
    } catch (InvalidArgumentException $exception) {
        $code = $exception->getMessage() === 'unknown_parameter' ? 'unknown_parameter' : 'invalid_parameter';
        return [
            'status' => 400,
            'headers' => [],
            'payload' => genetic_listings_error($code, 'Request parameters are invalid.'),
        ];
    }

    try {
        $rows = $loader($request['offset'], $request['limit'] + 1);
        $hasMore = count($rows) > $request['limit'];
        $visibleRows = array_slice($rows, 0, $request['limit']);

        return [
            'status' => 200,
            'headers' => [],
            'payload' => [
                'success' => true,
                'data' => [
                    'listings' => array_map('genetic_listings_project_row', $visibleRows),
                    'page' => [
                        'limit' => $request['limit'],
                        'next_cursor' => $hasMore
                            ? genetic_listings_encode_cursor($request['offset'] + $request['limit'])
                            : null,
                        'has_more' => $hasMore,
                    ],
                ],
            ],
        ];
    } catch (Throwable $exception) {
        error_log('Genetic listing catalog unavailable');
        return [
            'status' => 500,
            'headers' => [],
            'payload' => genetic_listings_error('listing_catalog_unavailable', 'Marketplace listings are unavailable.'),
        ];
    }
}

/** Emit the HTTP response for direct endpoint execution. */
function genetic_listings_run(): void
{
    $response = genetic_listings_handle(
        (string)($_SERVER['REQUEST_METHOD'] ?? ''),
        $_GET,
        'genetic_listings_load_rows'
    );

    http_response_code($response['status']);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    foreach ($response['headers'] as $headerValue) {
        header($headerValue);
    }
    echo json_encode($response['payload'], JSON_UNESCAPED_SLASHES);
}

if (!defined('NARRRFS_GENETIC_LISTINGS_TEST_MODE')) {
    genetic_listings_run();
}

