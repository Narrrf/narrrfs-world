<?php

declare(strict_types=1);

require_once __DIR__ . '/api/lib/genetic-marketplace-v1.php';

function test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function test_value(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

$pdo = new PDO('sqlite::memory:');

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$schema = [
    "CREATE TABLE tbl_users (
        discord_id TEXT PRIMARY KEY,
        username TEXT,
        avatar_url TEXT
    )",

    "CREATE TABLE tbl_genetic_trait_catalog (
        catalog_id INTEGER PRIMARY KEY,
        trait_type TEXT NOT NULL,
        trait_value TEXT NOT NULL,
        display_title TEXT,
        rarity_tier TEXT,
        image_type TEXT,
        image_path TEXT,
        preview_path TEXT
    )",

    "CREATE TABLE tbl_user_genetic_items (
        genetic_item_id INTEGER PRIMARY KEY,
        user_id TEXT NOT NULL,
        catalog_id INTEGER NOT NULL,
        trait_type TEXT NOT NULL,
        trait_value TEXT NOT NULL,
        current_level INTEGER NOT NULL DEFAULT 1,
        upgrade_status TEXT NOT NULL DEFAULT 'idle',
        upgrade_started_at TEXT,
        upgrade_ends_at TEXT,
        last_completed_at TEXT,
        ready_claim_notified_at TEXT,
        ready_claim_notification_count INTEGER NOT NULL DEFAULT 0,
        active_booster_item_id INTEGER,
        active_booster_used_at TEXT,
        acquired_method TEXT NOT NULL DEFAULT 'shop',
        is_listed_for_sale INTEGER NOT NULL DEFAULT 0,
        listed_listing_id INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        last_transfer_at TEXT,
        last_owner_user_id TEXT NOT NULL DEFAULT ''
    )",

    "CREATE TABLE tbl_genetic_market_listings (
        listing_id INTEGER PRIMARY KEY AUTOINCREMENT,
        genetic_item_id INTEGER NOT NULL,
        seller_user_id TEXT NOT NULL,
        buyer_user_id TEXT,
        catalog_id INTEGER NOT NULL,
        trait_type TEXT NOT NULL,
        trait_value TEXT NOT NULL,
        item_level_snapshot INTEGER NOT NULL DEFAULT 1,
        price_dspoinc INTEGER NOT NULL,
        listing_status TEXT NOT NULL DEFAULT 'active',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        sold_at TEXT,
        cancelled_at TEXT,
        removed_by_admin_user_id TEXT
    )",

    "CREATE TABLE tbl_user_scores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT,
        game TEXT,
        score INTEGER,
        timestamp TEXT DEFAULT CURRENT_TIMESTAMP,
        source TEXT DEFAULT 'legacy',
        game_type TEXT,
        season TEXT DEFAULT 'season_2',
        created_at TEXT
    )",

    "CREATE TABLE tbl_score_adjustments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT NOT NULL,
        admin_id TEXT NOT NULL,
        amount INTEGER NOT NULL,
        action TEXT NOT NULL,
        reason TEXT NOT NULL,
        timestamp TEXT DEFAULT CURRENT_TIMESTAMP,
        quest_id INTEGER
    )",

    "CREATE TABLE tbl_dspoinc_stakes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT NOT NULL,
        amount INTEGER NOT NULL,
        status TEXT
    )",

    "CREATE TABLE tbl_genetic_item_history (
        history_id INTEGER PRIMARY KEY AUTOINCREMENT,
        genetic_item_id INTEGER,
        listing_id INTEGER,
        user_id TEXT NOT NULL,
        action_type TEXT NOT NULL,
        old_value_json TEXT NOT NULL DEFAULT '{}',
        new_value_json TEXT NOT NULL DEFAULT '{}',
        admin_user_id TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($schema as $statement) {
    $pdo->exec($statement);
}

$seller = '111111111111111111';
$buyer = '222222222222222222';

$pdo->prepare(
    'INSERT INTO tbl_users (
        discord_id,
        username,
        avatar_url
     ) VALUES (?, ?, ?), (?, ?, ?)'
)->execute([
    $seller,
    'SellerMouse',
    '',
    $buyer,
    'BuyerMouse',
    ''
]);

$pdo->prepare(
    "INSERT INTO tbl_genetic_trait_catalog (
        catalog_id,
        trait_type,
        trait_value,
        display_title,
        rarity_tier,
        image_type,
        image_path,
        preview_path
     ) VALUES
        (
            1,
            'Accessories',
            'Cheese',
            'Cheese',
            'rare',
            'glb',
            '/public/three.js/public/genetic/accessories/cheese.glb',
            '/public/three.js/public/genetic/previews/accessories/cheese.png'
        ),
        (
            2,
            'Outfit',
            'Golden Suit',
            'Golden Suit',
            'epic',
            'glb',
            '/public/three.js/public/genetic/outfits/golden-suit.glb',
            '/public/three.js/public/genetic/previews/outfits/golden-suit.png'
        )"
);

