<?php
// Script to fix the setpoints function in point-management.php

$file = '../api/admin/point-management.php';
$content = file_get_contents($file);

// The problematic setpoints logic to replace
$oldLogic = '            // Get current amount
            $oldAmount = getUserTotalScore($db, $userId);
            $delta = $newAmount - $oldAmount;

            // EXACT SAME LOGIC AS BOT:
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
            } else {
                // 3. If user exists, also insert a new record for tracking (same as bot logic)
                $stmt = $db->prepare(\'INSERT INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)\');
                $stmt->bindValue(1, $userId, SQLITE3_TEXT);
                $stmt->bindValue(2, $delta, SQLITE3_INTEGER); // Use delta for proper tracking
                $stmt->bindValue(3, \'discord\', SQLITE3_TEXT);
                $stmt->bindValue(4, \'setpoints\', SQLITE3_TEXT);
                $stmt->execute();
            }';

// The fixed logic
$newLogic = '            // Get current amount
            $oldAmount = getUserTotalScore($db, $userId);

            // FIXED LOGIC: Clear all existing records and create one new record
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
            $stmt->execute();

            // 3. Calculate delta for logging
            $delta = $newAmount - $oldAmount;';

// Replace the logic
$newContent = str_replace($oldLogic, $newLogic, $content);

// Write back to file
file_put_contents($file, $newContent);

echo "✅ Fixed setpoints function in point-management.php\n";
echo "The setpoints function now:\n";
echo "1. Deletes all existing records for the user\n";
echo "2. Inserts one new record with the exact amount\n";
echo "3. This prevents multiple records from being summed incorrectly\n";
?> 