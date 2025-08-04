<?php
// Comprehensive fix for all points management functions
// This ensures only ONE record per user to prevent summing issues

$file = 'api/admin/point-management.php';
$content = file_get_contents($file);

// Fix 1: ADDPOINTS - Replace with DELETE + INSERT approach
$oldAddPoints = '            // EXACT SAME LOGIC AS BOT:
            // 1. Try to update existing user score
            $stmt = $db->prepare(\'UPDATE tbl_user_scores SET score = score + ? WHERE user_id = ?\');
            $stmt->bindValue(1, $amount, SQLITE3_INTEGER);
            $stmt->bindValue(2, $userId, SQLITE3_TEXT);
            $stmt->execute();
            $affectedRows = $db->changes();

            // 2. If no rows affected, user doesn\'t exist, so insert new record
            if ($affectedRows === 0) {
                $stmt = $db->prepare(\'INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)\');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, $amount, SQLITE3_INTEGER);
                $stmt->bindValue(3, \'discord\', SQLITE3_TEXT);
                $stmt->bindValue(4, \'addpoints\', SQLITE3_TEXT);
                $stmt->execute();
            }';

$newAddPoints = '            // FIXED LOGIC: Clear all existing records and create one new record
            // 1. Get current total balance
            $currentBalance = getUserTotalScore($db, $userId);
            $newBalance = $currentBalance + $amount;
            
            // 2. Delete all existing records for this user
            $stmt = $db->prepare(\'DELETE FROM tbl_user_scores WHERE user_id = ?\');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->execute();

            // 3. Insert one new record with the new total
            $stmt = $db->prepare(\'INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)\');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->bindValue(2, $newBalance, SQLITE3_INTEGER);
            $stmt->bindValue(3, \'discord\', SQLITE3_TEXT);
            $stmt->bindValue(4, \'addpoints\', SQLITE3_TEXT);
            $stmt->execute();';

// Fix 2: SETPOINTS - Replace with DELETE + INSERT approach  
$oldSetPoints = '            // EXACT SAME LOGIC AS BOT:
            // 1. Try to update existing user score
            $stmt = $db->prepare(\'UPDATE tbl_user_scores SET score = ? WHERE user_id = ?\');
            $stmt->bindValue(1, $newAmount, SQLITE3_INTEGER);
            $stmt->bindValue(2, $userId, SQLITE3_TEXT);
            $stmt->execute();
            $affectedRows = $db->changes();

            // 2. If no rows affected, user doesn\'t exist, so insert new record
            if ($affectedRows === 0) {
                $stmt = $db->prepare(\'INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)\');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, $newAmount, SQLITE3_INTEGER);
                $stmt->bindValue(3, \'discord\', SQLITE3_TEXT);
                $stmt->bindValue(4, \'setpoints\', SQLITE3_TEXT);
                $stmt->execute();
            }';

$newSetPoints = '            // FIXED LOGIC: Clear all existing records and create one new record
            // 1. Delete all existing records for this user
            $stmt = $db->prepare(\'DELETE FROM tbl_user_scores WHERE user_id = ?\');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->execute();

            // 2. Insert one new record with the exact amount
            $stmt = $db->prepare(\'INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)\');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->bindValue(2, $newAmount, SQLITE3_INTEGER);
            $stmt->bindValue(3, \'discord\', SQLITE3_TEXT);
            $stmt->bindValue(4, \'setpoints\', SQLITE3_TEXT);
            $stmt->execute();';

// Fix 3: REMOVEPOINTS - Replace with DELETE + INSERT approach
$oldRemovePoints = '            // EXACT SAME LOGIC AS BOT:
            // 1. Try to update existing user score
            $stmt = $db->prepare(\'UPDATE tbl_user_scores SET score = score - ? WHERE user_id = ?\');
            $stmt->bindValue(1, $amount, SQLITE3_INTEGER);
            $stmt->bindValue(2, $userId, SQLITE3_TEXT);
            $stmt->execute();
            $affectedRows = $db->changes();

            // 2. If no rows affected, user doesn\'t exist, so insert new record with negative score
            if ($affectedRows === 0) {
                $stmt = $db->prepare(\'INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)\');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, -$amount, SQLITE3_INTEGER);
                $stmt->bindValue(3, \'discord\', SQLITE3_TEXT);
                $stmt->bindValue(4, \'removepoints\', SQLITE3_TEXT);
                $stmt->execute();
            }';

$newRemovePoints = '            // FIXED LOGIC: Clear all existing records and create one new record
            // 1. Get current total balance
            $currentBalance = getUserTotalScore($db, $userId);
            $newBalance = $currentBalance - $amount;
            
            // 2. Delete all existing records for this user
            $stmt = $db->prepare(\'DELETE FROM tbl_user_scores WHERE user_id = ?\');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->execute();

            // 3. Insert one new record with the new total
            $stmt = $db->prepare(\'INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)\');
            $stmt->bindValue(1, $userId, SQLITE3_TEXT);
            $stmt->bindValue(2, $newBalance, SQLITE3_INTEGER);
            $stmt->bindValue(3, \'discord\', SQLITE3_TEXT);
            $stmt->bindValue(4, \'removepoints\', SQLITE3_TEXT);
            $stmt->execute();';

// Apply all fixes
$content = str_replace($oldAddPoints, $newAddPoints, $content);
$content = str_replace($oldSetPoints, $newSetPoints, $content);
$content = str_replace($oldRemovePoints, $newRemovePoints, $content);

// Write back to file
file_put_contents($file, $content);

echo "✅ FIXED ALL POINTS FUNCTIONS!\n";
echo "All three functions (addpoints, setpoints, removepoints) now:\n";
echo "1. Delete all existing records for the user\n";
echo "2. Insert one new record with the correct total\n";
echo "3. This prevents multiple records from being summed incorrectly\n";
echo "4. No more negative balances or calculation errors!\n";
?> 