/* CREATE + replay + CANCEL */

$pdo->prepare(
    "INSERT INTO tbl_user_genetic_items (
        genetic_item_id,
        user_id,
        catalog_id,
        trait_type,
        trait_value,
        current_level,
        upgrade_status
     ) VALUES (?, ?, 1, 'Accessories', 'Cheese', 4, 'idle')"
)->execute([
    100,
    $seller
]);

$created = genetic_marketplace_create_listing(
    $pdo,
    $seller,
    [
        'genetic_item_id' => 100,
        'price_dspoinc' => 125
    ]
);

test_assert(
    $created['replayed'] === false,
    'first create must not be replayed'
);

$listing100 = (int)$created['listing_id'];

test_assert(
    (int)test_value(
        $pdo,
        'SELECT is_listed_for_sale
         FROM tbl_user_genetic_items
         WHERE genetic_item_id = 100'
    ) === 1,
    'create must mark item listed'
);

$createReplay = genetic_marketplace_create_listing(
    $pdo,
    $seller,
    [
        'genetic_item_id' => 100,
        'price_dspoinc' => 125
    ]
);

test_assert(
    $createReplay['replayed'] === true,
    'same create retry must replay safely'
);

test_assert(
    (int)$createReplay['listing_id'] === $listing100,
    'create retry must return same listing'
);

$context = genetic_marketplace_player_context(
    $pdo,
    $seller
);

test_assert(
    count($context['listings']) === 1,
    'player context must return active listing'
);

test_assert(
    $context['listings'][0]['is_own_listing'] === true,
    'seller listing must be marked own listing'
);

test_assert(
    !array_key_exists(
        'seller_user_id',
        $context['listings'][0]
    ),
    'player context must not expose seller Discord ID'
);

$cancelled = genetic_marketplace_cancel_listing(
    $pdo,
    $seller,
    [
        'listing_id' => $listing100
    ]
);

test_assert(
    $cancelled['replayed'] === false,
    'first cancel must not be replayed'
);

test_assert(
    test_value(
        $pdo,
        'SELECT listing_status
         FROM tbl_genetic_market_listings
         WHERE listing_id = ?',
        [$listing100]
    ) === 'cancelled',
    'cancel must preserve cancelled listing row'
);

test_assert(
    (int)test_value(
        $pdo,
        'SELECT is_listed_for_sale
         FROM tbl_user_genetic_items
         WHERE genetic_item_id = 100'
    ) === 0,
    'cancel must restore inventory listing state'
);

$cancelReplay = genetic_marketplace_cancel_listing(
    $pdo,
    $seller,
    [
        'listing_id' => $listing100
    ]
);

test_assert(
    $cancelReplay['replayed'] === true,
    'cancel retry must replay safely'
);

/* BUY + DSPOINC + transfer + replay */

$pdo->prepare(
    "INSERT INTO tbl_user_genetic_items (
        genetic_item_id,
        user_id,
        catalog_id,
        trait_type,
        trait_value,
        current_level,
        upgrade_status
     ) VALUES (?, ?, 2, 'Outfit', 'Golden Suit', 7, 'idle')"
)->execute([
    200,
    $seller
]);

$pdo->prepare(
    "INSERT INTO tbl_user_scores (
        user_id,
        game,
        score,
        source
     ) VALUES (?, 'seed', 1000, 'test')"
)->execute([$buyer]);

$pdo->prepare(
    "INSERT INTO tbl_dspoinc_stakes (
        user_id,
        amount,
        status
     ) VALUES
        (?, 200, 'active'),
        (?, 100, 'frozen')"
)->execute([
    $buyer,
    $buyer
]);

$buyListing = genetic_marketplace_create_listing(
    $pdo,
    $seller,
    [
        'genetic_item_id' => 200,
        'price_dspoinc' => 250
    ]
);

$listing200 = (int)$buyListing['listing_id'];

test_assert(
    genetic_marketplace_available_dspoinc(
        $pdo,
        $buyer
    ) === 700,
    'available balance must subtract active + frozen stakes'
);

$bought = genetic_marketplace_buy_listing(
    $pdo,
    $buyer,
    [
        'listing_id' => $listing200
    ]
);

test_assert(
    $bought['replayed'] === false,
    'first buy must not be replayed'
);

test_assert(
    $bought['remaining_balance_dspoinc'] === 450,
    'buyer remaining spendable balance must be 450'
);

