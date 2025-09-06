<?php
/**
 * Discord Race Overview API
 * Provides comprehensive race statistics and overview data for admin interface
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get comprehensive race statistics
    $stats = getRaceStatistics($pdo);
    
    // Get top racers
    $topRacers = getTopRacers($pdo);
    
    // Get race overview with detailed participant data
    $raceOverview = getRaceOverview($pdo);
    
    // Get performance metrics
    $performance = getPerformanceMetrics($pdo);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'race_data' => [
                'stats' => $stats,
                'top_racers' => $topRacers,
                'race_overview' => $raceOverview,
                'performance' => $performance,
                'total_races' => $stats['total_races'],
                'total_participants' => $stats['participants'],
                'wins' => $stats['winners'],
                'recent_activity' => getRecentActivity($pdo)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

/**
 * Get comprehensive race statistics
 */
function getRaceStatistics($pdo) {
    // Total races
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tbl_cheese_races");
    $totalRaces = $stmt->fetch()['total'];
    
    // Active races
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM tbl_cheese_races WHERE status = 'active'");
    $activeRaces = $stmt->fetch()['active'];
    
    // Waiting races
    $stmt = $pdo->query("SELECT COUNT(*) as waiting FROM tbl_cheese_races WHERE status = 'waiting'");
    $waitingRaces = $stmt->fetch()['waiting'];
    
    // Finished races
    $stmt = $pdo->query("SELECT COUNT(*) as finished FROM tbl_cheese_races WHERE status = 'finished'");
    $finishedRaces = $stmt->fetch()['finished'];
    
    // Total participants (unique users who have participated)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as participants FROM tbl_race_participants");
    $totalParticipants = $stmt->fetch()['participants'];
    
    // Total wins (races where position = 1)
    $stmt = $pdo->query("SELECT COUNT(*) as wins FROM tbl_race_participants WHERE position = 1");
    $totalWins = $stmt->fetch()['wins'];
    
    // Recent races (last 24h)
    $stmt = $pdo->query("SELECT COUNT(*) as recent FROM tbl_cheese_races WHERE created_at >= datetime('now', '-1 day')");
    $recentRaces = $stmt->fetch()['recent'];
    
    return [
        'total_races' => (int)$totalRaces,
        'active_races' => (int)$activeRaces,
        'waiting_races' => (int)$waitingRaces,
        'finished_races' => (int)$finishedRaces,
        'participants' => (int)$totalParticipants,
        'winners' => (int)$totalWins,
        'recent_24h' => (int)$recentRaces
    ];
}

/**
 * Get top racers with detailed stats
 */
function getTopRacers($pdo) {
    $stmt = $pdo->query("
        SELECT 
            rp.user_id,
            rp.username,
            COUNT(DISTINCT rp.race_id) as races_participated,
            COUNT(CASE WHEN rp.position = 1 THEN 1 END) as wins,
            AVG(rp.cheese_count) as avg_cheese,
            MAX(rp.cheese_count) as best_cheese,
            SUM(rp.dspoinc_earned) as total_dspoinc
        FROM tbl_race_participants rp
        GROUP BY rp.user_id, rp.username
        ORDER BY wins DESC, avg_cheese DESC
        LIMIT 10
    ");
    
    $topRacers = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $topRacers[] = [
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'races_participated' => (int)$row['races_participated'],
            'wins' => (int)$row['wins'],
            'avg_cheese' => round((float)$row['avg_cheese'], 2),
            'best_cheese' => (int)$row['best_cheese'],
            'total_dspoinc' => (int)$row['total_dspoinc']
        ];
    }
    
    return $topRacers;
}

/**
 * Get detailed race overview with participant data
 */
