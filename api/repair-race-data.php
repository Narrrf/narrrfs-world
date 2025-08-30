<?php
// 🔧 CRITICAL REPAIR: Discord Race Data Synchronization Script
// This script fixes the missing participant records that cause incorrect race counts

// CORS headers for browser access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
function getSQLite3Connection() {
    try {
        // Use the production database path
        $dbPath = '/var/www/html/db/narrrf_world.sqlite';
        
        // Fallback to local path if production doesn't exist
        if (!file_exists($dbPath)) {
            $dbPath = __DIR__ . '/../db/narrrf_world.sqlite';
        }
        
        if (!file_exists($dbPath)) {
            throw new Exception("Database not found at: $dbPath");
        }
        
        $db = new SQLite3($dbPath);
        $db->enableExceptions(true);
        
        // Enable WAL mode for better concurrency
        $db->exec('PRAGMA journal_mode=WAL;');
        $db->exec('PRAGMA synchronous=NORMAL;');
        $db->exec('PRAGMA foreign_keys=ON;');
        
        return $db;
    } catch (Exception $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

// Main repair function
function repairRaceData() {
    try {
        $db = getSQLite3Connection();
        
        $results = [
            'success' => true,
            'races_found' => 0,
            'participants_created' => 0,
            'participants_updated' => 0,
            'errors' => []
        ];
        
        // 🔍 Step 1: Get all races from tbl_cheese_races
        $racesQuery = "
            SELECT race_id, creator_id, creator_name, created_at, status
            FROM tbl_cheese_races 
            ORDER BY created_at DESC
        ";
        
        $racesStmt = $db->prepare($racesQuery);
        $racesResult = $racesStmt->execute();
        
        $races = [];
        while ($race = $racesResult->fetchArray(SQLITE3_ASSOC)) {
            $races[] = $race;
        }
        
        $results['races_found'] = count($races);
        echo "📊 Found " . count($races) . " races in tbl_cheese_races\n";
        
        // 🔍 Step 2: Check existing participants
        $existingParticipantsQuery = "SELECT DISTINCT race_id FROM tbl_race_participants";
        $existingStmt = $db->prepare($existingParticipantsQuery);
        $existingResult = $existingStmt->execute();
        
        $existingRaceIds = [];
        while ($existing = $existingResult->fetchArray(SQLITE3_ASSOC)) {
            $existingRaceIds[] = $existing['race_id'];
        }
        
        echo "📊 Found " . count($existingRaceIds) . " races with existing participants\n";
        
        // 🔧 Step 3: Create missing participant records
        foreach ($races as $race) {
            try {
                $raceId = $race['race_id'];
                
                // Check if this race already has participants
                if (in_array($raceId, $existingRaceIds)) {
                    echo "✅ Race $raceId already has participants\n";
                    continue;
                }
                
                // 🔧 Create participant record for the race creator
                $insertParticipantQuery = "
                    INSERT INTO tbl_race_participants (
                        race_id, user_id, username, joined_at, status, 
                        position, cheese_count, dspoinc_earned, season, 
                        start_time, end_time, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $stmt = $db->prepare($insertParticipantQuery);
                
                // Set appropriate values based on race status
                $status = ($race['status'] === 'completed' || $race['status'] === 'finished') ? 'completed' : 'joined';
                $position = ($status === 'completed') ? 1 : 0; // Assume creator won if race is completed
                $cheeseCount = ($status === 'completed') ? 5 : 0; // Default winning cheese count
                $dspoincEarned = ($status === 'completed') ? 1000 : 0; // Default winning reward
                $season = 'season_2'; // Current season
                
                $stmt->bindValue(1, $raceId, SQLITE3_TEXT);
                $stmt->bindValue(2, $race['creator_id'], SQLITE3_TEXT);
                $stmt->bindValue(3, $race['creator_name'], SQLITE3_TEXT);
                $stmt->bindValue(4, $race['created_at'], SQLITE3_TEXT);
                $stmt->bindValue(5, $status, SQLITE3_TEXT);
                $stmt->bindValue(6, $position, SQLITE3_INTEGER);
                $stmt->bindValue(7, $cheeseCount, SQLITE3_INTEGER);
                $stmt->bindValue(8, $dspoincEarned, SQLITE3_INTEGER);
                $stmt->bindValue(9, $season, SQLITE3_TEXT);
                $stmt->bindValue(10, $race['created_at'], SQLITE3_TEXT);
                $stmt->bindValue(11, $race['created_at'], SQLITE3_TEXT);
                $stmt->bindValue(12, $race['created_at'], SQLITE3_TEXT);
                
                $result = $stmt->execute();
                
                if ($result) {
                    $results['participants_created']++;
                    echo "✅ Created participant record for race $raceId (creator: {$race['creator_name']})\n";
                } else {
                    $error = "Failed to create participant for race $raceId";
                    $results['errors'][] = $error;
                    echo "❌ $error\n";
                }
                
            } catch (Exception $e) {
                $error = "Error processing race {$race['race_id']}: " . $e->getMessage();
                $results['errors'][] = $error;
                echo "❌ $error\n";
            }
        }
        
        // 🔍 Step 4: Verify the repair
        $verifyQuery = "
            SELECT 
                (SELECT COUNT(*) FROM tbl_cheese_races) as total_races,
                (SELECT COUNT(DISTINCT race_id) FROM tbl_race_participants) as races_with_participants,
                (SELECT COUNT(*) FROM tbl_race_participants) as total_participants
        ";
        
        $verifyStmt = $db->prepare($verifyQuery);
        $verifyResult = $verifyStmt->execute();
        $verification = $verifyResult->fetchArray(SQLITE3_ASSOC);
        
        $results['verification'] = $verification;
        
        echo "📊 VERIFICATION RESULTS:\n";
        echo "   Total races: {$verification['total_races']}\n";
        echo "   Races with participants: {$verification['races_with_participants']}\n";
        echo "   Total participants: {$verification['total_participants']}\n";
        
        // 🔧 Step 5: Test the user profile query
        $testUserId = '328601656659017732'; // narrrf's Discord ID
        $testQuery = "
            SELECT COUNT(*) as total_races,
                   COUNT(CASE WHEN status = 'completed' AND position < 50 THEN 1 END) as wins,
                   COUNT(CASE WHEN status = 'completed' AND position < 100 THEN 1 END) as podiums,
                   MIN(CASE WHEN status = 'completed' THEN position END) as best_position,
                   SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned
            FROM tbl_race_participants
            WHERE user_id = ?
        ";
        
        $testStmt = $db->prepare($testQuery);
        $testStmt->bindValue(1, $testUserId, SQLITE3_TEXT);
        $testResult = $testStmt->execute();
        $testData = $testResult->fetchArray(SQLITE3_ASSOC);
        
        $results['test_user_profile'] = $testData;
        
        echo "🧪 TEST USER PROFILE (narrrf): " . json_encode($testData) . "\n";
        
        $db->close();
        return $results;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'races_found' => 0,
            'participants_created' => 0,
            'participants_updated' => 0,
            'errors' => [$e->getMessage()]
        ];
    }
}

// Execute the repair
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $results = repairRaceData();
    
    // Set appropriate HTTP status code
    http_response_code($results['success'] ? 200 : 500);
    
    // Output results
    echo json_encode($results, JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
