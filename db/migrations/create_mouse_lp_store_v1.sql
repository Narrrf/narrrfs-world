-- Mouse LP Store V1: local schema foundation.
-- This migration intentionally does not rely on SQLite foreign-key enforcement.

CREATE TABLE tbl_mouse_lp_store_offers (
    offer_id INTEGER PRIMARY KEY AUTOINCREMENT,
    source_type TEXT NOT NULL CHECK (source_type IN ('normal_store', 'genetic_item')),
    source_item_id INTEGER NOT NULL CHECK (source_item_id > 0),
    lp_price INTEGER NULL CHECK (lp_price IS NULL OR lp_price > 0),
    dspoinc_price INTEGER NULL CHECK (dspoinc_price IS NULL OR dspoinc_price > 0),
    stock INTEGER NOT NULL CHECK (stock >= 0),
    is_active INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
    created_by TEXT NOT NULL,
    updated_by TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (lp_price IS NOT NULL OR dspoinc_price IS NOT NULL),
    UNIQUE (source_type, source_item_id)
);

CREATE TABLE tbl_mouse_lp_store_purchases (
    purchase_id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchaser_user_id TEXT NOT NULL,
    idempotency_key TEXT NOT NULL,
    request_fingerprint TEXT NOT NULL,
    offer_id INTEGER NOT NULL,
    source_type TEXT NOT NULL CHECK (source_type IN ('normal_store', 'genetic_item')),
    source_item_id INTEGER NOT NULL CHECK (source_item_id > 0),
    item_title_snapshot TEXT NOT NULL,
    payment_method TEXT NOT NULL CHECK (payment_method IN ('lp', 'dspoinc')),
    amount_paid INTEGER NOT NULL CHECK (amount_paid > 0),
    selected_token_id TEXT NULL,
    selected_collection TEXT NULL CHECK (selected_collection IS NULL OR selected_collection = 'genesis'),
    mouse_name_snapshot TEXT NULL,
    delivery_reference_id INTEGER NULL,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (
        (payment_method = 'lp' AND selected_token_id IS NOT NULL
            AND selected_collection = 'genesis'
            AND TRIM(COALESCE(mouse_name_snapshot, '')) <> '')
        OR
        (payment_method = 'dspoinc' AND selected_token_id IS NULL
            AND selected_collection IS NULL AND mouse_name_snapshot IS NULL)
    ),
    UNIQUE (purchaser_user_id, idempotency_key)
);

CREATE TABLE tbl_mousefight_league_point_spends (
    spend_id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_id INTEGER NOT NULL UNIQUE,
    purchaser_user_id TEXT NOT NULL,
    token_id TEXT NOT NULL,
    collection TEXT NOT NULL DEFAULT 'genesis' CHECK (collection = 'genesis'),
    points_spent INTEGER NOT NULL CHECK (points_spent > 0),
    spent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_mouse_lp_store_offers_active
    ON tbl_mouse_lp_store_offers (is_active, source_type);
CREATE INDEX idx_mouse_lp_store_purchases_user_completed
    ON tbl_mouse_lp_store_purchases (purchaser_user_id, completed_at DESC);
CREATE INDEX idx_mousefight_lp_spends_token_collection
    ON tbl_mousefight_league_point_spends (token_id, collection);
