<?php
// Fix script to update existing race participant records
header('Content-Type: text/plain');

try {
    // Connect to database - use absolute path for Render server
    $db = new PDO('sqlite:/var/www/html/db/narrrf_world.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== FIXING RACE PARTICIPANTS DATABASE ===\n\n";
    
    // Check current state
    echo "1. Current state:\n";
    $total = $db->query("SELECT COUNT(*) as count FROM tbl_race_participants")->fetch(PDO::FETCH_ASSOC);
    echo "   Total participants: {$total['count']}\n";
    
    $statusCounts = $db->query("SELECT status, COUNT(*) as count FROM tbl_race_participants GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statusCounts as $status) {
        echo "   - {$status['status']}: {$status['count']}\n";
    }
    echo "\n";
    
    // Fix participants with 'racing' status (these should be marked as completed)
    echo "2. Fixing 'racing' status participants:\n";
    $racingParticipants = $db->query("
        SELECT race_id, user_id, position 
        FROM tbl_race_participants 
        WHERE status = 'racing'
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if ($racingParticipants) {
        echo "   Found " . count($racingParticipants) . " participants with 'racing' status\n";
        
        foreach ($racingParticipants as $participant) {
            try {
                // Determine position - if they have a decimal position, they were racing
                $position = $participant['position'];
                if (is_numeric($position) && $position > 0 && $position < 100) {
                    // They were racing, assign a reasonable position based on their progress
                    $finalPosition = min(10, max(1, round($position / 10))); // Convert percentage to position 1-10
                } else {
                    $finalPosition = 999; // DNF
                }
                
                $db->prepare("
                    UPDATE tbl_race_participants 
                    SET status = 'completed', finished_at = datetime('now'), updated_at = datetime('now')
                    WHERE race_id = ? AND user_id = ?
                ")->execute([$participant['race_id'], $participant['user_id']]);
                
                echo "   - Fixed participant {$participant['user_id']} in race {$participant['race_id']} (position: {$finalPosition})\n";
                
            } catch (Exception $e) {
                echo "   - ERROR fixing participant {$participant['user_id']}: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "   No 'racing' status participants found\n";
    }
    echo "\n";
    
    // Fix participants with 'joined' status (these should be marked as completed)
    echo "3. Fixing 'joined' status participants:\n";
    $joinedParticipants = $db->query("
        SELECT race_id, user_id, position 
        FROM tbl_race_participants 
        WHERE status = 'joined'
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if ($joinedParticipants) {
        echo "   Found " . count($joinedParticipants) . " participants with 'joined' status\n";
        
        foreach ($joinedParticipants as $participant) {
            try {
                $db->prepare("
                    UPDATE tbl_race_participants 
                    SET status = 'completed', finished_at = datetime('now'), updated_at = datetime('now')
                    WHERE race_id = ? AND user_id = ?
                ")->execute([$participant['race_id'], $participant['user_id']]);
                
                echo "   - Fixed participant {$participant['user_id']} in race {$participant['race_id']}\n";
                
            } catch (Exception $e) {
                echo "   - ERROR fixing participant {$participant['user_id']}: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "   No 'joined' status participants found\n";
    }
    echo "\n";
    
    // Check final state
    echo "4. Final state:\n";
    $finalTotal = $db->query("SELECT COUNT(*) as count FROM tbl_race_participants")->fetch(PDO::FETCH_ASSOC);
    echo "   Total participants: {$finalTotal['count']}\n";
    
    $finalStatusCounts = $db->query("SELECT status, COUNT(*) as count FROM tbl_race_participants GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($finalStatusCounts as $status) {
        echo "   - {$status['status']}: {$status['count']}\n";
    }
    echo "\n";
    
    // Check completed participants
    $completedCount = $db->query("SELECT COUNT(*) as count FROM tbl_race_participants WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC);
    echo "5. Completed participants: {$completedCount['count']}\n";
    
    // Check for specific user if provided
    if (isset($_GET['user_id'])) {
        $userId = $_GET['user_id'];
        echo "\n6. User {$userId} race participation:\n";
        $userRaces = $db->prepare("
            SELECT race_id, status, position, dspoinc_earned, created_at, finished_at
            FROM tbl_race_participants 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $userRaces->execute([$userId]);
        $races = $userRaces->fetchAll(PDO::FETCH_ASSOC);
        
        if ($races) {
            foreach ($races as $race) {
                echo "   - Race {$race['race_id']}: Status={$race['status']}, Position={$race['position']}, DSPOINC={$race['dspoinc_earned']}\n";
            }
        } else {
            echo "   No races found for user {$userId}\n";
        }
    }
    
    echo "\n=== FIX COMPLETE ===\n";
    echo "All race participant records have been updated to 'completed' status.\n";
    echo "Users should now see their race history in the mission status.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
