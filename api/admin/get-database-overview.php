<?php
// Return a live SQLite database overview for the Admin Interface Database Overview tab.

header('Content-Type: application/json');

try {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);

    function getTableCategory(string $tableName): string {
        $systemTables = [
            'tbl_users', 'tbl_user_roles', 'tbl_admin_sessions', 'tbl_seasons', 'tbl_season_settings',
            'tbl_season_leaderboards', 'tbl_user_season_achievements', 'tbl_game_settings'
        ];

        $economyTables = [
            'tbl_user_scores', 'tbl_score_adjustments', 'tbl_rewards',
            'tbl_wallet_transactions', 'tbl_wallet_balance_history', 'tbl_dspoinc_stakes',
            'tbl_community_funds'
        ];

        $nftTables = [
            'tbl_holder_verifications', 'tbl_nft_ownership', 'tbl_nft_traits',
            'tbl_role_grants', 'tbl_wl_role_grants', 'tbl_user_traits'
        ];

        $gameTables = [
            'tbl_cheese_clicks', 'tbl_cheese_hunt_captures', 'tbl_cheese_races', 'tbl_cheese_rumbles',
            'tbl_race_participants', 'tbl_rumble_participants', 'tbl_tetris_scores',
            'tbl_tetris_achievements', 'tbl_snake_achievements', 'tbl_space_invaders_achievements',
            'tbl_space_invaders_negative_scores_backup', 'tbl_space_invaders_settings',
            'tbl_glyph_memory_scores', 'leaderboard', 'boss_configurations', 'boss_level_notifications'
        ];

        $questTables = [
            'tbl_quests', 'tbl_quest_claims', 'tbl_store_items', 'tbl_purchase_history',
            'tbl_user_inventory', 'tbl_user_store_settings', 'tbl_twitter_missions',
            'tbl_twitter_mission_participants', 'tbl_twitter_verification_logs',
            'tbl_bingo_tickets', 'tbl_riddle_completions', 'tbl_partners', 'tbl_giveaways',
            'tbl_giveaway_participants', 'tbl_giveaway_winners'
        ];

        $securityTables = [
            'tbl_security_bruteforce_attempts', 'tbl_security_crawls', 'tbl_security_findings'
        ];

        $adminTables = [
            'tbl_bug_assignments', 'tbl_bug_categories', 'tbl_bug_comments', 'tbl_bug_priorities',
            'tbl_bug_reports', 'tbl_bug_status_history', 'tbl_bug_statuses',
            'tbl_admin_inventory_actions', 'tbl_item_usage_history', 'tbl_item_usage_requests',
            'tbl_discord_events', 'portal_waypoint_messages', 'tbl_winners_post_log',
            'tbl_historical_stats', 'tbl_historical_cheese_stats'
        ];

        if (in_array($tableName, $systemTables, true)) return 'system';
        if (in_array($tableName, $economyTables, true)) return 'economy';
        if (in_array($tableName, $nftTables, true)) return 'nft';
        if (in_array($tableName, $gameTables, true)) return 'games';
        if (in_array($tableName, $questTables, true)) return 'quests';
        if (in_array($tableName, $securityTables, true)) return 'security';
        if (in_array($tableName, $adminTables, true)) return 'admin';

        return 'other';
    }

    $tablesResult = $db->query("
        SELECT name
        FROM sqlite_master
        WHERE type = 'table'
          AND name NOT LIKE 'sqlite_%'
        ORDER BY name COLLATE NOCASE ASC
    ");

    $tables = [];
    $totalRows = 0;
    $tablesWithRows = 0;
    $emptyTables = 0;
    $categories = [
        'system' => 0,
        'economy' => 0,
        'nft' => 0,
        'games' => 0,
        'quests' => 0,
        'security' => 0,
        'admin' => 0,
        'other' => 0
    ];

    while ($row = $tablesResult->fetchArray(SQLITE3_ASSOC)) {
        $tableName = $row['name'];

        $countQuery = 'SELECT COUNT(*) AS row_count FROM "' . str_replace('"', '""', $tableName) . '"';
        $countResult = $db->querySingle($countQuery);

        $rowCount = (int)$countResult;
        $category = getTableCategory($tableName);

        $totalRows += $rowCount;
        if ($rowCount > 0) {
            $tablesWithRows++;
        } else {
            $emptyTables++;
        }

        $categories[$category]++;

        $tables[] = [
            'name' => $tableName,
            'row_count' => $rowCount,
            'category' => $category,
            'has_rows' => $rowCount > 0
        ];
    }

    $dbWritable = is_writable($dbPath);
    $dbSizeBytes = filesize($dbPath);
    $dbSizeMb = $dbSizeBytes !== false ? round($dbSizeBytes / 1024 / 1024, 2) : 0;

    $nftHealth = [
        'holder_verifications' => (int)$db->querySingle("SELECT COUNT(*) FROM tbl_holder_verifications"),
        'nft_ownership' => (int)$db->querySingle("SELECT COUNT(*) FROM tbl_nft_ownership"),
        'nft_traits' => (int)$db->querySingle("SELECT COUNT(*) FROM tbl_nft_traits"),
        'role_grants' => (int)$db->querySingle("SELECT COUNT(*) FROM tbl_role_grants")
    ];

    echo json_encode([
        'success' => true,
        'summary' => [
            'total_tables' => count($tables),
            'tables_with_rows' => $tablesWithRows,
            'empty_tables' => $emptyTables,
            'total_records' => $totalRows,
            'environment' => (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) ? 'Local' : 'Render',
            'sqlite_enabled' => extension_loaded('sqlite3'),
            'db_writable' => $dbWritable,
            'db_file_size_mb' => $dbSizeMb
        ],
        'categories' => $categories,
        'nft_health' => $nftHealth,
        'tables' => $tables
    ]);
} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}