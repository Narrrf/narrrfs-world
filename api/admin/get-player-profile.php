<?php
header('Content-Type: application/json');

try {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);

    $query = trim($_GET['q'] ?? '');

    if ($query === '') {
        throw new Exception('Missing search query');
    }

    /**
     * Return whether a table exists.
     * This lets the API remain stable even when optional systems are not deployed yet.
     */
    function tableExists(SQLite3 $db, string $tableName): bool
    {
        $safeTableName = SQLite3::escapeString($tableName);

        $result = $db->querySingle("
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
              AND name = '{$safeTableName}'
            LIMIT 1
        ");

        return !empty($result);
    }

    /**
     * Return all column names for a table.
     * This is used to avoid hard-coding columns that may differ between environments.
     */
    function getTableColumns(SQLite3 $db, string $tableName): array
    {
        if (!tableExists($db, $tableName)) {
            return [];
        }

        $columns = [];
        $result = $db->query("PRAGMA table_info({$tableName})");

        if (!$result) {
            return [];
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['name'])) {
                $columns[] = $row['name'];
            }
        }

        return $columns;
    }

    /**
     * Return the first matching column name from a list of candidates.
     * This makes the API resilient to small schema naming differences.
     */
    function firstMatchingColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Return a safely cast integer from querySingle.
     */
    function queryInt(SQLite3 $db, string $sql): int
    {
        $value = $db->querySingle($sql);

        if ($value === null || $value === false || $value === '') {
            return 0;
        }

        return (int)$value;
    }

    /**
     * Return a safely cast float from querySingle.
     */
    function queryFloat(SQLite3 $db, string $sql): float
    {
        $value = $db->querySingle($sql);

        if ($value === null || $value === false || $value === '') {
            return 0.0;
        }

        return (float)$value;
    }

    /**
     * Return a safely cast string from querySingle.
     */
    function queryString(SQLite3 $db, string $sql): string
    {
        $value = $db->querySingle($sql);

        if ($value === null || $value === false) {
            return '';
        }

        return (string)$value;
    }

    /**
     * Find a user in tbl_users first, then fall back to holder verification data.
     * This preserves the existing search behavior while allowing wallet-based lookups.
     */
/**
 * Find a user in tbl_users first, then fall back to holder verification data.
 * This preserves the existing search behavior while allowing wallet-based lookups.
 */
