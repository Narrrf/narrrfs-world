<?php
/**
 * SEASON TESTER ROLE - PLAYER IDENTIFICATION SCRIPT
 * 
 * This script identifies all players who have contributed to Narrrf's World
 * by playing any of the 5 games and should receive the "Season Tester" role.
 */

// Database connection
$dbPath = '../../db/narrrf_world.sqlite';
$db = new PDO("sqlite:$dbPath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🎯 SEASON TESTER ROLE - PLAYER IDENTIFICATION\n";
echo "==============================================\n\n";

// Array to store all unique Discord IDs
$allPlayers = [];

// 1. Get players from Tetris, Snake, Space Invaders (tbl_tetris_scores)
echo "📊 Analyzing Tetris, Snake, Space Invaders players...\n";
$stmt = $db->prepare("
    SELECT DISTINCT discord_id, COUNT(*) as game_count
    FROM tbl_tetris_scores 
    WHERE discord_id IS NOT NULL AND discord_id != ''
    GROUP BY discord_id
    ORDER BY game_count DESC
");
$stmt->execute();
$tetrisPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tetrisPlayers as $player) {
    $allPlayers[$player['discord_id']] = [
        'discord_id' => $player['discord_id'],
        'tetris_snake_space_games' => $player['game_count'],
        'cheese_hunt_clicks' => 0,
        'discord_races' => 0,
        'total_contributions' => $player['game_count']
    ];
}

echo "Found " . count($tetrisPlayers) . " players from Tetris/Snake/Space Invaders\n\n";

// 2. Get players from Cheese Hunt (tbl_cheese_clicks)
echo "🧀 Analyzing Cheese Hunt players...\n";
$stmt = $db->prepare("
    SELECT DISTINCT user_wallet, COUNT(*) as click_count
    FROM tbl_cheese_clicks 
    WHERE user_wallet IS NOT NULL AND user_wallet != ''
    GROUP BY user_wallet
    ORDER BY click_count DESC
");
$stmt->execute();
$cheesePlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cheesePlayers as $player) {
    $discordId = $player['user_wallet'];
    if (isset($allPlayers[$discordId])) {
        $allPlayers[$discordId]['cheese_hunt_clicks'] = $player['click_count'];
        $allPlayers[$discordId]['total_contributions'] += $player['click_count'];
    } else {
        $allPlayers[$discordId] = [
            'discord_id' => $discordId,
            'tetris_snake_space_games' => 0,
            'cheese_hunt_clicks' => $player['click_count'],
            'discord_races' => 0,
            'total_contributions' => $player['click_count']
        ];
    }
}

echo "Found " . count($cheesePlayers) . " players from Cheese Hunt\n\n";

// 3. Get players from Discord Race (tbl_race_participants)
echo "🏁 Analyzing Discord Race players...\n";
$stmt = $db->prepare("
    SELECT DISTINCT user_id, COUNT(*) as race_count
    FROM tbl_race_participants 
    WHERE user_id IS NOT NULL AND user_id != ''
    GROUP BY user_id
    ORDER BY race_count DESC
");
$stmt->execute();
$racePlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($racePlayers as $player) {
    $discordId = $player['user_id'];
    if (isset($allPlayers[$discordId])) {
        $allPlayers[$discordId]['discord_races'] = $player['race_count'];
        $allPlayers[$discordId]['total_contributions'] += $player['race_count'];
    } else {
        $allPlayers[$discordId] = [
            'discord_id' => $discordId,
            'tetris_snake_space_games' => 0,
            'cheese_hunt_clicks' => 0,
            'discord_races' => $player['race_count'],
            'total_contributions' => $player['race_count']
        ];
    }
}

echo "Found " . count($racePlayers) . " players from Discord Race\n\n";

// Sort players by total contributions
uasort($allPlayers, function($a, $b) {
    return $b['total_contributions'] - $a['total_contributions'];
});

// Display results
echo "🎉 SEASON TESTER ROLE ELIGIBLE PLAYERS\n";
echo "=====================================\n";
echo "Total Players: " . count($allPlayers) . "\n\n";

echo "TOP CONTRIBUTORS:\n";
echo "================\n";
$count = 0;
foreach ($allPlayers as $player) {
    $count++;
    if ($count <= 20) { // Show top 20
        echo sprintf("%2d. Discord ID: %s\n", $count, $player['discord_id']);
        echo sprintf("    Tetris/Snake/Space: %d games\n", $player['tetris_snake_space_games']);
        echo sprintf("    Cheese Hunt: %d clicks\n", $player['cheese_hunt_clicks']);
        echo sprintf("    Discord Races: %d races\n", $player['discord_races']);
        echo sprintf("    Total Contributions: %d\n\n", $player['total_contributions']);
    }
}

// Generate CSV for Discord bot
echo "📄 Generating CSV for Discord bot role granting...\n";
$csvContent = "discord_id,tetris_snake_space_games,cheese_hunt_clicks,discord_races,total_contributions\n";
foreach ($allPlayers as $player) {
    $csvContent .= sprintf("%s,%d,%d,%d,%d\n", 
        $player['discord_id'],
        $player['tetris_snake_space_games'],
        $player['cheese_hunt_clicks'],
        $player['discord_races'],
        $player['total_contributions']
    );
}

file_put_contents('season_tester_eligible_players.csv', $csvContent);
echo "✅ CSV file created: season_tester_eligible_players.csv\n\n";

// Generate SQL for role granting
echo "🔧 Generating SQL for role granting...\n";
$sqlContent = "-- SEASON TESTER ROLE GRANTING SQL\n";
$sqlContent .= "-- Role ID: 1417279348989497532\n";
$sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

$sqlContent .= "INSERT OR IGNORE INTO tbl_role_grants (discord_id, role_id, granted_at, reason) VALUES\n";
$values = [];
foreach ($allPlayers as $player) {
    $values[] = sprintf("('%s', '1417279348989497532', CURRENT_TIMESTAMP, 'Season Tester - %d total contributions')", 
        $player['discord_id'], 
        $player['total_contributions']
    );
}
$sqlContent .= implode(",\n", $values) . ";\n";

file_put_contents('season_tester_role_grants.sql', $sqlContent);
echo "✅ SQL file created: season_tester_role_grants.sql\n\n";

echo "🎯 SUMMARY\n";
echo "==========\n";
echo "Total eligible players: " . count($allPlayers) . "\n";
echo "Role ID: 1417279348989497532\n";
echo "Files generated:\n";
echo "- season_tester_eligible_players.csv\n";
echo "- season_tester_role_grants.sql\n\n";

echo "🚀 Ready for Season Tester role implementation!\n";
?>
