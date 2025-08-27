<?php
session_start();
header('Content-Type: application/json');

// Only session user can fetch profile!
$user_id = $_SESSION['discord_id'] ?? '';
if (!$user_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$db = new SQLite3(__DIR__ . '/../../db/narrrf_world.sqlite');

try {
    // 1. Basic info
    $stmt = $db->prepare("SELECT username, avatar_url, created_at FROM tbl_users WHERE discord_id = ?");
    $stmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $userRow = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    // 2. Discord join date = member since date
    $member_since = isset($userRow['created_at']) && $userRow['created_at'] ? substr($userRow['created_at'], 0, 10) : "";

    // 3. Roles
    $roles = [];
    $roleStmt = $db->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
    $roleStmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $res = $roleStmt->execute();
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) $roles[] = $row['role_name'];

    // 4. Traits
    $traits = [];
    $traitStmt = $db->prepare("SELECT trait FROM tbl_user_traits WHERE user_id = ?");
    $traitStmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $tr = $traitStmt->execute();
    while ($row = $tr->fetchArray(SQLITE3_ASSOC)) $traits[] = $row['trait'];

    // 5. Stats
    $adjStmt = $db->prepare("SELECT COUNT(*) FROM tbl_score_adjustments WHERE user_id = ?");
    $adjStmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $adj = $adjStmt->execute()->fetchArray(SQLITE3_NUM)[0];

    $srcStmt = $db->prepare("SELECT COUNT(DISTINCT source) FROM tbl_user_scores WHERE user_id = ?");
    $srcStmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $sources = $srcStmt->execute()->fetchArray(SQLITE3_NUM)[0];

    // 6. Calculate total DSPOINC from tbl_user_scores
    $dspoincStmt = $db->prepare("SELECT SUM(score) FROM tbl_user_scores WHERE user_id = ?");
    $dspoincStmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $dspoincResult = $dspoincStmt->execute()->fetchArray(SQLITE3_NUM);
    $total_dspoinc = $dspoincResult[0] ?? 0;

    // 7. INVENTORY - Get user's purchased items
    $inventory = [];
    $inventoryStmt = $db->prepare("
        SELECT 
            ui.item_id,
            ui.quantity,
            ui.acquired_at,
            si.item_name,
            si.description,
            si.price,
            si.image_url
        FROM tbl_user_inventory ui
        JOIN tbl_store_items si ON ui.item_id = si.item_id
        WHERE ui.user_id = ?
        ORDER BY ui.acquired_at DESC
    ");
    $inventoryStmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $inventoryResult = $inventoryStmt->execute();
    
    while ($row = $inventoryResult->fetchArray(SQLITE3_ASSOC)) {
        $inventory[] = [
            'item_id' => $row['item_id'],
            'item_name' => $row['item_name'],
            'description' => $row['description'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'image_url' => $row['image_url'],
            'acquired_at' => $row['acquired_at'],
            'total_value' => $row['price'] * $row['quantity']
        ];
    }

    // 8. PURCHASE HISTORY - Get user's purchase records
    $purchaseHistory = [];
    $purchaseStmt = $db->prepare("
        SELECT 
            ph.purchase_id,
            ph.item_id,
            ph.price_paid,
            ph.quantity,
            ph.purchased_at,
            si.item_name,
            si.description,
            si.image_url
        FROM tbl_purchase_history ph
        JOIN tbl_store_items si ON ph.item_id = si.item_id
        WHERE ph.user_id = ?
        ORDER BY ph.purchased_at DESC
        LIMIT 20
    ");
    $purchaseStmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $purchaseResult = $purchaseStmt->execute();
    
    while ($row = $purchaseResult->fetchArray(SQLITE3_ASSOC)) {
        $purchaseHistory[] = [
            'purchase_id' => $row['purchase_id'],
            'item_id' => $row['item_id'],
            'item_name' => $row['item_name'],
            'description' => $row['description'],
            'quantity' => $row['quantity'],
            'price_paid' => $row['price_paid'],
            'image_url' => $row['image_url'],
            'purchased_at' => $row['purchased_at'],
            'price_per_item' => $row['price_paid'] / $row['quantity']
        ];
    }

    // 9. INVENTORY SUMMARY STATS
    $inventoryStats = [
        'total_items' => 0,
        'unique_items' => 0,
        'total_value' => 0,
        'most_valuable_item' => null,
        'recent_purchases' => 0
    ];

    if (!empty($inventory)) {
        $inventoryStats['total_items'] = array_sum(array_column($inventory, 'quantity'));
        $inventoryStats['unique_items'] = count($inventory);
        $inventoryStats['total_value'] = array_sum(array_column($inventory, 'total_value'));
        
        // Find most valuable item
        $mostValuable = array_reduce($inventory, function($carry, $item) {
            return ($carry === null || $item['total_value'] > $carry['total_value']) ? $item : $carry;
        });
        $inventoryStats['most_valuable_item'] = $mostValuable;
    }

    // Count recent purchases (last 30 days)
    $recentPurchases = array_filter($purchaseHistory, function($purchase) {
        $purchaseDate = strtotime($purchase['purchased_at']);
        $thirtyDaysAgo = strtotime('-30 days');
        return $purchaseDate >= $thirtyDaysAgo;
    });
    $inventoryStats['recent_purchases'] = count($recentPurchases);

    // 10. PURCHASE SUMMARY STATS
    $purchaseStats = [
        'total_purchases' => count($purchaseHistory),
        'total_spent' => array_sum(array_column($purchaseHistory, 'price_paid')),
        'average_purchase_value' => count($purchaseHistory) > 0 ? array_sum(array_column($purchaseHistory, 'price_paid')) / count($purchaseHistory) : 0,
        'largest_purchase' => !empty($purchaseHistory) ? max(array_column($purchaseHistory, 'price_paid')) : 0,
        'favorite_item' => null
    ];

    // Find favorite item (most purchased)
    if (!empty($purchaseHistory)) {
        $itemCounts = [];
        foreach ($purchaseHistory as $purchase) {
            $itemName = $purchase['item_name'];
            $itemCounts[$itemName] = ($itemCounts[$itemName] ?? 0) + $purchase['quantity'];
        }
        
        if (!empty($itemCounts)) {
            $favoriteItem = array_keys($itemCounts, max($itemCounts))[0];
            $purchaseStats['favorite_item'] = [
                'name' => $favoriteItem,
                'total_quantity' => max($itemCounts)
            ];
        }
    }

    // NOW close the DB!
    $db->close();

    // Output enhanced JSON
    echo json_encode([
        'success' => true,
        'basic_info' => [
            'discord_id' => $user_id,
            'discord_name' => $userRow['username'] ?? 'Unknown',
            'avatar_url' => $userRow['avatar_url'] ?? '',
            'member_since' => $member_since,
            'roles' => $roles,
            'traits' => $traits
        ],
        'financial' => [
            'total_dspoinc' => (int)$total_dspoinc,
            'total_spent' => (int)$purchaseStats['total_spent'],
            'net_worth' => (int)($total_dspoinc - $purchaseStats['total_spent'])
        ],
        'stats' => [
            'scoreAdjustments' => (int)$adj,
            'sources' => (int)$sources,
            'roles' => count($roles),
            'total_items_owned' => $inventoryStats['total_items'],
            'unique_items_owned' => $inventoryStats['unique_items'],
            'total_purchases' => $purchaseStats['total_purchases']
        ],
        'inventory' => [
            'items' => $inventory,
            'summary' => $inventoryStats
        ],
        'purchase_history' => [
            'purchases' => $purchaseHistory,
            'summary' => $purchaseStats
        ]
    ]);

} catch (Exception $e) {
    $db->close();
    echo json_encode([
        'success' => false,
        'error' => 'Profile loading failed: ' . $e->getMessage()
    ]);
}
?>
