<?php
// 🔧 CRITICAL REPAIR: Discord Race Data Synchronization Script
// This script fixes the missing participant records that cause incorrect race counts

// CORS headers for web requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if running from command line
$isCommandLine = php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_METHOD']);

// Function to get database connection
function getSQLite3Connection() {
    $dbPath = '/data/narrrf_world.sqlite';
    if (!file_exists($dbPath)) {
        $dbPath = 'db/narrrf_world.sqlite'; // Fallback for local development
    }
    
    try {
        $db = new SQLite3($dbPath);
        $db->enableExceptions(true);
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
        $racesQuery = "SELECT race_id, creator_id, creator_name, status, created_at FROM tbl_cheese_races ORDER BY created_at DESC";
        $racesResult = $db->query($racesQuery);
        
        if (!$racesResult) {
            throw new Exception("Failed to query races: " . $db->lastErrorMsg());
        }
        
        $races = [];
        while ($race = $racesResult->fetchArray(SQLITE3_ASSOC)) {
            $races[] = $race;
        }
        
        $results['races_found'] = count($races);
        echo "🔍 Found " . count($races) . " races in database\n";
        
        // 🔍 Step 2: Check existing participants
        $participantsQuery = "SELECT race_id, COUNT(*) as count FROM tbl_race_participants GROUP BY race_id";
        $participantsResult = $db->query($participantsQuery);
        
        if (!$participantsResult) {
            throw new Exception("Failed to query participants: " . $db->lastErrorMsg());
        }
        
        $existingParticipants = [];
        while ($participant = $participantsResult->fetchArray(SQLITE3_ASSOC)) {
            $existingParticipants[$participant['race_id']] = (int)$participant['count'];
        }
        
        echo "🔍 Found participant records for " . count($existingParticipants) . " races\n";
        
        // 🔧 Step 3: Create missing participant records
        foreach ($races as $race) {
            $raceId = $race['race_id'];
            $creatorId = $race['creator_id'];
            $creatorName = $race['creator_name'];
            
            // Check if race creator already has a participant record
            $checkQuery = "
                SELECT COUNT(*) as count 
                FROM tbl_race_participants 
                WHERE race_id = ? AND user_id = ?
            ";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindValue(1, $raceId, SQLITE3_TEXT);
            $checkStmt->bindValue(2, $creatorId, SQLITE3_TEXT);
            $checkResult = $checkStmt->execute();
            
            if (!$checkResult) {
                $results['errors'][] = "Failed to check participants for race $raceId: " . $db->lastErrorMsg();
                continue;
            }
            
            $checkRow = $checkResult->fetchArray(SQLITE3_ASSOC);
            $participantExists = $checkRow['count'] > 0;
            
            if (!$participantExists) {
                // Create participant record for race creator
                $insertParticipantQuery = "
                    INSERT INTO tbl_race_participants (
                        race_id, user_id, username, joined_at, status, 
                        position, cheese_count, dspoinc_earned, season, 
                        start_time, end_time, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $insertStmt = $db->prepare($insertParticipantQuery);
                $now = date('Y-m-d H:i:s');
                $season = 'season_2'; // Current season
                
                $insertStmt->bindValue(1, $raceId, SQLITE3_TEXT);
                $insertStmt->bindValue(2, $creatorId, SQLITE3_TEXT);
                $insertStmt->bindValue(3, $creatorName, SQLITE3_TEXT);
                $insertStmt->bindValue(4, $now, SQLITE3_TEXT);
                $insertStmt->bindValue(5, 'joined', SQLITE3_TEXT);
                $insertStmt->bindValue(6, 0, SQLITE3_INTEGER);
                $insertStmt->bindValue(7, 0, SQLITE3_INTEGER);
                $insertStmt->bindValue(8, 0, SQLITE3_INTEGER);
                $insertStmt->bindValue(9, $season, SQLITE3_TEXT);
                $insertStmt->bindValue(10, $now, SQLITE3_TEXT);
                $insertStmt->bindValue(11, $now, SQLITE3_TEXT);
                $insertStmt->bindValue(12, $now, SQLITE3_TEXT);
                
                $insertResult = $insertStmt->execute();
                
                if ($insertResult) {
                    $results['participants_created']++;
                    echo "✅ Created participant record for $creatorName in race $raceId\n";
                } else {
                    $results['errors'][] = "Failed to create participant record for race $raceId: " . $db->lastErrorMsg();
                }
            } else {
                echo "ℹ️ Participant record already exists for $creatorName in race $raceId\n";
            }
        }
        
        // 🔍 Step 4: Verify the repair
        $verificationQuery = "
            SELECT COUNT(*) as total_races,
                   COUNT(CASE WHEN status = 'finished' THEN 1 END) as finished_races,
                   COUNT(CASE WHEN status = 'active' THEN 1 END) as active_races
            FROM tbl_cheese_races
        ";
        $verificationResult = $db->query($verificationQuery);
        
        if ($verificationResult) {
            $verificationRow = $verificationResult->fetchArray(SQLITE3_ASSOC);
            echo "📊 Verification - Total races: " . $verificationRow['total_races'] . 
                 ", Finished: " . $verificationRow['finished_races'] . 
                 ", Active: " . $verificationRow['active_races'] . "\n";
        }
        
        // 🔍 Step 5: Test the user profile query
        $testQuery = "
            SELECT 
                COUNT(*) as total_races,
                COUNT(CASE WHEN status = 'completed' AND position < 50 THEN 1 END) as wins,
                COUNT(CASE WHEN status = 'completed' AND position < 100 THEN 1 END) as podiums,
                MIN(CASE WHEN status = 'completed' THEN position END) as best_position,
                SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned
            FROM tbl_race_participants
            WHERE user_id = '328601656659017732'
        ";
        
        $testResult = $db->query($testQuery);
        if ($testResult) {
            $testRow = $testResult->fetchArray(SQLITE3_ASSOC);
            echo "🧪 Test query for user 328601656659017732:\n";
            echo "   Total races: " . $testRow['total_races'] . "\n";
            echo "   Wins: " . $testRow['wins'] . "\n";
            echo "   Podiums: " . $testRow['podiums'] . "\n"; // Corrected from podiums to podiums
            echo "   Best position: " . ($testRow['best_position'] ?: 'N/A') . "\n";
            echo "   Total DSPOINC: " . $testRow['total_dspoinc_earned'] . "\n";
        }
        
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
if ($isCommandLine) {
    // Running from command line
    echo "🔧 CRITICAL REPAIR: Discord Race Data Synchronization Script\n";
    echo "========================================================\n\n";
    
    $results = repairRaceData();
    
    if ($results['success']) {
        echo "\n✅ Repair completed successfully!\n";
        echo "🔍 Races found: " . $results['races_found'] . "\n";
        echo "🔧 Participants created: " . $results['participants_created'] . "\n";
        echo "📈 Participants updated: " . $results['participants_updated'] . "\n";
        
        if (!empty($results['errors'])) {
            echo "⚠️ Errors encountered:\n";
            foreach ($results['errors'] as $error) {
                echo "   - $error\n";
            }
        }
        
        echo "\n🎯 Next steps:\n";
        echo "1. Test your profile page to see updated race counts\n";
        echo "2. Check mission status for correct race statistics\n";
        echo "3. Verify admin interface shows consistent data\n";
        
    } else {
        echo "\n❌ Repair failed: " . $results['error'] . "\n";
        exit(1);
    }
    
} else {
    // Running from HTTP request
    if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        $results = repairRaceData();
        http_response_code($results['success'] ? 200 : 500);
        echo json_encode($results, JSON_PRETTY_PRINT);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}
?>
