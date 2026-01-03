<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ✅ PUBLIC ACCESS: Season statistics are public data - no authentication required
// Season statistics contain no sensitive information and should be accessible to all users
// This allows the Season Statistics & Legends section to work on live environment

try {
    // Determine database path based on environment
    $isProduction = $_SERVER['HTTP_HOST'] === 'narrrfs.world';
    $dbPath = $isProduction ? '/data/narrrf_world.sqlite' : __DIR__ . '/../../db/narrrf_world.sqlite';
    
    // Connect to database with PDO
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get query parameters
    $season = $_GET['season'] ?? 'current';
    $game_type = $_GET['game_type'] ?? 'all';

    // Get current season info - use the active season from tbl_seasons
    $stmt = $pdo->prepare("SELECT season_name FROM tbl_seasons WHERE is_active = 1 ORDER BY season_id DESC LIMIT 1");
    $stmt->execute();
    $current_season_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_season = $current_season_result['season_name'] ?? 'Season 7';

    // Determine which season to show
    if ($season === 'current') {
        $target_season = $current_season; // This will be "Season 5" (or current active season)
    } else if (strpos($season, 'season_') === 0) {
        // Convert "season_5" format to "Season 5" format
        $season_num = str_replace('season_', '', $season);
        $target_season = 'Season ' . $season_num;
    } else {
        $target_season = $season;
    }
    
    // Debug logging
    error_log("Season API Debug: season=$season, current_season=$current_season, target_season=$target_season");
    
    // Verify target season is set correctly for strict filtering
    if (empty($target_season)) {
        error_log("WARNING: target_season is empty, defaulting to current_season");
        $target_season = $current_season;
    }

    // Get all available seasons from tbl_seasons (not just seasons with scores)
    $stmt = $pdo->prepare("SELECT season_name FROM tbl_seasons ORDER BY season_id DESC");
    $stmt->execute();
    $available_seasons = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get season statistics
    $season_stats = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_scores,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(DISTINCT discord_id) as unique_players,
                COUNT(CASE WHEN is_top_performer = 1 THEN 1 END) as top_performers
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND season = ?
        ");
        $stmt->execute([$target_season]);
        $season_stats['tetris'] = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Tetris stats for season '$target_season': " . json_encode($season_stats['tetris']));
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_scores,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(DISTINCT discord_id) as unique_players,
                COUNT(CASE WHEN is_top_performer = 1 THEN 1 END) as top_performers
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND season = ?
        ");
        $stmt->execute([$target_season]);
        $season_stats['snake'] = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Snake stats for season '$target_season': " . json_encode($season_stats['snake']));
    }
    
    if ($game_type === 'all' || $game_type === 'space_invaders') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_scores,
                MAX(score) as max_score,
                AVG(score) as avg_score,
                COUNT(DISTINCT discord_id) as unique_players,
                COUNT(CASE WHEN is_top_performer = 1 THEN 1 END) as top_performers
            FROM tbl_tetris_scores 
            WHERE game = 'space_invaders' AND season = ?
        ");
        $stmt->execute([$target_season]);
        $season_stats['space_invaders'] = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Space Invaders stats for season '$target_season': " . json_encode($season_stats['space_invaders']));
    }

    // Add Cheese Hunt statistics
    if ($game_type === 'all' || $game_type === 'cheese_hunt') {
        // Cheese Hunt shows ALL-TIME data (preserved across seasons)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT user_wallet) as unique_players
            FROM tbl_cheese_clicks
        ");
        $stmt->execute();
        $cheese_basic = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get max clicks by a single user (ALL-TIME data)
        $stmt = $pdo->prepare("
            SELECT 
                user_wallet,
                COUNT(*) as user_clicks
            FROM tbl_cheese_clicks 
            GROUP BY user_wallet
            ORDER BY user_clicks DESC
            LIMIT 1
        ");
        $stmt->execute();
        $max_clicks_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $max_clicks = $max_clicks_result ? $max_clicks_result['user_clicks'] : 0;
        
        // Calculate average clicks per user
        $avg_clicks = $cheese_basic['unique_players'] > 0 ? 
            round($cheese_basic['total_clicks'] / $cheese_basic['unique_players'], 2) : 0;
        
        // Convert to match the expected structure
        $season_stats['cheese_hunt'] = [
            'total_clicks' => $cheese_basic['total_clicks'] ?? 0,
            'unique_players' => $cheese_basic['unique_players'] ?? 0,
            'max_clicks' => $max_clicks,
            'avg_clicks' => $avg_clicks,
            'top_performers' => $cheese_basic['unique_players'] ?? 0
        ];
    }

    // Add Discord Race statistics
    if ($game_type === 'all' || $game_type === 'discord_race') {
        // Discord Race shows ALL-TIME data (preserved across seasons)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT r.race_id) as total_races,
                COUNT(DISTINCT rp.user_id) as total_participants,
                COUNT(CASE WHEN rp.position = 1 THEN 1 END) as total_prizes_awarded,
                COUNT(CASE WHEN rp.joined_at >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN rp.joined_at >= datetime('now', '-7 days') THEN 1 END) as recent_7d,
                COUNT(DISTINCT rp.user_id) as top_performers
            FROM tbl_cheese_races r
            LEFT JOIN tbl_race_participants rp ON r.race_id = rp.race_id
        ");
        $stmt->execute();
        $race_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Convert to match the expected structure
        $season_stats['discord_race'] = [
            'total_races' => $race_data['total_races'] ?? 0,
            'total_participants' => $race_data['total_participants'] ?? 0,
            'total_prizes_awarded' => $race_data['total_prizes_awarded'] ?? 0,
            'recent_24h' => $race_data['recent_24h'] ?? 0,
            'recent_7d' => $race_data['recent_7d'] ?? 0,
            'top_performers' => $race_data['top_performers'] ?? 0
        ];
    }

    // Add Cheese Rumble statistics
    if ($game_type === 'all' || $game_type === 'cheese_rumble') {
        // Cheese Rumble shows ALL-TIME data (preserved across seasons, similar to Discord Race)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT cr.rumble_id) as total_rumbles,
                COUNT(DISTINCT rp.user_id) as total_participants,
                COUNT(CASE WHEN rp.status = 'winner' OR rp.final_position = 1 THEN 1 END) as total_wins,
                COUNT(CASE WHEN rp.final_position <= 3 AND rp.final_position IS NOT NULL THEN 1 END) as podiums,
                COUNT(CASE WHEN cr.created_at >= datetime('now', '-24 hours') THEN 1 END) as recent_24h,
                COUNT(CASE WHEN cr.created_at >= datetime('now', '-7 days') THEN 1 END) as recent_7d
            FROM tbl_cheese_rumbles cr
            LEFT JOIN tbl_rumble_participants rp ON cr.rumble_id = rp.rumble_id
        ");
        $stmt->execute();
        $rumble_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Convert to match the expected structure
        $season_stats['cheese_rumble'] = [
            'total_rumbles' => $rumble_data['total_rumbles'] ?? 0,
            'total_participants' => $rumble_data['total_participants'] ?? 0,
            'total_wins' => $rumble_data['total_wins'] ?? 0,
            'podiums' => $rumble_data['podiums'] ?? 0,
            'recent_24h' => $rumble_data['recent_24h'] ?? 0,
            'recent_7d' => $rumble_data['recent_7d'] ?? 0,
            'top_performers' => $rumble_data['total_participants'] ?? 0
        ];
    }

    // Get top performers for the season
    $top_performers = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                MAX(score) as score,
                MIN(timestamp) as timestamp,
                MAX(is_top_performer) as is_top_performer
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND season = ? 
            GROUP BY discord_id, discord_name
            ORDER BY score DESC 
            LIMIT 10
        ");
        $stmt->execute([$target_season]);
        $top_performers['tetris'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                MAX(score) as score,
                MIN(timestamp) as timestamp,
                MAX(is_top_performer) as is_top_performer
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND season = ? 
            GROUP BY discord_id, discord_name
            ORDER BY score DESC 
            LIMIT 10
        ");
        $stmt->execute([$target_season]);
        $top_performers['snake'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert Snake scores to DSPOINC (multiply by 10)
        foreach ($top_performers['snake'] as &$entry) {
            $entry['score'] = $entry['score'] * 10;
        }
    }
    
    if ($game_type === 'all' || $game_type === 'space_invaders') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                MAX(score) as score,
                MIN(timestamp) as timestamp,
                MAX(is_top_performer) as is_top_performer
            FROM tbl_tetris_scores 
            WHERE game = 'space_invaders' AND season = ? 
            GROUP BY discord_id, discord_name
            ORDER BY score DESC 
            LIMIT 10
        ");
        $stmt->execute([$target_season]);
        $top_performers['space_invaders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert Space Invaders scores to DSPOINC (divide by 100)
        foreach ($top_performers['space_invaders'] as &$entry) {
            $entry['score'] = round($entry['score'] / 100);
        }
    }

    // Get all-time top performers (marked as legends)
    $all_time_legends = [];
    
    if ($game_type === 'all' || $game_type === 'tetris') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                score,
                season,
                timestamp
            FROM tbl_tetris_scores 
            WHERE game = 'tetris' AND is_top_performer = 1 
            ORDER BY score DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $all_time_legends['tetris'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($game_type === 'all' || $game_type === 'snake') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                score,
                season,
                timestamp
            FROM tbl_tetris_scores 
            WHERE game = 'snake' AND is_top_performer = 1 
            ORDER BY score DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $all_time_legends['snake'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert Snake legend scores to DSPOINC (multiply by 10)
        foreach ($all_time_legends['snake'] as &$entry) {
            $entry['score'] = $entry['score'] * 10;
        }
    }
    
    if ($game_type === 'all' || $game_type === 'space_invaders') {
        $stmt = $pdo->prepare("
            SELECT 
                discord_id,
                discord_name,
                score,
                season,
                timestamp
            FROM tbl_tetris_scores 
            WHERE game = 'space_invaders' AND is_top_performer = 1 
            ORDER BY score DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $all_time_legends['space_invaders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert Space Invaders legend scores to DSPOINC (divide by 100)
        foreach ($all_time_legends['space_invaders'] as &$entry) {
            $entry['score'] = round($entry['score'] / 100);
        }
    }

    // Get season timeline
    $season_timeline = [];
    foreach ($available_seasons as $season_name) {
        $stmt = $pdo->prepare("
            SELECT 
                MIN(timestamp) as start_date,
                MAX(season_end_date) as end_date,
                COUNT(*) as total_scores,
                COUNT(DISTINCT discord_id) as unique_players
            FROM tbl_tetris_scores 
            WHERE season = ?
        ");
        $stmt->execute([$season_name]);
        $timeline_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $season_timeline[] = [
            'season' => $season_name,
            'start_date' => $timeline_data['start_date'],
            'end_date' => $timeline_data['end_date'],
            'total_scores' => $timeline_data['total_scores'],
            'unique_players' => $timeline_data['unique_players']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'current_season' => "season_$current_season",
            'target_season' => $target_season,
            'available_seasons' => $available_seasons,
            'season_stats' => $season_stats,
            'top_performers' => $top_performers,
            'all_time_legends' => $all_time_legends,
            'season_timeline' => $season_timeline
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to get season statistics: ' . $e->getMessage()
    ]);
}
?> 