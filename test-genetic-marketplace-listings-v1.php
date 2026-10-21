<?php
declare(strict_types=1);

define('NARRRFS_GENETIC_LISTINGS_TEST_MODE', true);
require __DIR__ . '/api/marketplace/v1/genetic-listings.php';

function marketplace_test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$dispatchCount = 0;
$rejectLoader = static function () use (&$dispatchCount): array {
    $dispatchCount++;
    return [];
};

foreach (['HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
    $response = genetic_listings_handle($method, [], $rejectLoader);
    marketplace_test_assert($response['status'] === 405, $method . ' must return 405');
    marketplace_test_assert($response['headers'] === ['Allow: GET'], $method . ' must advertise GET only');
}
marketplace_test_assert($dispatchCount === 0, 'Rejected methods must not dispatch database work');

$fixtureRows = [
    [
        'listing_id' => 41,
        'display_title' => 'Mouse DNA',
        'trait_type' => 'Outfit',
        'trait_value' => 'Lab Coat',
        'rarity_tier' => 'rare',
        'image_type' => 'png',
        'image_path' => '/public/img/lab-coat.png',
        'preview_path' => 'public/img/lab-coat-preview.png',
        'price_dspoinc' => 999,
        'seller_user_id' => 'private',
        'wallet' => 'private',
        'genetic_item_id' => 777,
        'inventory_state' => 'private',
        'audit_data' => 'private',
    ],
];

$response = genetic_listings_handle('GET', ['limit' => '24'], static fn (): array => $fixtureRows);
marketplace_test_assert($response['status'] === 200, 'GET must return the catalog response');
marketplace_test_assert(array_keys($response['payload']) === ['success', 'data'], 'Top-level allowlist mismatch');
marketplace_test_assert(array_keys($response['payload']['data']) === ['listings', 'page'], 'Data allowlist mismatch');

$listing = $response['payload']['data']['listings'][0];
$expectedListingKeys = [
    'listing_ref', 'display_title', 'trait_type', 'trait_value', 'rarity_tier',
    'image_type', 'image_path', 'preview_path',
];
marketplace_test_assert(array_keys($listing) === $expectedListingKeys, 'Listing field allowlist mismatch');
marketplace_test_assert(array_keys($response['payload']['data']['page']) === ['limit', 'next_cursor', 'has_more'], 'Page allowlist mismatch');

$encoded = json_encode($response['payload']);
foreach (['price', 'balance', 'wallet', 'seller', 'owner', 'buyer', 'discord', 'genetic_item_id', 'inventory', 'audit', 'purchase', 'settlement', 'reward', 'transaction'] as $excluded) {
    marketplace_test_assert(stripos((string)$encoded, $excluded) === false, 'Excluded field leaked: ' . $excluded);
}

$unsafeMedia = genetic_listings_project_row([
    'listing_id' => 42,
    'image_path' => 'https://private.example/signed?id=1',
    'preview_path' => '../private/file.png',
]);
marketplace_test_assert($unsafeMedia['image_path'] === '' && $unsafeMedia['preview_path'] === '', 'Unsafe media must be blank');

$positiveMediaPaths = [
    '/public/three.js/public/genetic/accessories/angel-wings.glb',
    '/public/three.js/public/genetic/previews/accessories/angel-wings.png',
    '/public/img/genetic/glb/accessories/angel-wings.glb',
    '/public/img/genetic/previews/accessories/angel-wings.webp',
];
foreach ($positiveMediaPaths as $path) {
    marketplace_test_assert(genetic_listings_public_asset($path) === $path, 'Verified Genetic media path rejected: ' . $path);
}
marketplace_test_assert(
    genetic_listings_public_asset('img/genetic/previews/accessories/angel-wings.png') === '/public/img/genetic/previews/accessories/angel-wings.png',
    'Verified relative Genetic preview path must normalize into the public Genetic namespace'
);

$protectedOrUnsafeMediaPaths = [
    '/api/user/details.php',
    '/db/narrrf_world.sqlite',
    '/admin-interface.html',
    '/public/admin/manage-scores.html',
    '/api/owner-control/v1/canary.php',
    '/public/img/genetic/previews/accessories/payload.svg',
    '/public/img/genetic/previews/accessories/payload.html',
    '/public/img/genetic/glb/accessories/model.php',
    '/public/img/genetic/previews/accessories/%2e%2e%2fsecret.png',
    '/public/img/genetic/previews/accessories/image.png%3fadmin=1',
    '/public/img/genetic/previews//double-slash.png',
];
foreach ($protectedOrUnsafeMediaPaths as $path) {
    marketplace_test_assert(genetic_listings_public_asset($path) === '', 'Protected or unsafe media path accepted: ' . $path);
}

$unknown = genetic_listings_handle('GET', ['admin' => '1'], static fn (): array => []);
marketplace_test_assert($unknown['status'] === 400, 'Unknown parameters must fail closed');
$badLimit = genetic_listings_handle('GET', ['limit' => '49'], static fn (): array => []);
marketplace_test_assert($badLimit['status'] === 400, 'Out-of-range limit must fail closed');
$badCursor = genetic_listings_handle('GET', ['cursor' => 'not-valid!'], static fn (): array => []);
marketplace_test_assert($badCursor['status'] === 400, 'Invalid cursor must fail closed');

$source = file_get_contents(__DIR__ . '/api/marketplace/v1/genetic-listings.php');
marketplace_test_assert(is_string($source), 'Endpoint source must be readable');
marketplace_test_assert(preg_match_all('/^SELECT$/m', $source) === 1, 'Endpoint must contain exactly one SELECT statement');
marketplace_test_assert(str_contains($source, 'SQLITE3_OPEN_READONLY'), 'SQLite connection must be read-only');
marketplace_test_assert(!preg_match('/\b(?:INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|CREATE|ATTACH|DETACH|BEGIN|COMMIT|ROLLBACK)\b\s+/i', $source), 'Write or transaction SQL found');
marketplace_test_assert(!str_contains($source, '/api/admin/'), 'Endpoint must not dispatch into admin routes');
marketplace_test_assert(!str_contains($source, 'session_start'), 'Endpoint must not start a session');

echo "PASS: Genetic Marketplace metadata endpoint contract and no-write checks\n";