test_assert(
    test_value(
        $pdo,
        'SELECT user_id
         FROM tbl_user_genetic_items
         WHERE genetic_item_id = 200'
    ) === $buyer,
    'buyer must own transferred item'
);

test_assert(
    test_value(
        $pdo,
        'SELECT listing_status
         FROM tbl_genetic_market_listings
         WHERE listing_id = ?',
        [$listing200]
    ) === 'sold',
    'listing must finalize sold'
);

test_assert(
    test_value(
        $pdo,
        'SELECT buyer_user_id
         FROM tbl_genetic_market_listings
         WHERE listing_id = ?',
        [$listing200]
    ) === $buyer,
    'sold listing must record buyer'
);

test_assert(
    (int)test_value(
        $pdo,
        "SELECT COALESCE(SUM(score), 0)
         FROM tbl_user_scores
         WHERE user_id = ?
           AND game = 'genetic_marketplace_sale'",
        [$seller]
    ) === 250,
    'seller must receive exactly 250 DSPOINC'
);

test_assert(
    (int)test_value(
        $pdo,
        "SELECT COALESCE(SUM(score), 0)
         FROM tbl_user_scores
         WHERE user_id = ?
           AND game = 'genetic_marketplace_purchase'",
        [$buyer]
    ) === -250,
    'buyer must spend exactly 250 DSPOINC'
);

test_assert(
    (int)test_value(
        $pdo,
        'SELECT COUNT(*)
         FROM tbl_score_adjustments'
    ) === 2,
    'buy must write exactly two score adjustments'
);

test_assert(
    (int)test_value(
        $pdo,
        'SELECT COUNT(*)
         FROM tbl_genetic_item_history
         WHERE listing_id = ?',
        [$listing200]
    ) === 2,
    'buy must write seller + buyer Genetic history'
);

$scoreRowsBeforeReplay = (int)test_value(
    $pdo,
    'SELECT COUNT(*)
     FROM tbl_user_scores'
);

$historyBeforeReplay = (int)test_value(
    $pdo,
    'SELECT COUNT(*)
     FROM tbl_genetic_item_history'
);

$buyReplay = genetic_marketplace_buy_listing(
    $pdo,
    $buyer,
    [
        'listing_id' => $listing200
    ]
);

test_assert(
    $buyReplay['replayed'] === true,
    'same buyer retry must replay without another settlement'
);

test_assert(
    (int)test_value(
        $pdo,
        'SELECT COUNT(*)
         FROM tbl_user_scores'
    ) === $scoreRowsBeforeReplay,
    'buy replay must not duplicate ledger rows'
);

test_assert(
    (int)test_value(
        $pdo,
        'SELECT COUNT(*)
         FROM tbl_genetic_item_history'
    ) === $historyBeforeReplay,
    'buy replay must not duplicate Genetic history'
);

/* MAX-2 exact trait protection */

$pdo->prepare(
    "INSERT INTO tbl_user_genetic_items (
        genetic_item_id,
        user_id,
        catalog_id,
        trait_type,
        trait_value,
        current_level,
        upgrade_status
     ) VALUES
        (301, ?, 1, 'Accessories', 'Cheese', 1, 'idle'),
        (302, ?, 1, 'Accessories', 'Cheese', 2, 'idle'),
        (303, ?, 1, 'Accessories', 'Cheese', 3, 'idle')"
)->execute([
    $buyer,
    $buyer,
    $seller
]);

$limitListing = genetic_marketplace_create_listing(
    $pdo,
    $seller,
    [
        'genetic_item_id' => 303,
        'price_dspoinc' => 10
    ]
);

$limitBlocked = false;

try {
    genetic_marketplace_buy_listing(
        $pdo,
        $buyer,
        [
            'listing_id' =>
                (int)$limitListing['listing_id']
        ]
    );
} catch (GeneticMarketplaceV1Exception $error) {
    $limitBlocked =
        $error->httpStatus === 409 &&
        $error->errorCode === 'exact_trait_limit';
}

test_assert(
    $limitBlocked,
    'third exact Genetic trait copy must be blocked'
);

test_assert(
    genetic_marketplace_origin_allowed([
        'HTTP_ORIGIN' =>
            'https://narrrfs.world',
        'HTTP_SEC_FETCH_SITE' =>
            'same-origin'
    ]),
    'canonical origin must be allowed'
);

test_assert(
    !genetic_marketplace_origin_allowed([
        'HTTP_ORIGIN' =>
            'https://example.com',
        'HTTP_SEC_FETCH_SITE' =>
            'cross-site'
    ]),
    'foreign origin must be rejected'
);

echo
    "PASS: Genetic Marketplace V1 create/cancel/buy is "
    . "transaction-guarded, replay-safe, max-2 protected, "
    . "active+frozen stake aware, and atomically audited "
    . "in isolated SQLite.\n";
