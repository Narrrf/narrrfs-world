<?php
// 🧠 Cheese Architect API — Get Leaderboard from SQLite
header('Content-Type: application/json');

// Use the correct database path that contains all user data
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🔍 Get the active season from tbl_seasons table
    $seasonStmt = $db->prepare("
        SELECT season_name
        FROM tbl_seasons
        WHERE is_active = 1
        ORDER BY start_date DESC
        LIMIT 1
    ");
    $seasonStmt->execute();
    $currentSeason = $seasonStmt->fetchColumn() ?: 'Season 9';

    // 🔍 Get the previous season (for frozen leaderboard fallback)
    $prevSeasonStmt = $db->prepare("
        SELECT season_name
        FROM tbl_seasons
        WHERE is_active = 0
        ORDER BY end_date DESC
        LIMIT 1
    ");
    $prevSeasonStmt->execute();
    $previousSeason = $prevSeasonStmt->fetchColumn() ?: 'Season 8';

    error_log("Current season: $currentSeason, Previous season: $previousSeason");

    // Helper function to get highest role from user roles array
    function getHighestRole($roles) {
        if (empty($roles) || !is_array($roles)) {
            return null;
        }

        // Role priority based on multiplier system (higher priority = lower number)
        $rolePriority = [
            'Monthly Tetris Legend' => 0.5,
            'Monthly Snake Legend' => 0.5,
            'Monthly Cheese Invaders Legend' => 0.5,
            'VIP Holder' => 1,
            '🎴 VIP Holder' => 1,
            'Holder' => 2,
            '🏆 Holder' => 2,
            'Champion' => 3,
            'Season Tester' => 4,
            'WL' => 4,
            'Early Bird' => 5,
            'Cheese Hunter' => 6,
            '🧀 Cheese Hunter' => 6
        ];

        $highestRole = null;
        $highestPriority = 999;

        foreach ($roles as $role) {
            // Clean role name (remove emojis but preserve original)
            $cleanRole = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $role);
            $cleanRole = trim($cleanRole);

            if (isset($rolePriority[$cleanRole])) {
                $priority = $rolePriority[$cleanRole];
                if ($priority < $highestPriority) {
                    $highestPriority = $priority;
                    $highestRole = $role;
                }
            }

            if (isset($rolePriority[$role])) {
                $priority = $rolePriority[$role];
                if ($priority < $highestPriority) {
                    $highestPriority = $priority;
                    $highestRole = $role;
                }
            }
        }

        return $highestRole;
    }

    // Helper function to enrich leaderboard entry with user info (avatar, roles)
    function enrichLeaderboardEntry($db, $discordId, $entry) {
        // Get user info from tbl_users
        $userStmt = $db->prepare("
            SELECT username, avatar_url
            FROM tbl_users
            WHERE discord_id = ?
        ");
        $userStmt->execute([$discordId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $entry['discord_name'] = $user['username'] ?? ($entry['discord_name'] ?? $discordId);
            $entry['avatar_url'] = $user['avatar_url'] ?? null;
        }

        // Get user roles from tbl_user_roles
        $rolesStmt = $db->prepare("
            SELECT role_name
            FROM tbl_user_roles
            WHERE user_id = ?
        ");
        $rolesStmt->execute([$discordId]);
        $roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

        $entry['roles'] = $roles;
        $entry['highest_role'] = getHighestRole($roles);

        return $entry;
    }

    // Helper function to get leaderboard from current season or fallback to previous
    function getLeaderboard($db, $game, $currentSeason, $previousSeason, $useFrozenLeaderboard = false) {
        if ($useFrozenLeaderboard) {
            error_log("Using frozen $previousSeason leaderboard for $game (total scores across all games < 3)");

            $historicalStmt = $db->prepare("
                SELECT
                    hs.discord_id,
                    COALESCE(u.username, hs.discord_id) as discord_name,
                    hs.best_score as score,
                    hs.season_end_date as timestamp
                FROM tbl_historical_stats hs
                LEFT JOIN tbl_users u ON hs.discord_id = u.discord_id
                WHERE hs.game = ? AND hs.season = ?
                ORDER BY hs.best_score DESC, hs.season_end_date ASC
                LIMIT 10
            ");
            $historicalStmt->execute([$game, $previousSeason]);
            $historicalLeaderboard = $historicalStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($historicalLeaderboard as &$entry) {
                $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
            }
            unset($entry);

            return [
                'leaderboard' => $historicalLeaderboard,
                'is_frozen' => true,
                'season_shown' => $previousSeason
            ];
        }

        $currentStmt = $db->prepare("
            SELECT
    discord_id,
    MAX(discord_name) as discord_name,
    MAX(score) as score,
    MIN(timestamp) as timestamp
FROM tbl_tetris_scores
WHERE game = ? AND season = ?
GROUP BY discord_id
ORDER BY score DESC, timestamp ASC
LIMIT 10
        ");
        $currentStmt->execute([$game, $currentSeason]);
        $currentLeaderboard = $currentStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($currentLeaderboard as &$entry) {
            $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
        }
        unset($entry);

        return [
            'leaderboard' => $currentLeaderboard,
            'is_frozen' => false,
            'season_shown' => $currentSeason
        ];
    }

    // Get season dates for timestamp filtering
    $seasonDatesStmt = $db->prepare("
        SELECT start_date, end_date
        FROM tbl_seasons
        WHERE is_active = 1
        ORDER BY start_date DESC
        LIMIT 1
    ");
    $seasonDatesStmt->execute();
    $seasonDates = $seasonDatesStmt->fetch(PDO::FETCH_ASSOC);
    $currentSeasonStart = $seasonDates['start_date'] ?? null;
    $currentSeasonEnd = $seasonDates['end_date'] ?? null;

    if ($currentSeasonStart && strpos($currentSeasonStart, ' ') !== false) {
        $datePart = explode(' ', $currentSeasonStart)[0];
        $currentSeasonStart = $datePart . ' 00:00:00';
    }

    // For Cheese Rumble: exclude data from season start date (only count from next day onwards)
    $seasonStartNextDay = null;
    if ($currentSeasonStart) {
        $seasonStartDate = explode(' ', $currentSeasonStart)[0];
        $seasonStartNextDay = date('Y-m-d 00:00:00', strtotime($seasonStartDate . ' +1 day'));
    }

    // 🐛 BUG FIX: Check total scores across ALL 3 games combined (not per game)
    $totalScoresStmt = $db->prepare("
        SELECT COUNT(*) as total_scores
        FROM tbl_tetris_scores
        WHERE season = ? AND game IN ('tetris', 'snake', 'space_invaders')
    ");
    $totalScoresStmt->execute([$currentSeason]);
    $totalScoresAcrossAllGames = $totalScoresStmt->fetchColumn() ?: 0;

    error_log("Total scores across all 3 games in $currentSeason: $totalScoresAcrossAllGames");

    $useFrozenLeaderboard = ($totalScoresAcrossAllGames < 3);

    // Core arcade leaderboards
    $tetrisResult = getLeaderboard($db, 'tetris', $currentSeason, $previousSeason, $useFrozenLeaderboard);
    $snakeResult = getLeaderboard($db, 'snake', $currentSeason, $previousSeason, $useFrozenLeaderboard);
    $spaceInvadersResult = getLeaderboard($db, 'space_invaders', $currentSeason, $previousSeason, $useFrozenLeaderboard);

    foreach ($spaceInvadersResult['leaderboard'] as &$entry) {
        $entry['score'] = round($entry['score']);
    }
    unset($entry);

        // Helper function to get Mouse Genesis Leaderboard
    function getLabPowerLeaderboard($db) {
    $stmt = $db->prepare("
        SELECT
            COALESCE(u.username, t.last_owner_user_id) AS discord_name,
            t.last_owner_user_id AS discord_id,
            SUM(t.current_level) AS total_lab_power,
            MAX(t.current_level) AS highest_trait_level
        FROM tbl_nft_trait_upgrades t
        LEFT JOIN tbl_users u
            ON u.discord_id = t.last_owner_user_id
        WHERE COALESCE(t.collection, 'genesis') = 'genesis'
          AND COALESCE(t.last_owner_user_id, '') <> ''
        GROUP BY t.last_owner_user_id
        ORDER BY total_lab_power DESC, highest_trait_level DESC, t.last_owner_user_id ASC
        LIMIT 10
    ");
    $stmt->execute();

    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($leaderboard as &$entry) {
        $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
    }
    unset($entry);

    return $leaderboard;
}

// Helper function to get Mouse Genetic Leaderboard
function getMouseLeaderboard($db) {
    $stmt = $db->prepare("
        SELECT
            COALESCE(u.username, g.user_id) AS discord_name,
            g.user_id AS discord_id,
            SUM(g.current_level) AS total_level,
            COUNT(*) AS trait_count,
            MAX(g.current_level) AS highest_trait_level
        FROM tbl_user_genetic_items g
        LEFT JOIN tbl_users u ON u.discord_id = g.user_id
        GROUP BY g.user_id
        ORDER BY total_level DESC, trait_count DESC, highest_trait_level DESC
        LIMIT 10
    ");
    $stmt->execute();

    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($leaderboard as &$entry) {
        $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
    }
    unset($entry);

    return $leaderboard;
}

    // Helper function to get Cheese Hunt leaderboard
    function getCheeseHuntLeaderboard($db, $currentSeason, $previousSeason, $currentSeasonStart, $currentSeasonEnd, $useFrozenLeaderboard = false) {
        if ($useFrozenLeaderboard) {
            return [
                'leaderboard' => [],
                'is_frozen' => true,
                'season_shown' => $previousSeason
            ];
        }

        $seasonFilters = [
            ['condition' => 'season = ?', 'label' => 'exact'],
            ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
        ];

        $leaderboard = [];

        foreach ($seasonFilters as $filter) {
            $stmt = $db->prepare("
                SELECT
                    user_wallet as discord_id,
                    COUNT(*) as score,
                    MIN(timestamp) as timestamp
                FROM tbl_cheese_clicks
                WHERE " . $filter['condition'] . "
                GROUP BY user_wallet
                ORDER BY score DESC, timestamp ASC
                LIMIT 10
            ");
            $stmt->execute([$currentSeason]);
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($leaderboard)) {
                break;
            }
        }

        if (empty($leaderboard) && $currentSeasonStart && $currentSeasonEnd) {
            $stmt = $db->prepare("
                SELECT
                    user_wallet as discord_id,
                    COUNT(*) as score,
                    MIN(timestamp) as timestamp
                FROM tbl_cheese_clicks
                WHERE timestamp >= ?
                AND timestamp <= ?
                GROUP BY user_wallet
                ORDER BY score DESC, timestamp ASC
                LIMIT 10
            ");
            $stmt->execute([
                $currentSeasonStart,
                $currentSeasonEnd
            ]);
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($leaderboard as &$entry) {
            $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
        }
        unset($entry);

        return [
            'leaderboard' => $leaderboard,
            'is_frozen' => false,
            'season_shown' => $currentSeason
        ];
    }

    // Helper function to get Discord Race leaderboard
    function getDiscordRaceLeaderboard($db, $currentSeason, $previousSeason, $currentSeasonStart, $currentSeasonEnd, $useFrozenLeaderboard = false) {
        if ($useFrozenLeaderboard) {
            return [
                'leaderboard' => [],
                'is_frozen' => true,
                'season_shown' => $previousSeason
            ];
        }

        $seasonFilters = [
            ['condition' => 'season = ?', 'label' => 'exact'],
            ['condition' => 'season LIKE ? || "%"', 'label' => 'prefix']
        ];

        $leaderboard = [];

        foreach ($seasonFilters as $filter) {
            $stmt = $db->prepare("
                SELECT
                    user_id as discord_id,
                    COUNT(*) as total_races,
                    COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
                    MIN(finished_at) as timestamp
                FROM tbl_race_participants
                WHERE " . $filter['condition'] . "
                GROUP BY user_id
                ORDER BY total_races DESC, wins DESC, timestamp ASC
                LIMIT 10
            ");
            $stmt->execute([$currentSeason]);
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($leaderboard)) {
                break;
            }
        }

        if (empty($leaderboard) && $currentSeasonStart && $currentSeasonEnd) {
            $stmt = $db->prepare("
                SELECT
                    user_id as discord_id,
                    COUNT(*) as total_races,
                    COUNT(CASE WHEN position = 1 THEN 1 END) as wins,
                    MIN(finished_at) as timestamp
                FROM tbl_race_participants
                WHERE finished_at >= ?
                AND finished_at <= ?
                GROUP BY user_id
                ORDER BY total_races DESC, wins DESC, timestamp ASC
                LIMIT 10
            ");
            $stmt->execute([
                $currentSeasonStart,
                $currentSeasonEnd
            ]);
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($leaderboard as &$entry) {
            $entry['score'] = (int) $entry['total_races'];
        }
        unset($entry);

        foreach ($leaderboard as &$entry) {
            $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
        }
        unset($entry);

        return [
            'leaderboard' => $leaderboard,
            'is_frozen' => false,
            'season_shown' => $currentSeason
        ];
    }

    // Helper function to get Cheese Rumble leaderboard
    function getCheeseRumbleLeaderboard($db, $currentSeason, $previousSeason, $currentSeasonStart, $currentSeasonEnd, $seasonStartNextDay, $useFrozenLeaderboard = false) {
        if ($useFrozenLeaderboard) {
            return [
                'leaderboard' => [],
                'is_frozen' => true,
                'season_shown' => $previousSeason
            ];
        }

        if (!$seasonStartNextDay || !$currentSeasonEnd) {
            return [
                'leaderboard' => [],
                'is_frozen' => false,
                'season_shown' => $currentSeason
            ];
        }

        $stmt = $db->prepare("
            SELECT
                rp.user_id as discord_id,
                COUNT(*) as total_rumbles,
                COUNT(CASE WHEN rp.final_position = 1 THEN 1 END) as wins,
                MIN(cr.created_at) as timestamp
            FROM tbl_rumble_participants rp
            JOIN tbl_cheese_rumbles cr ON rp.rumble_id = cr.rumble_id
            WHERE datetime(replace(replace(cr.created_at, 'T', ' '), 'Z', '')) >= datetime(?)
            AND datetime(replace(replace(cr.created_at, 'T', ' '), 'Z', '')) < datetime(?)
            GROUP BY rp.user_id
            ORDER BY total_rumbles DESC, wins DESC, timestamp ASC
            LIMIT 10
        ");
        $stmt->execute([
            $seasonStartNextDay,
            $currentSeasonEnd
        ]);
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($leaderboard as &$entry) {
            $entry['score'] = (int) $entry['total_rumbles'];
        }
        unset($entry);

        foreach ($leaderboard as &$entry) {
            $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
        }
        unset($entry);

        return [
            'leaderboard' => $leaderboard,
            'is_frozen' => false,
            'season_shown' => $currentSeason
        ];
    }

    // Helper function to get DSPOINC earnings leaderboard (season-based)
    function getDspoincEarningsLeaderboard($db, $currentSeason, $currentSeasonStart, $currentSeasonEnd, $useFrozenLeaderboard = false) {
        if ($useFrozenLeaderboard || !$currentSeasonStart || !$currentSeasonEnd) {
            return [
                'leaderboard' => [],
                'is_frozen' => true,
                'season_shown' => $currentSeason
            ];
        }

        $stmt = $db->prepare("
            SELECT
                user_id as discord_id,
                SUM(amount) as total_dspoinc,
                MIN(timestamp) as first_earned,
                MAX(timestamp) as last_earned
            FROM tbl_score_adjustments
            WHERE action = 'add'
            AND amount > 0
            AND timestamp >= ?
            AND timestamp <= ?
            GROUP BY user_id
            ORDER BY total_dspoinc DESC, first_earned ASC
            LIMIT 10
        ");
        $stmt->execute([
            $currentSeasonStart,
            $currentSeasonEnd
        ]);
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($leaderboard as &$entry) {
            $entry['score'] = (int) $entry['total_dspoinc'];
        }
        unset($entry);

        foreach ($leaderboard as &$entry) {
            $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
        }
        unset($entry);

        return [
            'leaderboard' => $leaderboard,
            'is_frozen' => false,
            'season_shown' => $currentSeason
        ];
    }

// Helper function to get Glyph Memory leaderboard for the ACTIVE season only
function getGlyphMemoryLeaderboard($db, $seasonName) {
    $difficulties = ['easy', 'medium', 'hard'];
    $result = [];

    foreach ($difficulties as $difficulty) {
        $stmt = $db->prepare("
            SELECT
                discord_id,
                COALESCE(MAX(discord_name), 'Guest') as discord_name,
                difficulty,
                MIN(time_ms) as best_time_ms,
                MIN(timestamp) as timestamp
            FROM tbl_glyph_memory_scores
            WHERE difficulty = ?
              AND COALESCE(season, ?) = ?
            GROUP BY discord_id, difficulty
            ORDER BY best_time_ms ASC, timestamp ASC
            LIMIT 10
        ");
        $stmt->execute([$difficulty, $seasonName, $seasonName]);
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($scores as &$entry) {
            $totalSec = floor($entry['best_time_ms'] / 1000);
            $min = floor($totalSec / 60);
            $sec = $totalSec % 60;
            $entry['best_time_formatted'] = sprintf("%02d:%02d", $min, $sec);
            $entry = enrichLeaderboardEntry($db, $entry['discord_id'], $entry);
        }
        unset($entry);

        $result[$difficulty] = $scores;
    }

    return $result;
}

    // Get non-arcade leaderboards
    $dspoincEarningsResult = getDspoincEarningsLeaderboard(
        $db,
        $currentSeason,
        $currentSeasonStart,
        $currentSeasonEnd,
        $useFrozenLeaderboard
    );

    $cheeseHuntResult = getCheeseHuntLeaderboard(
        $db,
        $currentSeason,
        $previousSeason,
        $currentSeasonStart,
        $currentSeasonEnd,
        $useFrozenLeaderboard
    );

    $discordRaceResult = getDiscordRaceLeaderboard(
        $db,
        $currentSeason,
        $previousSeason,
        $currentSeasonStart,
        $currentSeasonEnd,
        $useFrozenLeaderboard
    );

    $cheeseRumbleResult = getCheeseRumbleLeaderboard(
        $db,
        $currentSeason,
        $previousSeason,
        $currentSeasonStart,
        $currentSeasonEnd,
        $seasonStartNextDay,
        $useFrozenLeaderboard
    );

$glyphMemoryLeaderboard = [];
$tableCheck = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_glyph_memory_scores'");
$tableCheck->execute();
if ($tableCheck->fetch()) {
    $glyphMemoryLeaderboard = getGlyphMemoryLeaderboard($db, $currentSeason);
}

$displaySeason = $useFrozenLeaderboard ? $previousSeason : $currentSeason;
$isFrozen = $useFrozenLeaderboard;


// Add new leaderboard loads here + the function on top + update the html and tables repeat 
echo json_encode([
    'success' => true,
    'current_season' => $currentSeason,
    'display_season' => $displaySeason,
    'is_frozen' => $isFrozen,
    'dspoinc_earnings' => $dspoincEarningsResult['leaderboard'],
    'tetris' => $tetrisResult['leaderboard'],
    'snake' => $snakeResult['leaderboard'],
    'space_invaders' => $spaceInvadersResult['leaderboard'],
    'cheese_hunt' => $cheeseHuntResult['leaderboard'],
    'discord_race' => $discordRaceResult['leaderboard'],
    'cheese_rumble' => $cheeseRumbleResult['leaderboard'],
    'glyph_memory' => $glyphMemoryLeaderboard,
    'mouse_leaderboard' => getMouseLeaderboard($db),
    'lab_power_leaderboard' => getLabPowerLeaderboard($db)
]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>