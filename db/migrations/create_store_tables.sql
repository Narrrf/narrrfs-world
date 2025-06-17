-- Create store items table
CREATE TABLE IF NOT EXISTS tbl_store_items (
    item_id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    price INTEGER NOT NULL,
    image_url TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create user inventory table
CREATE TABLE IF NOT EXISTS tbl_user_inventory (
    inventory_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    item_id INTEGER NOT NULL,
    quantity INTEGER DEFAULT 1,
    acquired_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME,
    FOREIGN KEY (item_id) REFERENCES tbl_store_items(item_id),
    UNIQUE(user_id, item_id)
);

-- Create purchase history table
CREATE TABLE IF NOT EXISTS tbl_purchase_history (
    purchase_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    item_id INTEGER NOT NULL,
    price_paid INTEGER NOT NULL,
    quantity INTEGER DEFAULT 1,
    purchased_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES tbl_store_items(item_id)
);

-- Create indices for better query performance
CREATE INDEX IF NOT EXISTS idx_user_inventory ON tbl_user_inventory(user_id);
CREATE INDEX IF NOT EXISTS idx_purchase_history ON tbl_purchase_history(user_id); 