function getRaceOverview($pdo) {
    $stmt = $pdo->query("
        SELECT 
            cr.race_id,
            cr.creator_id,
            cr.creator_name,
            cr.status,
            cr.max_players,
            cr.duration,
            cr.dspoinc_reward,
            cr.role_reward,
            cr.comment,
            cr.created_at,
            cr.started_at,
            cr.ended_at,
            COUNT(rp.id) as participant_count,
            COALESCE(MAX(rp.cheese_count), 0) as max_cheese,
            COALESCE(AVG(rp.cheese_count), 0) as avg_cheese,
            COALESCE(SUM(rp.dspoinc_earned), 0) as total_dspoinc_earned
        FROM tbl_cheese_races cr
        LEFT JOIN tbl_race_participants rp ON cr.race_id = rp.race_id
        GROUP BY cr.race_id, cr.creator_id, cr.creator_name, cr.status, cr.max_players, 
                 cr.duration, cr.dspoinc_reward, cr.role_reward, cr.comment, cr.created_at, 
                 cr.started_at, cr.ended_at
        ORDER BY cr.created_at DESC
        LIMIT 50
    ");
    
    $races = [];
    $raceCount = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $raceCount++;
        // Debug: Log first few races to see what's being processed
        if ($raceCount <= 5) {
            error_log("Processing race #$raceCount: " . $row['race_id'] . " - " . $row['created_at']);
        }
        // Format dates - use ISO format for admin interface compatibility
        try {
            $createdAt = new DateTime($row['created_at']);
            $formattedDate = $createdAt->format('Y-m-d\TH:i:s.000\Z');
        } catch (Exception $e) {
            // Fallback: use the original date if DateTime parsing fails
            error_log("DateTime parsing error for: " . $row['created_at'] . " - " . $e->getMessage());
            $formattedDate = $row['created_at']; // Use original format
        }
        
        // Calculate duration if race has started and ended
        $duration = 'N/A';
        if ($row['started_at'] && $row['ended_at']) {
            $startTime = new DateTime($row['started_at']);
            $endTime = new DateTime($row['ended_at']);
            $duration = $endTime->diff($startTime)->format('%H:%M:%S');
        }
        
        $races[] = [
            'id' => $row['race_id'],  // Use 'id' for admin interface compatibility
            'race_id' => $row['race_id'],
            'creator_id' => $row['creator_id'],
            'creator_name' => $row['creator_name'],
            'status' => $row['status'],
            'max_players' => (int)$row['max_players'],
            'duration' => $duration,
            'dspoinc_reward' => (int)$row['dspoinc_reward'],
            'role_reward' => $row['role_reward'],
            'comment' => $row['comment'],
            'created_at' => $formattedDate,
            'started_at' => $row['started_at'],
            'ended_at' => $row['ended_at'],
            'participant_count' => (int)$row['participant_count'],
            'max_cheese_collected' => (int)$row['max_cheese'],
            'avg_cheese_collected' => round((float)$row['avg_cheese'], 2),
            'total_dspoinc_earned' => (int)$row['total_dspoinc_earned']
        ];
    }
    
    return $races;
}

/**
 * Get performance metrics
 */
function getPerformanceMetrics($pdo) {
    // Success rate (finished races / total races)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tbl_cheese_races");
    $totalRaces = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as finished FROM tbl_cheese_races WHERE status = 'finished'");
    $finishedRaces = $stmt->fetch()['finished'];
    
    $successRate = $totalRaces > 0 ? round(($finishedRaces / $totalRaces) * 100, 1) : 0;
    
    // Average participants per race
    $stmt = $pdo->query("
        SELECT AVG(participant_count) as avg_participants
        FROM (
            SELECT COUNT(*) as participant_count
            FROM tbl_race_participants
            GROUP BY race_id
        )
    ");
    $avgParticipants = $stmt->fetch()['avg_participants'];
    
    // Average cheese collected per race
    $stmt = $pdo->query("
        SELECT AVG(cheese_count) as avg_cheese
        FROM tbl_race_participants
        WHERE cheese_count > 0
    ");
    $avgCheese = $stmt->fetch()['avg_cheese'];
    
    return [
        'success_rate' => $successRate . '%',
        'avg_participants' => round((float)$avgParticipants, 1),
        'avg_cheese' => round((float)$avgCheese, 2),
        'peak_participants' => 'Coming Soon',
        'top_performance' => 'Coming Soon'
    ];
}

/**
 * Get recent race activity events
 */
function getRecentActivity($pdo) {
    // Get recent race events from tbl_discord_events
    $stmt = $pdo->query("
        SELECT 
            event_type,
            user_name as username,
            user_id,
            description,
            created_at,
            timestamp
        FROM tbl_discord_events 
        WHERE event_type IN ('player_joined', 'race_started', 'race_finished')
        ORDER BY created_at DESC, timestamp DESC
        LIMIT 50
    ");
    
    $activities = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $activities[] = [
            'event_type' => $row['event_type'],
            'username' => $row['username'] ?: 'Unknown Player',
            'user_id' => $row['user_id'],
            'description' => $row['description'] ?: 'No description',
            'created_at' => $row['created_at'] ?: $row['timestamp'],
            'race_id' => null // We'll need to extract this from description if needed
        ];
    }
    
    // If no events from tbl_discord_events, get recent race participants as activity
    if (empty($activities)) {
        $stmt = $pdo->query("
            SELECT 
                'player_joined' as event_type,
                rp.username,
                rp.user_id,
                'Player joined race' as description,
                rp.joined_at as created_at,
                rp.race_id
            FROM tbl_race_participants rp
            ORDER BY rp.joined_at DESC
            LIMIT 50
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'event_type' => $row['event_type'],
                'username' => $row['username'] ?: 'Unknown Player',
                'user_id' => $row['user_id'],
                'description' => $row['description'],
                'created_at' => $row['created_at'],
                'race_id' => $row['race_id']
            ];
        }
    }
    
    return $activities;
}
?>
