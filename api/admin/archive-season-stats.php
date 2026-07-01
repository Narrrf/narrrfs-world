<?php
/**
 * Archive Season Stats API
 *
 * CRITICAL: Run this BEFORE each season reset.
 * Archives season-based competitive systems into historical tables.
 *
 * Season-based archive scope:
 * - Tetris / Snake / Space Invaders
 * - Cheeseman / Cheese Runner
 * - Labyrinth Blast
 * - Cheese Hunt snapshot
 * - Glyph Memory
 * - Discord Cheese Race
 * - Cheese Rumble
 *
 * Discord Cheese Race and Cheese Rumble are archived as compact per-user
 * historical summaries because profile.html displays them as current-season games.
 *
 * Persistent systems intentionally NOT archived/reset here:
 * - DSPOINC balances
 * - Staking
 * - Lab progression
 * - Genetic inventory
 * - Marketplace
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Return the SQLite PDO connection for local or production.
 */
function getSQLite3Connection() {
    $dbPath = (
        $_SERVER['HTTP_HOST'] === 'localhost' ||
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
    )
        ? '../../db/narrrf_world.sqlite'
        : '/var/www/html/db/narrrf_world.sqlite';

    if (!file_exists($dbPath)) {
        return null;
    }

    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Resolve the internal admin secret used for protected maintenance actions.
 */
function getExpectedSecret() {
    $candidates = [
        getenv('API_SECRET'),
        getenv('INTERNAL_API_SECRET'),
        getenv('DISCORD_SECRET')
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    return '';
}

/**
 * Read the caller authorization token from headers.
 */
function getProvidedSecret() {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    return trim($header);
}

$expectedSecret = getExpectedSecret();
$providedSecret = getProvidedSecret();

$isLocalhost = (
    $_SERVER['HTTP_HOST'] === 'localhost' ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
);

if (!$isLocalhost && ($expectedSecret === '' || $providedSecret !== $expectedSecret)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

$db = getSQLite3Connection();

if (!$db) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

try {
    $db->beginTransaction();

    // Ensure the Glyph historical table exists.
    $db->exec("
        CREATE TABLE IF NOT EXISTS tbl_historical_glyph_stats (
            historical_id INTEGER PRIMARY KEY AUTOINCREMENT,
            discord_id TEXT NOT NULL,
            discord_name TEXT,
            difficulty TEXT NOT NULL,
            season TEXT NOT NULL,
            total_runs INTEGER DEFAULT 0,
            best_time_ms INTEGER DEFAULT 0,
            avg_time_ms REAL DEFAULT 0,
            best_pairs_matched INTEGER DEFAULT 0,
            season_start_date TEXT,
            season_end_date TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(discord_id, difficulty, season)
        )
    ");

    // Get current active season.
    $seasonStmt = $db->query("
        SELECT season_name, start_date, end_date
        FROM tbl_seasons
        WHERE is_active = 1
        ORDER BY season_id DESC
        LIMIT 1
    ");
    $currentSeason = $seasonStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentSeason) {
        throw new Exception('No active season found');
    }

    $seasonName = $currentSeason['season_name'];
    $seasonStart = $currentSeason['start_date'];
    $seasonEnd = $currentSeason['end_date'];

    // Archive arcade game stats.
    $archiveGamesStmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_historical_stats
            (
                discord_id,
                game,
                season,
                total_games,
                best_score,
                total_score,
                avg_score,
                season_start_date,
                season_end_date
            )
        SELECT
            discord_id,
            game,
            :season as season,
            COUNT(*) as total_games,
            MAX(score) as best_score,
            SUM(score) as total_score,
            AVG(score) as avg_score,
            :season_start as season_start_date,
            :season_end as season_end_date
                FROM tbl_tetris_scores
        WHERE game IN ('tetris', 'snake', 'space_invaders', 'cheeseman', 'labyrinth_blast')
          AND COALESCE(season, :season) = :season
        GROUP BY discord_id, game
    ");
    $archiveGamesStmt->execute([
        ':season' => $seasonName,
        ':season_start' => $seasonStart,
        ':season_end' => $seasonEnd
    ]);
    $gamesArchived = $archiveGamesStmt->rowCount();

    // Archive Cheese Hunt snapshot.
    $archiveCheeseStmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_historical_cheese_stats
            (
                user_wallet,
                season,
                total_clicks,
                quest_clicks,
                days_played,
                season_start_date,
                season_end_date
            )
        SELECT
            user_wallet,
            :season as season,
            COUNT(*) as total_clicks,
            COUNT(CASE WHEN quest_id IS NOT NULL THEN 1 END) as quest_clicks,
            COUNT(DISTINCT DATE(timestamp)) as days_played,
            :season_start as season_start_date,
            :season_end as season_end_date
        FROM tbl_cheese_clicks
        GROUP BY user_wallet
    ");
    $archiveCheeseStmt->execute([
        ':season' => $seasonName,
        ':season_start' => $seasonStart,
        ':season_end' => $seasonEnd
    ]);
    $cheeseArchived = $archiveCheeseStmt->rowCount();

    // Archive Glyph Memory stats.
    $archiveGlyphStmt = $db->prepare("
        INSERT OR REPLACE INTO tbl_historical_glyph_stats
            (
                discord_id,
                discord_name,
                difficulty,
                season,
                total_runs,
                best_time_ms,
                avg_time_ms,
                best_pairs_matched,
                season_start_date,
                season_end_date
            )
        SELECT
            discord_id,
            MAX(discord_name) as discord_name,
            difficulty,
            :season as season,
            COUNT(*) as total_runs,
            MIN(time_ms) as best_time_ms,
            AVG(time_ms) as avg_time_ms,
            MAX(pairs_matched) as best_pairs_matched,
            :season_start as season_start_date,
            :season_end as season_end_date
        FROM tbl_glyph_memory_scores
        WHERE COALESCE(season, :season) = :season
        GROUP BY discord_id, difficulty
    ");
    $archiveGlyphStmt->execute([
    ':season' => $seasonName,
    ':season_start' => $seasonStart,
    ':season_end' => $seasonEnd
]);
$glyphArchived = $archiveGlyphStmt->rowCount();

/**
 * Archive Discord Cheese Race current-season stats.
 *
 * Plain language for DEVS:
 * Discord Cheese Race is shown on the profile as a current-season game.
 * The profile reads tbl_race_participants by season first, then by finished_at
 * inside the active season window. This archive stores a compact Season summary
 * in tbl_historical_stats using game = 'discord_race'.
 */
$archiveRaceStmt = $db->prepare("
    INSERT OR REPLACE INTO tbl_historical_stats
        (
            discord_id,
            game,
            season,
            total_games,
            best_score,
            total_score,
            avg_score,
            season_start_date,
            season_end_date
        )
    SELECT
        user_id as discord_id,
        'discord_race' as game,
        :season as season,
        COUNT(*) as total_games,
        COALESCE(MIN(CASE WHEN position IS NOT NULL AND position > 0 THEN position END), 0) as best_score,
        COALESCE(SUM(dspoinc_earned), 0) as total_score,
        COALESCE(AVG(dspoinc_earned), 0) as avg_score,
        :season_start as season_start_date,
        :season_end as season_end_date
    FROM tbl_race_participants
    WHERE
        (
            season = :season
            OR season LIKE :season_prefix
            OR (
                finished_at IS NOT NULL
                AND datetime(finished_at) >= datetime(:season_start)
                AND datetime(finished_at) < datetime(:season_end)
            )
        )
    GROUP BY user_id
");
$archiveRaceStmt->execute([
    ':season' => $seasonName,
    ':season_prefix' => $seasonName . '%',
    ':season_start' => $seasonStart,
    ':season_end' => $seasonEnd
]);
$raceArchived = $archiveRaceStmt->rowCount();

/**
 * Archive Cheese Rumble current-season stats.
 *
 * Plain language for DEVS:
 * Cheese Rumble is shown on the profile as a current-season game.
 * The profile reads tbl_rumble_participants by season or season window.
 * This archive stores a compact Season summary in tbl_historical_stats
 * using game = 'cheese_rumble'.
 */
$archiveRumbleStmt = $db->prepare("
    INSERT OR REPLACE INTO tbl_historical_stats
        (
            discord_id,
            game,
            season,
            total_games,
            best_score,
            total_score,
            avg_score,
            season_start_date,
            season_end_date
        )
    SELECT
        user_id as discord_id,
        'cheese_rumble' as game,
        :season as season,
        COUNT(*) as total_games,
        COALESCE(MIN(CASE WHEN final_position IS NOT NULL AND final_position > 0 THEN final_position END), 0) as best_score,
        COALESCE(SUM(dspoinc_earned), 0) as total_score,
        COALESCE(AVG(dspoinc_earned), 0) as avg_score,
        :season_start as season_start_date,
        :season_end as season_end_date
    FROM tbl_rumble_participants
    WHERE
        (
            season = :season
            OR season LIKE :season_prefix
            OR (
                datetime(updated_at) >= datetime(:season_start)
                AND datetime(updated_at) < datetime(:season_end)
            )
            OR (
                datetime(joined_at) >= datetime(:season_start)
                AND datetime(joined_at) < datetime(:season_end)
            )
        )
    GROUP BY user_id
");
$archiveRumbleStmt->execute([
    ':season' => $seasonName,
    ':season_prefix' => $seasonName . '%',
    ':season_start' => $seasonStart,
    ':season_end' => $seasonEnd
]);
$rumbleArchived = $archiveRumbleStmt->rowCount();

$db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Season stats archived successfully',
        'season_archived' => $seasonName,
        'archived_at' => gmdate('Y-m-d H:i:s') . ' UTC',
        'stats' => [
    'games_archived' => $gamesArchived,
    'cheese_users_archived' => $cheeseArchived,
    'glyph_rows_archived' => $glyphArchived,
    'discord_race_users_archived' => $raceArchived,
    'cheese_rumble_users_archived' => $rumbleArchived
]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    echo json_encode([
        'success' => false,
        'error' => 'Failed to archive season stats: ' . $e->getMessage()
    ]);
}
?>