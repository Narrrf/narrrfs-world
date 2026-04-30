<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * Create SQLite PDO connection.
 * Uses local DB on localhost/127.0.0.1 and production DB on Render.
 */
function getSQLite3Connection() {
    $isLocalhost =
        ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' ||
        strpos(($_SERVER['HTTP_HOST'] ?? ''), '127.0.0.1') !== false;

    $dbPath = $isLocalhost
        ? '../../db/narrrf_world.sqlite'
        : '/var/www/html/db/narrrf_world.sqlite';

    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Format time in ms to MM:SS for Glyph Memory display.
 */
function formatGlyphTimeMs($ms) {
    if ($ms === null || !is_numeric($ms)) {
        return '--:--';
    }

    $totalSec = (int) floor($ms / 1000);
    $min = (int) floor($totalSec / 60);
    $sec = (int) ($totalSec % 60);

    return sprintf('%02d:%02d', $min, $sec);
}

/**
 * Safely cast nullable DB value to int.
 */
function safeInt($value) {
    return $value !== null ? (int) $value : 0;
}

/**
 * Safely cast nullable DB value to float.
 */
function safeFloat($value, $precision = 2) {
    return $value !== null ? round((float) $value, $precision) : 0;
}

/**
 * Add unique player IDs from a list into the global player registry.
 */
function addPlayerIdsToRegistry(&$registry, $rows, $idField = 'discord_id') {
    if (!is_array($rows)) {
        return;
    }

    foreach ($rows as $row) {
        if (!empty($row[$idField])) {
            $registry[(string) $row[$idField]] = true;
        }
    }
}

try {
    $db = getSQLite3Connection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get current active season
    $seasonStmt = $db->query("
        SELECT season_name
        FROM tbl_seasons
        WHERE is_active = 1
        ORDER BY season_id DESC
        LIMIT 1
    ");
    $fullSeasonName = $seasonStmt->fetchColumn() ?: 'Season 11';
    $currentSeason = $fullSeasonName;

    // Get current season start date for date-window systems
    $seasonDateStmt = $db->prepare("
        SELECT start_date, end_date
        FROM tbl_seasons
        WHERE is_active = 1
        ORDER BY season_id DESC
        LIMIT 1
    ");
    $seasonDateStmt->execute();
    $seasonDateRow = $seasonDateStmt->fetch(PDO::FETCH_ASSOC);

    $seasonStart = $seasonDateRow['start_date'] ?? date('Y-m-d H:i:s', strtotime('-30 days'));
    $seasonEnd = $seasonDateRow['end_date'] ?? null;

    $seasonStartSql = date('Y-m-d H:i:s', strtotime($seasonStart));
    $seasonStartIso = date('Y-m-d\TH:i:s', strtotime($seasonStartSql)) . 'Z';
    $recent24hSql = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $recent7dSql = date('Y-m-d H:i:s', strtotime('-7 days'));
    $recent24hIso = date('Y-m-d\TH:i:s', strtotime('-24 hours')) . 'Z';
    $recent7dIso = date('Y-m-d\TH:i:s', strtotime('-7 days')) . 'Z';

    $allPlayerRegistry = [];
    $gamesWithActivity = 0;
    $summedUniquePlayerEntries = 0;
    $totalEntriesAcrossGames = 0;

    $response = [
        'success' => true,
        'data' => [
            'overview' => [
                'games_configured' => 8,
                'games_with_activity' => 0,
                'current_season' => $fullSeasonName,
                'last_updated' => date('Y-m-d H:i:s'),
                'summed_unique_player_entries' => 0,
                'true_unique_players' => 0,
                'total_entries' => 0
            ],
            'recent_activity' => [],
            'games' => []
        ]
    ];

    /**
     * 1. TETRIS
     */
    try {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_tetris_scores
            WHERE game = 'tetris'
            AND season = ?
        ");
        $stmt->execute([$currentSeason]);
        $tetrisData = $stmt->fetch(PDO::FETCH_ASSOC);

        $topStmt = $db->prepare("
            SELECT
                discord_id,
                discord_name as username,
                MAX(score) as best_score,
                COUNT(*) as total_games,
                MAX(timestamp) as last_game
            FROM tbl_tetris_scores
            WHERE game = 'tetris'
            AND season = ?
            GROUP BY discord_id, discord_name
            ORDER BY best_score DESC, last_game ASC
            LIMIT 10
        ");
        $topStmt->execute([$currentSeason]);
        $topPlayers = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        addPlayerIdsToRegistry($allPlayerRegistry, $topPlayers);

        $response['data']['games']['tetris'] = [
            'game_name' => 'Tetris',
            'game_icon' => '🧩',
            'status' => 'active',
            'data_mode' => 'seasonal_score_ladder',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_entries' => safeInt($tetrisData['total_scores']),
                'total_scores' => safeInt($tetrisData['total_scores']),
                'unique_players' => safeInt($tetrisData['unique_players']),
                'best_value' => safeInt($tetrisData['max_score']),
                'max_score' => safeInt($tetrisData['max_score']),
                'avg_value' => round($tetrisData['avg_score'] ?: 0),
                'avg_score' => round($tetrisData['avg_score'] ?: 0),
                'recent_24h' => safeInt($tetrisData['recent_24h']),
                'recent_7d' => safeInt($tetrisData['recent_7d'])
            ],
            'top_players' => $topPlayers,
            'diagnostics' => [
                'query_ok' => true,
                'source_table' => 'tbl_tetris_scores',
                'season_mode' => 'strict_current_season',
                'row_count' => safeInt($tetrisData['total_scores'])
            ]
        ];

        if (safeInt($tetrisData['total_scores']) > 0) {
            $gamesWithActivity++;
        }
        $summedUniquePlayerEntries += safeInt($tetrisData['unique_players']);
        $totalEntriesAcrossGames += safeInt($tetrisData['total_scores']);
    } catch (Exception $e) {
        error_log("Tetris stats error: " . $e->getMessage());
    }

    /**
     * 2. SNAKE
     */
    try {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_tetris_scores
            WHERE game = 'snake'
            AND season = ?
        ");
        $stmt->execute([$currentSeason]);
        $snakeData = $stmt->fetch(PDO::FETCH_ASSOC);

        $topStmt = $db->prepare("
            SELECT
                discord_id,
                discord_name as username,
                MAX(score) as best_score,
                COUNT(*) as total_games,
                MAX(timestamp) as last_game
            FROM tbl_tetris_scores
            WHERE game = 'snake'
            AND season = ?
            GROUP BY discord_id, discord_name
            ORDER BY best_score DESC, last_game ASC
            LIMIT 10
        ");
        $topStmt->execute([$currentSeason]);
        $topPlayers = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        addPlayerIdsToRegistry($allPlayerRegistry, $topPlayers);

        $response['data']['games']['snake'] = [
            'game_name' => 'Snake',
            'game_icon' => '🐍',
            'status' => 'active',
            'data_mode' => 'seasonal_score_ladder',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_entries' => safeInt($snakeData['total_scores']),
                'total_scores' => safeInt($snakeData['total_scores']),
                'unique_players' => safeInt($snakeData['unique_players']),
                'best_value' => safeInt($snakeData['max_score']),
                'max_score' => safeInt($snakeData['max_score']),
                'avg_value' => round($snakeData['avg_score'] ?: 0),
                'avg_score' => round($snakeData['avg_score'] ?: 0),
                'recent_24h' => safeInt($snakeData['recent_24h']),
                'recent_7d' => safeInt($snakeData['recent_7d'])
            ],
            'top_players' => $topPlayers,
            'diagnostics' => [
                'query_ok' => true,
                'source_table' => 'tbl_tetris_scores',
                'season_mode' => 'strict_current_season',
                'row_count' => safeInt($snakeData['total_scores'])
            ]
        ];

        if (safeInt($snakeData['total_scores']) > 0) {
            $gamesWithActivity++;
        }
        $summedUniquePlayerEntries += safeInt($snakeData['unique_players']);
        $totalEntriesAcrossGames += safeInt($snakeData['total_scores']);
    } catch (Exception $e) {
        error_log("Snake stats error: " . $e->getMessage());
    }

    /**
     * 3. SPACE INVADERS
     */
    try {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_tetris_scores
            WHERE game = 'space_invaders'
            AND season = ?
        ");
        $stmt->execute([$currentSeason]);
        $spaceData = $stmt->fetch(PDO::FETCH_ASSOC);

        $topStmt = $db->prepare("
            SELECT
                discord_id,
                discord_name as username,
                MAX(score) as best_score,
                COUNT(*) as total_games,
                MAX(timestamp) as last_game
            FROM tbl_tetris_scores
            WHERE game = 'space_invaders'
            AND season = ?
            GROUP BY discord_id, discord_name
            ORDER BY best_score DESC, last_game ASC
            LIMIT 10
        ");
        $topStmt->execute([$currentSeason]);
        $topPlayers = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        addPlayerIdsToRegistry($allPlayerRegistry, $topPlayers);

        $response['data']['games']['space_invaders'] = [
            'game_name' => 'Space Invaders',
            'game_icon' => '👾',
            'status' => 'active',
            'data_mode' => 'seasonal_score_ladder',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_entries' => safeInt($spaceData['total_scores']),
                'total_scores' => safeInt($spaceData['total_scores']),
                'unique_players' => safeInt($spaceData['unique_players']),
                'best_value' => safeInt($spaceData['max_score']),
                'max_score' => safeInt($spaceData['max_score']),
                'avg_value' => round($spaceData['avg_score'] ?: 0),
                'avg_score' => round($spaceData['avg_score'] ?: 0),
                'recent_24h' => safeInt($spaceData['recent_24h']),
                'recent_7d' => safeInt($spaceData['recent_7d'])
            ],
            'top_players' => $topPlayers,
            'diagnostics' => [
                'query_ok' => true,
                'source_table' => 'tbl_tetris_scores',
                'season_mode' => 'strict_current_season',
                'row_count' => safeInt($spaceData['total_scores'])
            ]
        ];

        if (safeInt($spaceData['total_scores']) > 0) {
            $gamesWithActivity++;
        }
        $summedUniquePlayerEntries += safeInt($spaceData['unique_players']);
        $totalEntriesAcrossGames += safeInt($spaceData['total_scores']);
    } catch (Exception $e) {
        error_log("Space Invaders stats error: " . $e->getMessage());
    }

    /**
     * 4. CHEESE HUNT
     * Persistent activity system.
     */
    try {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_clicks,
                COUNT(DISTINCT user_wallet) as unique_players,
                COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
                COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d,
                (
                    SELECT MAX(player_clicks)
                    FROM (
                        SELECT COUNT(*) as player_clicks
                        FROM tbl_cheese_clicks
                        GROUP BY user_wallet
                    )
                ) as max_clicks,
                (
                    SELECT AVG(player_clicks)
                    FROM (
                        SELECT COUNT(*) as player_clicks
                        FROM tbl_cheese_clicks
                        GROUP BY user_wallet
                    )
                ) as avg_clicks
            FROM tbl_cheese_clicks
        ");
        $stmt->execute();
        $cheeseData = $stmt->fetch(PDO::FETCH_ASSOC);

        $topStmt = $db->prepare("
            SELECT
                user_wallet as discord_id,
                COUNT(*) as total_clicks,
                COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
                MAX(timestamp) as last_click
            FROM tbl_cheese_clicks
            GROUP BY user_wallet
            ORDER BY total_clicks DESC, last_click ASC
            LIMIT 10
        ");
        $topStmt->execute();
        $topPlayers = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        addPlayerIdsToRegistry($allPlayerRegistry, $topPlayers);

        $response['data']['games']['cheese_hunt'] = [
            'game_name' => 'Cheese Hunt',
            'game_icon' => '🧀',
            'status' => 'active',
            'data_mode' => 'persistent_activity',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_entries' => safeInt($cheeseData['total_clicks']),
                'total_clicks' => safeInt($cheeseData['total_clicks']),
                'unique_players' => safeInt($cheeseData['unique_players']),
                'best_value' => safeInt($cheeseData['quest_clicks']),
                'quest_clicks' => safeInt($cheeseData['quest_clicks']),
                'max_clicks' => safeInt($cheeseData['max_clicks']),
                'avg_clicks' => safeFloat($cheeseData['avg_clicks'], 0),
                'recent_24h' => safeInt($cheeseData['recent_24h']),
                'recent_7d' => safeInt($cheeseData['recent_7d'])
            ],
            'top_players' => $topPlayers,
            'diagnostics' => [
                'query_ok' => true,
                'source_table' => 'tbl_cheese_clicks',
                'season_mode' => 'persistent_all_time',
                'row_count' => safeInt($cheeseData['total_clicks'])
            ]
        ];

        if (safeInt($cheeseData['total_clicks']) > 0) {
            $gamesWithActivity++;
        }

        $summedUniquePlayerEntries += safeInt($cheeseData['unique_players']);
        $totalEntriesAcrossGames += safeInt($cheeseData['total_clicks']);
    } catch (Exception $e) {
        error_log("Cheese Hunt stats error: " . $e->getMessage());
    }

    /**
     * 5. DISCORD RACE
     * Persistent activity system.
     */
    try {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_races,
                COUNT(DISTINCT user_id) as unique_players,
                COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
                COUNT(CASE WHEN joined_at >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN joined_at >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_race_participants
        ");
        $stmt->execute();
        $raceData = $stmt->fetch(PDO::FETCH_ASSOC);

        $topStmt = $db->prepare("
            SELECT
                user_id as discord_id,
                COUNT(*) as total_races,
                COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN position <= 3 THEN 1 END) as podiums,
                MIN(position) as best_position,
                MAX(joined_at) as last_race
            FROM tbl_race_participants
            GROUP BY user_id
            ORDER BY total_races DESC, wins DESC, podiums DESC, last_race ASC
            LIMIT 10
        ");
        $topStmt->execute();
        $topPlayers = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        addPlayerIdsToRegistry($allPlayerRegistry, $topPlayers);

        $response['data']['games']['discord_race'] = [
            'game_name' => 'Discord Race',
            'game_icon' => '🏁',
            'status' => 'active',
            'data_mode' => 'persistent_activity',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_entries' => safeInt($raceData['total_races']),
                'total_races' => safeInt($raceData['total_races']),
                'unique_players' => safeInt($raceData['unique_players']),
                'best_value' => safeInt($raceData['wins']),
                'wins' => safeInt($raceData['wins']),
                'podiums' => safeInt($raceData['podiums']),
                'recent_24h' => safeInt($raceData['recent_24h']),
                'recent_7d' => safeInt($raceData['recent_7d'])
            ],
            'top_players' => $topPlayers,
            'diagnostics' => [
                'query_ok' => true,
                'source_table' => 'tbl_race_participants',
                'season_mode' => 'persistent_all_time',
                'row_count' => safeInt($raceData['total_races'])
            ]
        ];

        if (safeInt($raceData['total_races']) > 0) {
            $gamesWithActivity++;
        }
        $summedUniquePlayerEntries += safeInt($raceData['unique_players']);
        $totalEntriesAcrossGames += safeInt($raceData['total_races']);
    } catch (Exception $e) {
        error_log("Discord Race stats error: " . $e->getMessage());
    }

    /**
     * 6. CHEESE RUMBLE
     * Season-window system using created_at range from current season start.
     */
    try {
        $rumbleStmt = $db->prepare("
            SELECT
                COUNT(DISTINCT cr.rumble_id) as total_rumbles,
                COUNT(CASE WHEN cr.created_at >= ? THEN 1 END) as recent_24h,
                COUNT(CASE WHEN cr.created_at >= ? THEN 1 END) as recent_7d
            FROM tbl_cheese_rumbles cr
            WHERE cr.created_at >= ?
        ");
        $rumbleStmt->execute([$recent24hIso, $recent7dIso, $seasonStartIso]);
        $rumbleCounts = $rumbleStmt->fetch(PDO::FETCH_ASSOC);

        $participantStmt = $db->prepare("
            SELECT
                COUNT(DISTINCT rp.user_id) as unique_players,
                COUNT(CASE WHEN rp.status = 'winner' OR rp.final_position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN rp.final_position <= 3 AND rp.final_position IS NOT NULL THEN 1 END) as podiums
            FROM tbl_rumble_participants rp
            JOIN tbl_cheese_rumbles cr ON rp.rumble_id = cr.rumble_id
            WHERE cr.created_at >= ?
        ");
        $participantStmt->execute([$seasonStartIso]);
        $participantData = $participantStmt->fetch(PDO::FETCH_ASSOC);

        $topPlayersStmt = $db->prepare("
            SELECT
                rp.user_id as discord_id,
                u.username,
                COUNT(CASE WHEN rp.status = 'winner' OR rp.final_position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN rp.final_position <= 3 AND rp.final_position IS NOT NULL THEN 1 END) as podiums,
                COUNT(*) as total_rumbles,
                SUM(COALESCE(rp.dspoinc_earned, 0)) as total_dspoinc,
                MIN(rp.final_position) as best_position
            FROM tbl_rumble_participants rp
            JOIN tbl_cheese_rumbles cr ON rp.rumble_id = cr.rumble_id
            LEFT JOIN tbl_users u ON rp.user_id = u.discord_id
            WHERE cr.created_at >= ?
            GROUP BY rp.user_id
            ORDER BY wins DESC, podiums DESC, best_position ASC
            LIMIT 10
        ");
        $topPlayersStmt->execute([$seasonStartIso]);
        $topPlayers = $topPlayersStmt->fetchAll(PDO::FETCH_ASSOC);

        $rumbleOverviewStmt = $db->prepare("
            SELECT
                cr.rumble_id,
                cr.creator_name,
                cr.status,
                cr.max_players,
                cr.created_at,
                cr.duration,
                cr.dspoinc_reward,
                COUNT(rp.user_id) as participant_count,
                (
                    SELECT u2.username
                    FROM tbl_rumble_participants rp2
                    JOIN tbl_users u2 ON rp2.user_id = u2.discord_id
                    WHERE rp2.rumble_id = cr.rumble_id
                    AND (rp2.status = 'winner' OR rp2.final_position = 1)
                    LIMIT 1
                ) as winner_name
            FROM tbl_cheese_rumbles cr
            LEFT JOIN tbl_rumble_participants rp ON cr.rumble_id = rp.rumble_id
            WHERE cr.created_at >= ?
            GROUP BY cr.rumble_id
            ORDER BY cr.created_at DESC
            LIMIT 10
        ");
        $rumbleOverviewStmt->execute([$seasonStartIso]);
        $rumbleOverview = $rumbleOverviewStmt->fetchAll(PDO::FETCH_ASSOC);

        addPlayerIdsToRegistry($allPlayerRegistry, $topPlayers);

        $response['data']['games']['cheese_rumble'] = [
            'game_name' => 'Cheese Rumble',
            'game_icon' => '💥',
            'status' => 'active',
            'data_mode' => 'season_window_activity',
            'season_data' => [
                'current_season' => $fullSeasonName,
                'total_entries' => safeInt($rumbleCounts['total_rumbles']),
                'total_rumbles' => safeInt($rumbleCounts['total_rumbles']),
                'unique_players' => safeInt($participantData['unique_players']),
                'best_value' => safeInt($participantData['wins']),
                'wins' => safeInt($participantData['wins']),
                'podiums' => safeInt($participantData['podiums']),
                'recent_24h' => safeInt($rumbleCounts['recent_24h']),
                'recent_7d' => safeInt($rumbleCounts['recent_7d']),
                'rumble_overview' => $rumbleOverview
            ],
            'top_players' => $topPlayers,
            'diagnostics' => [
                'query_ok' => true,
                'source_table' => 'tbl_cheese_rumbles + tbl_rumble_participants',
                'season_mode' => 'current_season_date_window',
                'row_count' => safeInt($rumbleCounts['total_rumbles'])
            ]
        ];

        if (safeInt($rumbleCounts['total_rumbles']) > 0) {
            $gamesWithActivity++;
        }
        $summedUniquePlayerEntries += safeInt($participantData['unique_players']);
        $totalEntriesAcrossGames += safeInt($rumbleCounts['total_rumbles']);
    } catch (Exception $e) {
        error_log("Cheese Rumble stats error: " . $e->getMessage());
    }

    // 🧀 7 Cheese Runner / Cheeseman Admin Stats
$cheesemanSeasonStmt = $db->prepare("
    SELECT
        COUNT(*) AS total_scores,
        COUNT(DISTINCT discord_id) AS unique_players,
        MAX(score) AS max_score,
        SUM(score) AS total_score,
        COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) AS recent_24h,
        COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) AS recent_7d
    FROM tbl_tetris_scores
    WHERE game = 'cheeseman'
");
$cheesemanSeasonStmt->execute();
$cheesemanSeasonData = $cheesemanSeasonStmt->fetch(PDO::FETCH_ASSOC);

$cheesemanTopStmt = $db->prepare("
    SELECT
        s.discord_id,
        COALESCE(u.username, s.discord_name, s.discord_id) AS discord_name,
        MAX(s.score) AS score,
        MAX(s.timestamp) AS timestamp
    FROM tbl_tetris_scores s
    LEFT JOIN tbl_users u ON u.discord_id = s.discord_id
    WHERE s.game = 'cheeseman'
    GROUP BY s.discord_id
    ORDER BY score DESC
    LIMIT 10
");
$cheesemanTopStmt->execute();
$cheesemanTopPlayers = $cheesemanTopStmt->fetchAll(PDO::FETCH_ASSOC);

$response['data']['games']['cheeseman'] = [
    'name' => 'Cheese Runner',
    'icon' => '🧀',
    'game_key' => 'cheeseman',
    'season_data' => [
        'total_scores' => (int)($cheesemanSeasonData['total_scores'] ?? 0),
        'total_games' => (int)($cheesemanSeasonData['total_scores'] ?? 0),
        'unique_players' => (int)($cheesemanSeasonData['unique_players'] ?? 0),
        'max_score' => (int)($cheesemanSeasonData['max_score'] ?? 0),
        'best_score' => (int)($cheesemanSeasonData['max_score'] ?? 0),
        'total_score' => (int)($cheesemanSeasonData['total_score'] ?? 0),
        'total_dspoinc' => (int)($cheesemanSeasonData['total_score'] ?? 0),
        'recent_24h' => (int)($cheesemanSeasonData['recent_24h'] ?? 0),
        'recent_7d' => (int)($cheesemanSeasonData['recent_7d'] ?? 0)
    ],
    'top_players' => $cheesemanTopPlayers,
    'leaderboard' => $cheesemanTopPlayers
];

if (safeInt($cheesemanSeasonData['total_scores'] ?? 0) > 0) {
    $gamesWithActivity++;
}

$summedUniquePlayerEntries += safeInt($cheesemanSeasonData['unique_players'] ?? 0);
$totalEntriesAcrossGames += safeInt($cheesemanSeasonData['total_scores'] ?? 0);
addPlayerIdsToRegistry($allPlayerRegistry, $cheesemanTopPlayers);

/**
 * 8. GLYPH MEMORY
 */
    try {
        $tableCheckStmt = $db->prepare("
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
            AND name = 'tbl_glyph_memory_scores'
            LIMIT 1
        ");
        $tableCheckStmt->execute();
        $glyphTableExists = $tableCheckStmt->fetchColumn();

        if ($glyphTableExists) {
            $stmt = $db->prepare("
                SELECT
                    COUNT(*) as total_runs,
                    COUNT(DISTINCT discord_id) as unique_players,
                    MIN(time_ms) as best_time_ms,
                    AVG(time_ms) as avg_time_ms,
                    COUNT(CASE WHEN timestamp >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                    COUNT(CASE WHEN timestamp >= datetime('now', '-7 days') THEN 1 END) as recent_7d
                FROM tbl_glyph_memory_scores
            ");
            $stmt->execute();
            $glyphData = $stmt->fetch(PDO::FETCH_ASSOC);

            $difficultyBreakdownStmt = $db->prepare("
                SELECT
                    difficulty,
                    COUNT(*) as total_runs,
                    COUNT(DISTINCT discord_id) as unique_players,
                    MIN(time_ms) as best_time_ms,
                    AVG(time_ms) as avg_time_ms
                FROM tbl_glyph_memory_scores
                GROUP BY difficulty
                ORDER BY difficulty ASC
            ");
            $difficultyBreakdownStmt->execute();
            $difficultyRows = $difficultyBreakdownStmt->fetchAll(PDO::FETCH_ASSOC);

            $difficultyBreakdown = [
                'easy' => [
                    'total_runs' => 0,
                    'unique_players' => 0,
                    'best_time_ms' => null,
                    'best_time_formatted' => '--:--',
                    'avg_time_ms' => null,
                    'avg_time_formatted' => '--:--'
                ],
                'medium' => [
                    'total_runs' => 0,
                    'unique_players' => 0,
                    'best_time_ms' => null,
                    'best_time_formatted' => '--:--',
                    'avg_time_ms' => null,
                    'avg_time_formatted' => '--:--'
                ],
                'hard' => [
                    'total_runs' => 0,
                    'unique_players' => 0,
                    'best_time_ms' => null,
                    'best_time_formatted' => '--:--',
                    'avg_time_ms' => null,
                    'avg_time_formatted' => '--:--'
                ]
            ];

            foreach ($difficultyRows as $row) {
                $difficultyKey = $row['difficulty'];
                if (!isset($difficultyBreakdown[$difficultyKey])) {
                    continue;
                }

                $difficultyBreakdown[$difficultyKey] = [
                    'total_runs' => safeInt($row['total_runs']),
                    'unique_players' => safeInt($row['unique_players']),
                    'best_time_ms' => $row['best_time_ms'] !== null ? (int) $row['best_time_ms'] : null,
                    'best_time_formatted' => formatGlyphTimeMs($row['best_time_ms']),
                    'avg_time_ms' => $row['avg_time_ms'] !== null ? (int) round($row['avg_time_ms']) : null,
                    'avg_time_formatted' => formatGlyphTimeMs($row['avg_time_ms'])
                ];
            }

            $topEasyStmt = $db->prepare("
                SELECT
                    discord_id,
                    COALESCE(discord_name, 'Guest') as username,
                    MIN(time_ms) as best_time_ms,
                    MIN(timestamp) as first_best_at
                FROM tbl_glyph_memory_scores
                WHERE difficulty = 'easy'
                GROUP BY discord_id, discord_name
                ORDER BY best_time_ms ASC, first_best_at ASC
                LIMIT 10
            ");
            $topEasyStmt->execute();
            $topEasy = $topEasyStmt->fetchAll(PDO::FETCH_ASSOC);

            $topMediumStmt = $db->prepare("
                SELECT
                    discord_id,
                    COALESCE(discord_name, 'Guest') as username,
                    MIN(time_ms) as best_time_ms,
                    MIN(timestamp) as first_best_at
                FROM tbl_glyph_memory_scores
                WHERE difficulty = 'medium'
                GROUP BY discord_id, discord_name
                ORDER BY best_time_ms ASC, first_best_at ASC
                LIMIT 10
            ");
            $topMediumStmt->execute();
            $topMedium = $topMediumStmt->fetchAll(PDO::FETCH_ASSOC);

            $topHardStmt = $db->prepare("
                SELECT
                    discord_id,
                    COALESCE(discord_name, 'Guest') as username,
                    MIN(time_ms) as best_time_ms,
                    MIN(timestamp) as first_best_at
                FROM tbl_glyph_memory_scores
                WHERE difficulty = 'hard'
                GROUP BY discord_id, discord_name
                ORDER BY best_time_ms ASC, first_best_at ASC
                LIMIT 10
            ");
            $topHardStmt->execute();
            $topHard = $topHardStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($topEasy as &$row) {
                $row['best_time_formatted'] = formatGlyphTimeMs($row['best_time_ms']);
            }
            unset($row);

            foreach ($topMedium as &$row) {
                $row['best_time_formatted'] = formatGlyphTimeMs($row['best_time_ms']);
            }
            unset($row);

            foreach ($topHard as &$row) {
                $row['best_time_formatted'] = formatGlyphTimeMs($row['best_time_ms']);
            }
            unset($row);

            addPlayerIdsToRegistry($allPlayerRegistry, $topEasy);
            addPlayerIdsToRegistry($allPlayerRegistry, $topMedium);
            addPlayerIdsToRegistry($allPlayerRegistry, $topHard);

            $response['data']['games']['glyph_memory'] = [
                'game_name' => 'Glyph Memory',
                'game_icon' => '🔮',
                'status' => 'active',
                'data_mode' => 'all_time_time_trial',
                'season_data' => [
                    'current_season' => $fullSeasonName,
                    'total_entries' => safeInt($glyphData['total_runs']),
                    'total_runs' => safeInt($glyphData['total_runs']),
                    'unique_players' => safeInt($glyphData['unique_players']),
                    'best_value' => $glyphData['best_time_ms'] !== null ? (int) $glyphData['best_time_ms'] : null,
                    'best_time_ms' => $glyphData['best_time_ms'] !== null ? (int) $glyphData['best_time_ms'] : null,
                    'best_time_formatted' => formatGlyphTimeMs($glyphData['best_time_ms']),
                    'avg_value' => $glyphData['avg_time_ms'] !== null ? (int) round($glyphData['avg_time_ms']) : null,
                    'avg_time_ms' => $glyphData['avg_time_ms'] !== null ? (int) round($glyphData['avg_time_ms']) : null,
                    'avg_time_formatted' => formatGlyphTimeMs($glyphData['avg_time_ms']),
                    'recent_24h' => safeInt($glyphData['recent_24h']),
                    'recent_7d' => safeInt($glyphData['recent_7d']),
                    'difficulty_breakdown' => $difficultyBreakdown
                ],
                'top_players' => [
                    'easy' => $topEasy,
                    'medium' => $topMedium,
                    'hard' => $topHard
                ],
                'diagnostics' => [
                    'query_ok' => true,
                    'source_table' => 'tbl_glyph_memory_scores',
                    'season_mode' => 'all_time_time_trial',
                    'row_count' => safeInt($glyphData['total_runs'])
                ]
            ];

            if (safeInt($glyphData['total_runs']) > 0) {
                $gamesWithActivity++;
            }
            $summedUniquePlayerEntries += safeInt($glyphData['unique_players']);
            $totalEntriesAcrossGames += safeInt($glyphData['total_runs']);
        } else {
            $response['data']['games']['glyph_memory'] = [
                'game_name' => 'Glyph Memory',
                'game_icon' => '🔮',
                'status' => 'inactive',
                'data_mode' => 'all_time_time_trial',
                'season_data' => [
                    'current_season' => $fullSeasonName,
                    'total_entries' => 0,
                    'total_runs' => 0,
                    'unique_players' => 0,
                    'best_value' => null,
                    'best_time_ms' => null,
                    'best_time_formatted' => '--:--',
                    'avg_value' => null,
                    'avg_time_ms' => null,
                    'avg_time_formatted' => '--:--',
                    'recent_24h' => 0,
                    'recent_7d' => 0,
                    'difficulty_breakdown' => [
                        'easy' => [],
                        'medium' => [],
                        'hard' => []
                    ]
                ],
                'top_players' => [
                    'easy' => [],
                    'medium' => [],
                    'hard' => []
                ],
                'diagnostics' => [
                    'query_ok' => false,
                    'source_table' => 'tbl_glyph_memory_scores',
                    'season_mode' => 'all_time_time_trial',
                    'row_count' => 0,
                    'warning' => 'Glyph table not found'
                ]
            ];
        }
    } catch (Exception $e) {
        error_log("Glyph Memory stats error: " . $e->getMessage());
    }

        /**
     * RECENT ACTIVITY
     * Safe first version: only normalized score-ladder games that match the current Overview renderer.
     */
    try {
        $recentActivityStmt = $db->prepare("
            SELECT game, discord_name, timestamp, score
            FROM tbl_tetris_scores
            WHERE game IN ('tetris', 'snake', 'space_invaders', 'cheeseman')
            AND season = ?
            ORDER BY timestamp DESC
            LIMIT 10
        ");
        $recentActivityStmt->execute([$currentSeason]);
        $recentActivity = $recentActivityStmt->fetchAll(PDO::FETCH_ASSOC);

        $response['data']['recent_activity'] = $recentActivity ?: [];
    } catch (Exception $e) {
        error_log('Recent activity stats error: ' . $e->getMessage());
        $response['data']['recent_activity'] = [];
    }

    $response['data']['overview']['games_with_activity'] = $gamesWithActivity;
    $response['data']['overview']['summed_unique_player_entries'] = $summedUniquePlayerEntries;
    $response['data']['overview']['true_unique_players'] = count($allPlayerRegistry);
    $response['data']['overview']['total_entries'] = $totalEntriesAcrossGames;

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("get-all-games-stats.php error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>