function findUser(SQLite3 $db, string $query): array
{
    $safeQuery = SQLite3::escapeString($query);

    $userIdColumn = null;
    $userNameColumn = null;
    $user = false;

    $tblUsersColumns = getTableColumns($db, 'tbl_users');

    if (!empty($tblUsersColumns)) {
        $userIdColumn = firstMatchingColumn($tblUsersColumns, [
            'user_id',
            'discord_id',
            'discord_user_id',
            'id'
        ]);

        $userNameColumn = firstMatchingColumn($tblUsersColumns, [
            'username',
            'discord_username',
            'name'
        ]);
    }

    if ($userIdColumn && $userNameColumn) {
        $userSql = "
            SELECT {$userIdColumn} AS user_id, {$userNameColumn} AS username
            FROM tbl_users
            WHERE {$userIdColumn} = '{$safeQuery}'
               OR LOWER({$userNameColumn}) = LOWER('{$safeQuery}')
               OR LOWER({$userNameColumn}) LIKE LOWER('{$safeQuery}%')
               OR LOWER({$userNameColumn}) LIKE LOWER('%{$safeQuery}%')
            ORDER BY
                CASE
                    WHEN {$userIdColumn} = '{$safeQuery}' THEN 1
                    WHEN LOWER({$userNameColumn}) = LOWER('{$safeQuery}') THEN 2
                    WHEN LOWER({$userNameColumn}) LIKE LOWER('{$safeQuery}%') THEN 3
                    ELSE 4
                END,
                LENGTH({$userNameColumn}) ASC,
                {$userNameColumn} ASC
            LIMIT 1
        ";

        $userResult = $db->query($userSql);
        $user = $userResult ? $userResult->fetchArray(SQLITE3_ASSOC) : false;

        if ($user && !empty($user['user_id'])) {
            return [
                'user_id' => (string)$user['user_id'],
                'username' => $user['username'] ?: 'Unknown'
            ];
        }
    }

    if (tableExists($db, 'tbl_holder_verifications')) {
        $verificationColumns = getTableColumns($db, 'tbl_holder_verifications');

        $userIdCol = firstMatchingColumn($verificationColumns, ['user_id', 'discord_id']);
        $usernameCol = firstMatchingColumn($verificationColumns, ['username', 'discord_username', 'name']);
        $walletCol = firstMatchingColumn($verificationColumns, ['wallet', 'wallet_address']);
        $verifiedAtCol = firstMatchingColumn($verificationColumns, ['verified_at', 'created_at', 'timestamp']);

        if ($userIdCol && $usernameCol && $walletCol) {
            $orderByTimestamp = $verifiedAtCol
                ? "{$verifiedAtCol} DESC"
                : "rowid DESC";

            $verificationSql = "
                SELECT {$userIdCol} AS user_id, {$usernameCol} AS username, {$walletCol} AS wallet
                FROM tbl_holder_verifications
                WHERE {$userIdCol} = '{$safeQuery}'
                   OR {$walletCol} = '{$safeQuery}'
                   OR LOWER({$usernameCol}) = LOWER('{$safeQuery}')
                   OR LOWER({$usernameCol}) LIKE LOWER('{$safeQuery}%')
                   OR LOWER({$usernameCol}) LIKE LOWER('%{$safeQuery}%')
                ORDER BY
                    CASE
                        WHEN {$userIdCol} = '{$safeQuery}' THEN 1
                        WHEN {$walletCol} = '{$safeQuery}' THEN 2
                        WHEN LOWER({$usernameCol}) = LOWER('{$safeQuery}') THEN 3
                        WHEN LOWER({$usernameCol}) LIKE LOWER('{$safeQuery}%') THEN 4
                        ELSE 5
                    END,
                    LENGTH({$usernameCol}) ASC,
                    {$orderByTimestamp}
                LIMIT 1
            ";

            $verificationResult = $db->query($verificationSql);
            $verificationUser = $verificationResult ? $verificationResult->fetchArray(SQLITE3_ASSOC) : false;

            if ($verificationUser && !empty($verificationUser['user_id'])) {
                return [
                    'user_id' => (string)$verificationUser['user_id'],
                    'username' => $verificationUser['username'] ?: 'Unknown'
                ];
            }
        }
    }

    throw new Exception('No player found');
}

    /**
     * Load holder / wallet identity data.
     * This is kept separate so the admin renderer can later show a dedicated identity card.
     */
    function loadHolderData(SQLite3 $db, string $userId): array
    {
        $safeUserId = SQLite3::escapeString($userId);

        $holder = [
            'wallet' => '',
            'verified_at' => '',
            'is_holder' => false,
            'has_genesis' => false,
            'has_vip' => false,
            'nft_total' => 0,
            'genesis_count' => 0,
            'vip_count' => 0
        ];

        if (tableExists($db, 'tbl_holder_verifications')) {
            $columns = getTableColumns($db, 'tbl_holder_verifications');

            $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
            $walletColumn = firstMatchingColumn($columns, ['wallet', 'wallet_address']);
            $verifiedAtColumn = firstMatchingColumn($columns, ['verified_at', 'created_at', 'timestamp']);

            if ($userIdColumn && $walletColumn) {
                $holder['wallet'] = queryString($db, "
                    SELECT {$walletColumn}
                    FROM tbl_holder_verifications
                    WHERE {$userIdColumn} = '{$safeUserId}'
                    ORDER BY rowid DESC
                    LIMIT 1
                ");
            }

            if ($userIdColumn && $verifiedAtColumn) {
                $holder['verified_at'] = queryString($db, "
                    SELECT {$verifiedAtColumn}
                    FROM tbl_holder_verifications
                    WHERE {$userIdColumn} = '{$safeUserId}'
                    ORDER BY rowid DESC
                    LIMIT 1
                ");
            }
        }

        if (tableExists($db, 'tbl_nft_ownership')) {
            $columns = getTableColumns($db, 'tbl_nft_ownership');

            $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
            $collectionColumn = firstMatchingColumn($columns, ['collection', 'collection_name']);
            $verifiedColumn = firstMatchingColumn($columns, ['is_verified', 'verified']);

            if ($userIdColumn && $collectionColumn) {
                $verifiedFilter = '';
                if ($verifiedColumn) {
                    $verifiedFilter = " AND {$verifiedColumn} = 1 ";
                }

                $holder['genesis_count'] = queryInt($db, "
                    SELECT COUNT(*)
                    FROM tbl_nft_ownership
                    WHERE {$userIdColumn} = '{$safeUserId}'
                      AND LOWER({$collectionColumn}) = 'genesis'
                      {$verifiedFilter}
                ");

                $holder['vip_count'] = queryInt($db, "
                    SELECT COUNT(*)
                    FROM tbl_nft_ownership
                    WHERE {$userIdColumn} = '{$safeUserId}'
                      AND LOWER({$collectionColumn}) = 'vip'
                      {$verifiedFilter}
                ");

                $holder['nft_total'] = queryInt($db, "
                    SELECT COUNT(*)
                    FROM tbl_nft_ownership
                    WHERE {$userIdColumn} = '{$safeUserId}'
                      {$verifiedFilter}
                ");

                $holder['has_genesis'] = $holder['genesis_count'] > 0;
                $holder['has_vip'] = $holder['vip_count'] > 0;
                $holder['is_holder'] = $holder['nft_total'] > 0 || $holder['wallet'] !== '';
            }
        }

        return $holder;
    }

    /**
     * Load all assigned user roles.
     */
    function loadRoles(SQLite3 $db, string $userId): array
    {
        $safeUserId = SQLite3::escapeString($userId);

        if (!tableExists($db, 'tbl_user_roles')) {
            return [];
        }

        $columns = getTableColumns($db, 'tbl_user_roles');
        $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
        $roleColumn = firstMatchingColumn($columns, ['role_name', 'role']);

        if (!$userIdColumn || !$roleColumn) {
            return [];
        }

        $roles = [];
        $result = $db->query("
            SELECT DISTINCT {$roleColumn} AS role_name
            FROM tbl_user_roles
            WHERE {$userIdColumn} = '{$safeUserId}'
            ORDER BY {$roleColumn} ASC
        ");

        if (!$result) {
            return [];
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['role_name'])) {
                $roles[] = $row['role_name'];
            }
        }

        return $roles;
    }

    /**
     * Load NFT traits tied to the user.
     */
/**
 * Load NFT traits tied to the user.
 * Returns both raw rows and grouped trait counts so the frontend can
 * show "Cheese x2" instead of repeating identical cards.
 */
function loadTraits(SQLite3 $db, string $userId): array
{
    $safeUserId = SQLite3::escapeString($userId);

    if (!tableExists($db, 'tbl_nft_traits')) {
        return [
            'raw' => [],
            'summary' => []
        ];
    }

    $columns = getTableColumns($db, 'tbl_nft_traits');
    $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
    $traitTypeColumn = firstMatchingColumn($columns, ['trait_type', 'type']);
    $traitValueColumn = firstMatchingColumn($columns, ['trait_value', 'value']);

    if (!$userIdColumn || !$traitTypeColumn || !$traitValueColumn) {
        return [
            'raw' => [],
            'summary' => []
        ];
    }

    $rawTraits = [];
    $traitCounts = [];

    $result = $db->query("
        SELECT {$traitTypeColumn} AS trait_type, {$traitValueColumn} AS trait_value
        FROM tbl_nft_traits
        WHERE {$userIdColumn} = '{$safeUserId}'
        ORDER BY {$traitTypeColumn} ASC, {$traitValueColumn} ASC
        LIMIT 200
    ");

    if (!$result) {
        return [
            'raw' => [],
            'summary' => []
        ];
    }

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $traitType = (string)($row['trait_type'] ?? '');
        $traitValue = (string)($row['trait_value'] ?? '');

        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $rawTraits[] = [
            'trait_type' => $traitType,
            'trait_value' => $traitValue
        ];

        if (!isset($traitCounts[$traitType])) {
            $traitCounts[$traitType] = [];
        }

        if (!isset($traitCounts[$traitType][$traitValue])) {
            $traitCounts[$traitType][$traitValue] = 0;
        }

        $traitCounts[$traitType][$traitValue]++;
    }

    $traitSummary = [];

    foreach ($traitCounts as $traitType => $values) {
        ksort($values, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($values as $traitValue => $count) {
            $traitSummary[] = [
                'trait_type' => $traitType,
                'trait_value' => $traitValue,
                'count' => (int)$count
            ];
        }
    }

    usort($traitSummary, function ($left, $right) {
        $typeCompare = strcasecmp($left['trait_type'], $right['trait_type']);
        if ($typeCompare !== 0) {
            return $typeCompare;
        }

        return strcasecmp($left['trait_value'], $right['trait_value']);
    });

    return [
        'raw' => $rawTraits,
        'summary' => $traitSummary
    ];
}
    /**
     * Load DSPOINC economy summary.
     * Uses tbl_user_scores as the source of total balance.
     * Optionally subtracts frozen staking balance if a staking table exists.
     */
    function loadEconomy(SQLite3 $db, string $userId): array
    {
        $safeUserId = SQLite3::escapeString($userId);

        $economy = [
            'total_dspoinc' => 0,
            'frozen_dspoinc' => 0,
            'available_dspoinc' => 0,
            'score_entries' => 0,
            'staking_positions' => 0
        ];

        if (tableExists($db, 'tbl_user_scores')) {
            $columns = getTableColumns($db, 'tbl_user_scores');

            $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
            $scoreColumn = firstMatchingColumn($columns, ['score', 'amount', 'points']);

            if ($userIdColumn && $scoreColumn) {
                $economy['total_dspoinc'] = queryInt($db, "
                    SELECT COALESCE(SUM({$scoreColumn}), 0)
                    FROM tbl_user_scores
                    WHERE {$userIdColumn} = '{$safeUserId}'
                ");

                $economy['score_entries'] = queryInt($db, "
                    SELECT COUNT(*)
                    FROM tbl_user_scores
                    WHERE {$userIdColumn} = '{$safeUserId}'
                ");
            }
        }

        if (tableExists($db, 'tbl_dspoinc_stakes')) {
            $columns = getTableColumns($db, 'tbl_dspoinc_stakes');

            $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
            $amountColumn = firstMatchingColumn($columns, ['amount', 'staked_amount', 'stake_amount']);
            $statusColumn = firstMatchingColumn($columns, ['status', 'stake_status', 'state']);

            if ($userIdColumn && $amountColumn) {
                if ($statusColumn) {
                    $economy['frozen_dspoinc'] = queryInt($db, "
                        SELECT COALESCE(SUM({$amountColumn}), 0)
                        FROM tbl_dspoinc_stakes
                        WHERE {$userIdColumn} = '{$safeUserId}'
                          AND LOWER({$statusColumn}) IN ('active', 'locked', 'staked')
                    ");
                } else {
                    $economy['frozen_dspoinc'] = queryInt($db, "
                        SELECT COALESCE(SUM({$amountColumn}), 0)
                        FROM tbl_dspoinc_stakes
                        WHERE {$userIdColumn} = '{$safeUserId}'
                    ");
                }

                $economy['staking_positions'] = queryInt($db, "
                    SELECT COUNT(*)
                    FROM tbl_dspoinc_stakes
                    WHERE {$userIdColumn} = '{$safeUserId}'
                ");
            }
        }

        $economy['available_dspoinc'] = max(0, $economy['total_dspoinc'] - $economy['frozen_dspoinc']);

        return $economy;
    }

    /**
     * Load store / inventory summary.
     */
    function loadInventory(SQLite3 $db, string $userId): array
    {
        $safeUserId = SQLite3::escapeString($userId);

        $inventory = [
            'items_owned' => 0
        ];

        if (!tableExists($db, 'tbl_user_inventory')) {
            return $inventory;
        }

        $columns = getTableColumns($db, 'tbl_user_inventory');
        $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);

        if (!$userIdColumn) {
            return $inventory;
        }

        $inventory['items_owned'] = queryInt($db, "
            SELECT COUNT(*)
            FROM tbl_user_inventory
            WHERE {$userIdColumn} = '{$safeUserId}'
        ");

        return $inventory;
    }

    /**
     * Load purchase summary.
     */
    function loadPurchases(SQLite3 $db, string $userId): array
    {
        $safeUserId = SQLite3::escapeString($userId);

        $purchases = [
            'purchase_count' => 0,
            'total_spent' => 0,
            'last_purchase_at' => ''
        ];

        if (!tableExists($db, 'tbl_purchase_history')) {
            return $purchases;
        }

        $columns = getTableColumns($db, 'tbl_purchase_history');
        $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
        $amountColumn = firstMatchingColumn($columns, ['price', 'amount', 'cost', 'total_price']);
        $timestampColumn = firstMatchingColumn($columns, ['created_at', 'purchased_at', 'timestamp']);

        if (!$userIdColumn) {
            return $purchases;
        }

        $purchases['purchase_count'] = queryInt($db, "
            SELECT COUNT(*)
            FROM tbl_purchase_history
            WHERE {$userIdColumn} = '{$safeUserId}'
        ");

        if ($amountColumn) {
            $purchases['total_spent'] = queryInt($db, "
                SELECT COALESCE(SUM({$amountColumn}), 0)
                FROM tbl_purchase_history
                WHERE {$userIdColumn} = '{$safeUserId}'
            ");
        }

        if ($timestampColumn) {
            $purchases['last_purchase_at'] = queryString($db, "
                SELECT {$timestampColumn}
                FROM tbl_purchase_history
                WHERE {$userIdColumn} = '{$safeUserId}'
                ORDER BY {$timestampColumn} DESC
                LIMIT 1
            ");
        }

        return $purchases;
    }

    /**
     * Load per-game summary from tbl_tetris_scores.
     * This table is reused by multiple browser games in Narrrf's World.
     */
    function loadGameStats(SQLite3 $db, string $userId): array
    {
        $safeUserId = SQLite3::escapeString($userId);

        $gameStats = [
            'tetris' => ['games_played' => 0, 'best_score' => 0, 'dspoinc_earned' => 0, 'last_played' => ''],
            'snake' => ['games_played' => 0, 'best_score' => 0, 'dspoinc_earned' => 0, 'last_played' => ''],
            'space_invaders' => ['games_played' => 0, 'best_score' => 0, 'dspoinc_earned' => 0, 'last_played' => '']
        ];

        if (!tableExists($db, 'tbl_tetris_scores')) {
            return $gameStats;
        }

        $columns = getTableColumns($db, 'tbl_tetris_scores');
        $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
        $gameColumn = firstMatchingColumn($columns, ['game']);
        $scoreColumn = firstMatchingColumn($columns, ['score']);
        $timestampColumn = firstMatchingColumn($columns, ['timestamp', 'created_at', 'played_at']);

        if (!$userIdColumn || !$gameColumn || !$scoreColumn) {
            return $gameStats;
        }

        foreach (array_keys($gameStats) as $gameKey) {
            $safeGameKey = SQLite3::escapeString($gameKey);

            $gameStats[$gameKey]['games_played'] = queryInt($db, "
                SELECT COUNT(*)
                FROM tbl_tetris_scores
                WHERE {$userIdColumn} = '{$safeUserId}'
                  AND {$gameColumn} = '{$safeGameKey}'
            ");

            $gameStats[$gameKey]['best_score'] = queryInt($db, "
                SELECT COALESCE(MAX({$scoreColumn}), 0)
                FROM tbl_tetris_scores
                WHERE {$userIdColumn} = '{$safeUserId}'
                  AND {$gameColumn} = '{$safeGameKey}'
            ");

            $gameStats[$gameKey]['dspoinc_earned'] = queryInt($db, "
                SELECT COALESCE(SUM({$scoreColumn}), 0)
                FROM tbl_tetris_scores
                WHERE {$userIdColumn} = '{$safeUserId}'
                  AND {$gameColumn} = '{$safeGameKey}'
            ");

            if ($timestampColumn) {
                $gameStats[$gameKey]['last_played'] = queryString($db, "
                    SELECT {$timestampColumn}
                    FROM tbl_tetris_scores
                    WHERE {$userIdColumn} = '{$safeUserId}'
                      AND {$gameColumn} = '{$safeGameKey}'
                    ORDER BY {$timestampColumn} DESC
                    LIMIT 1
                ");
            }
        }

        /**
         * TODO: If you want Cheese Hunt / Discord Race / Glyph here too,
         * add dedicated loaders once their live table schemas are confirmed.
         * I am intentionally not inventing unsupported columns.
         */

        return $gameStats;
    }

    /**
     * Load a simple recent-activity snapshot from known systems.
     */
    function loadActivity(SQLite3 $db, string $userId): array
    {
        $safeUserId = SQLite3::escapeString($userId);

        $activity = [
            'last_score_at' => '',
            'last_verification_at' => '',
            'last_purchase_at' => '',
            'last_seen_at' => ''
        ];

        if (tableExists($db, 'tbl_user_scores')) {
            $columns = getTableColumns($db, 'tbl_user_scores');
            $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
            $timestampColumn = firstMatchingColumn($columns, ['timestamp', 'created_at', 'updated_at']);

            if ($userIdColumn && $timestampColumn) {
                $activity['last_score_at'] = queryString($db, "
                    SELECT {$timestampColumn}
                    FROM tbl_user_scores
                    WHERE {$userIdColumn} = '{$safeUserId}'
                    ORDER BY {$timestampColumn} DESC
                    LIMIT 1
                ");
            }
        }

        if (tableExists($db, 'tbl_holder_verifications')) {
            $columns = getTableColumns($db, 'tbl_holder_verifications');
            $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
            $timestampColumn = firstMatchingColumn($columns, ['verified_at', 'created_at', 'timestamp']);

            if ($userIdColumn && $timestampColumn) {
                $activity['last_verification_at'] = queryString($db, "
                    SELECT {$timestampColumn}
                    FROM tbl_holder_verifications
                    WHERE {$userIdColumn} = '{$safeUserId}'
                    ORDER BY {$timestampColumn} DESC
                    LIMIT 1
                ");
            }
        }

        if (tableExists($db, 'tbl_purchase_history')) {
            $columns = getTableColumns($db, 'tbl_purchase_history');
            $userIdColumn = firstMatchingColumn($columns, ['user_id', 'discord_id']);
            $timestampColumn = firstMatchingColumn($columns, ['created_at', 'purchased_at', 'timestamp']);

            if ($userIdColumn && $timestampColumn) {
                $activity['last_purchase_at'] = queryString($db, "
                    SELECT {$timestampColumn}
                    FROM tbl_purchase_history
                    WHERE {$userIdColumn} = '{$safeUserId}'
                    ORDER BY {$timestampColumn} DESC
                    LIMIT 1
                ");
            }
        }

        $activity['last_seen_at'] = $activity['last_purchase_at']
            ?: $activity['last_score_at']
            ?: $activity['last_verification_at'];

        return $activity;
    }

    $user = findUser($db, $query);
    $userId = $user['user_id'];
    $username = $user['username'] ?: 'Unknown';

    $holder = loadHolderData($db, $userId);
    $roles = loadRoles($db, $userId);
    $traitData = loadTraits($db, $userId);
    $economy = loadEconomy($db, $userId);
    $inventory = loadInventory($db, $userId);
    $purchases = loadPurchases($db, $userId);
    $gameStats = loadGameStats($db, $userId);
    $activity = loadActivity($db, $userId);

    echo json_encode([
        'success' => true,
        'profile' => [
            /**
             * Backward-compatible legacy fields for current admin renderer.
             */
            'user_id' => $userId,
            'username' => $username,
            'balance' => $economy['total_dspoinc'],
            'wallet' => $holder['wallet'],
            'genesis_count' => $holder['genesis_count'],
            'vip_count' => $holder['vip_count'],
            'roles' => $roles,
            'traits' => $traitData['raw'],
            'trait_summary' => $traitData['summary'],

            /**
             * New structured fields for the richer admin player profile.
             */
            'identity' => [
                'user_id' => $userId,
                'username' => $username
            ],
            'holder' => $holder,
            'economy' => $economy,
            'inventory' => $inventory,
            'purchases' => $purchases,
            'game_stats' => $gameStats,
            'activity' => $activity
        ]
    ]);
} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}