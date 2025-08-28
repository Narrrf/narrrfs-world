<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

try {
    // Test database connection
    if (file_exists('/var/www/html/db/narrrf_world.sqlite')) {
        // Production environment (Render)
        $db = new PDO('sqlite:/var/www/html/db/narrrf_world.sqlite');
    } else {
        // Local environment (XAMPP)
        $db = new PDO('sqlite:' . __DIR__ . '/../db/narrrf_world.sqlite');
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test basic queries
    $testQueries = [
        'tetris_scores' => 'SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = "tetris"',
        'snake_scores' => 'SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = "snake"',
        'space_invaders_scores' => 'SELECT COUNT(*) as count FROM tbl_tetris_scores WHERE game = "space_invaders"',
        'cheese_clicks' => 'SELECT COUNT(*) as count FROM tbl_cheese_clicks',
        'race_participants' => 'SELECT COUNT(*) as count FROM tbl_race_participants',
        'users' => 'SELECT COUNT(*) as count FROM tbl_users'
    ];
    
    $results = [];
    foreach ($testQueries as $name => $query) {
        try {
            $stmt = $db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $results[$name] = (int)$result['count'];
        } catch (Exception $e) {
            $results[$name] = 'error: ' . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Missions API test successful',
        'database_status' => 'connected',
        'table_counts' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Test failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>

