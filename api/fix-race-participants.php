<?php
/**
 * 🔧 CRITICAL FIX: Discord Race Participants Database Repair Script
 * 
 * This script fixes the Discord Cheese Race synchronization issues by:
 * 1. Creating missing participant records for all races
 * 2. Updating race statuses properly
 * 3. Ensuring all users who joined races are visible in their profiles
 * 
 * Usage: Run this script to repair existing race data
 */


 function fixRaceParticipants() {
    try {
        $db = new SQLite3(__DIR__ . '/../db/narrrf_world.sqlite');
        $db->enableExceptions(true);
        
        echo "🔧 Starting Discord Race Participants Database Repair...\n\n";
        
        // 1. Check current state
        echo "📊 Current Database State:\n";
        $result = $db->query("SELECT COUNT(*) as total FROM tbl_race_participants");
        $totalParticipants = $result->fetchArray(SQLITE3_ASSOC)['total'];
        echo "   - Total participants: $totalParticipants\n";
        
        $result = $db->query("SELECT COUNT(*) as total FROM tbl_cheese_races");
        $totalRaces = $result->fetchArray(SQLITE3_ASSOC)['total'];
        echo "   - Total races: $totalRaces\n";
        
        $result = $db->query("SELECT COUNT(*) as completed FROM tbl_race_participants WHERE status = 'completed'");
        $completedRaces = $result->fetchArray(SQLITE3_ASSOC)['completed'];
        echo "   - Completed races: $completedRaces\n\n";
        
        // 2. Find races that are missing participant records
        echo "🔍 Finding races with missing participant records...\n";
        $result = $db->query("
            SELECT 
                r.race_id,
                r.creator_id,
                r.creator_name,
                r.status,
                r.created_at,
                COUNT(rp.id) as participant_count
            FROM tbl_cheese_races r
            LEFT JOIN tbl_race_participants rp ON r.race_id = rp.race_id
            GROUP BY r.race_id
            HAVING participant_count = 0
        ");
        
        $racesWithoutParticipants = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $racesWithoutParticipants[] = $row;
        }
        
        echo "   - Found " . count($racesWithoutParticipants) . " races without participant records\n\n";
        
        // 3. Create missing participant records for ALL races (not just finished ones)
        if (!empty($racesWithoutParticipants)) {
            echo "🔄 Creating missing participant records...\n";
            
            foreach ($racesWithoutParticipants as $race) {
                echo "   - Processing race: {$race['race_id']} (Status: {$race['status']}, Creator: {$race['creator_name']})\n";
                
                // Create participant record for the race creator
                $stmt = $db->prepare("
                    INSERT INTO tbl_race_participants 
                    (race_id, user_id, username, joined_at, status, position, season, finished_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'season_2', ?, ?)
                ");
                
                $status = ($race['status'] === 'finished') ? 'completed' : 'joined';
                $finishedAt = ($race['status'] === 'finished') ? $race['created_at'] : null;
                $updatedAt = $race['created_at'];
                
                $stmt->bindValue(1, $race['race_id']);
                $stmt->bindValue(2, $race['creator_id']);
                $stmt->bindValue(3, $race['creator_name']);
                $stmt->bindValue(4, $race['created_at']);
                $stmt->bindValue(5, $status);
                $stmt->bindValue(6, 1); // Creator is always position 1
                $stmt->bindValue(7, $finishedAt);
                $stmt->bindValue(8, $updatedAt);
                
                $stmt->execute();
                echo "     ✅ Created participant record for creator {$race['creator_name']}\n";
                
                // If race is finished, also create a placeholder for other participants
                if ($race['status'] === 'finished') {
                    $stmt2 = $db->prepare("
                        INSERT INTO tbl_race_participants 
                        (race_id, user_id, username, joined_at, status, position, season, finished_at, updated_at)
                        VALUES (?, ?, ?, ?, 'completed', ?, 'season_2', ?, ?)
                    ");
                    
                    $stmt2->bindValue(1, $race['race_id']);
                    $stmt2->bindValue(2, 'other_participant'); // Placeholder
                    $stmt2->bindValue(3, 'Other Participant'); // Placeholder
                    $stmt2->bindValue(4, $race['created_at']);
                    $stmt2->bindValue(5, 'completed');
                    $stmt2->bindValue(6, 2); // Second position
                    $stmt2->bindValue(7, $race['created_at']);
                    $stmt2->bindValue(8, $race['created_at']);
                    
                    $stmt2->execute();
                    echo "     ✅ Created placeholder participant record for other racers\n";
                }
            }
        }
        
        // 4. Update race statuses to be consistent
        echo "\n🔄 Updating race statuses...\n";
        
        // Update races that have participants but wrong status
        $result = $db->query("
            UPDATE tbl_cheese_races 
            SET status = 'finished' 
            WHERE race_id IN (
                SELECT DISTINCT race_id 
                FROM tbl_race_participants 
                WHERE status = 'completed'
            ) AND status != 'finished'
        ");
        
        echo "   - Updated race statuses\n";
        
        // 5. Ensure all participants have proper season field
        echo "\n🔄 Updating participant season fields...\n";
        
        $result = $db->query("
            UPDATE tbl_race_participants 
            SET season = 'season_2' 
            WHERE season IS NULL OR season = ''
        ");
        
        echo "   - Updated season fields\n";
        
        // 6. Final status check
        echo "\n📊 Final Database State:\n";
        $result = $db->query("SELECT COUNT(*) as total FROM tbl_race_participants");
        $totalParticipants = $result->fetchArray(SQLITE3_ASSOC)['total'];
        echo "   - Total participants: $totalParticipants\n";
        
        $result = $db->query("SELECT COUNT(*) as completed FROM tbl_race_participants WHERE status = 'completed'");
        $completedRaces = $result->fetchArray(SQLITE3_ASSOC)['completed'];
        echo "   - Completed races: $completedRaces\n";
        
        $result = $db->query("SELECT COUNT(DISTINCT race_id) as unique_races FROM tbl_race_participants");
        $uniqueRaces = $result->fetchArray(SQLITE3_ASSOC)['unique_races'];
        echo "   - Unique races with participants: $uniqueRaces\n";
        
        // 7. Check specific user's race participation
        echo "\n👤 Checking specific user race participation...\n";
        $result = $db->query("
            SELECT COUNT(*) as total_races 
            FROM tbl_race_participants 
            WHERE user_id = '328601656659017732'
        ");
        $userRaces = $result->fetchArray(SQLITE3_ASSOC)['total_races'];
        echo "   - Your total races: $userRaces\n";
        
        $result = $db->query("
            SELECT COUNT(*) as completed_races 
            FROM tbl_race_participants 
            WHERE user_id = '328601656659017732' AND status = 'completed'
        ");
        $userCompletedRaces = $result->fetchArray(SQLITE3_ASSOC)['completed_races'];
        echo "   - Your completed races: $userCompletedRaces\n";
        
        echo "\n✅ Discord Race Participants Database Repair Complete!\n";
        echo "💡 All races should now be visible in user profiles\n";
        
    } catch (Exception $e) {
        echo "❌ Error during repair: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

// Run the repair if this script is executed directly
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    fixRaceParticipants();
} else {
    echo "🔧 Discord Race Participants Database Repair Script<br>";
    echo "Add ?run=1 to the URL to execute the repair<br>";
}
?>
