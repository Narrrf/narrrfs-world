<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Disable error display in production - only log errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Log the incoming request for debugging
error_log("Cheese click request received: " . json_encode($input));

if (!isset($input['user_wallet']) || !isset($input['egg_id'])) {
    http_response_code(400);
    echo json_encode(['error' => '❌ Missing required fields', 'received' => $input]);
    exit;
}

$userWallet = trim($input['user_wallet']);
$eggId = trim($input['egg_id']);
$timestamp = isset($input['timestamp']) ? $input['timestamp'] : time();
$quest_id = isset($input['quest_id']) ? intval($input['quest_id']) : null;
$screenshot_data = isset($input['screenshot']) ? $input['screenshot'] : null;

// Use centralized database configuration
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDatabaseConnection();

    // Insert cheese click record with proper column mapping
    $stmt = $pdo->prepare("INSERT INTO tbl_cheese_clicks (user_wallet, egg_id, timestamp, quest_id)
                           VALUES (?, ?, datetime(?, 'unixepoch'), ?)");
    $result = $stmt->execute([$userWallet, $eggId, $timestamp, $quest_id]);
    
    if (!$result) {
        throw new Exception("Failed to insert cheese click record");
    }
    
    $insertId = $pdo->lastInsertId();
    error_log("Cheese click inserted successfully with ID: $insertId");

    $response = [
        'success' => true,
        'message' => "🧀 Cheese click logged: $eggId by $userWallet",
        'timestamp' => $timestamp,
        'insert_id' => $insertId
    ];

    // If this is a quest completion, handle additional logic
    if ($quest_id) {
        // Get quest details
        $stmt = $pdo->prepare("SELECT * FROM tbl_quests WHERE quest_id = ? AND is_active = 1");
        $stmt->execute([$quest_id]);
        $quest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($quest && $quest['type'] === 'cheese_hunt') {
            $cheese_config = json_decode($quest['cheese_config'], true);
            
            // Check if user has already claimed this quest
            $stmt = $pdo->prepare("SELECT * FROM tbl_quest_claims WHERE quest_id = ? AND user_id = ?");
            $stmt->execute([$quest_id, $userWallet]);
            $existing_claim = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_claim) {
                // Quest already completed by this user
                $response['quest_completed'] = false;
                $response['message'] = "You've already completed this cheese hunt quest!";
            } else {
                // Count how many different cheese eggs this user has clicked for this quest
                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT egg_id) as unique_eggs 
                                       FROM tbl_cheese_clicks 
                                       WHERE quest_id = ? AND user_wallet = ?");
                $stmt->execute([$quest_id, $userWallet]);
                $egg_count = $stmt->fetch(PDO::FETCH_ASSOC)['unique_eggs'];
                
                $required_eggs = $cheese_config['cheese_count'] ?? 3;
                
                if ($egg_count >= $required_eggs) {
                    // All cheese eggs clicked! Complete the quest
                    $stmt = $pdo->prepare("INSERT INTO tbl_quest_claims (quest_id, user_id, proof, claimed_at, status)
                                           VALUES (?, ?, ?, datetime('now'), 'pending')");
                    $stmt->execute([$quest_id, $userWallet, "Cheese hunt completed: All $required_eggs eggs found"]);

                    // Save screenshot if provided
                    if ($screenshot_data && $cheese_config['screenshot_required']) {
                        $screenshot_path = saveScreenshot($screenshot_data, $userWallet, $quest_id);
                        if ($screenshot_path) {
                            $stmt = $pdo->prepare("UPDATE tbl_quest_claims SET proof = ? WHERE quest_id = ? AND user_id = ?");
                            $stmt->execute([$screenshot_path, $quest_id, $userWallet]);
                        }
                    }

                    // Create Discord ticket if enabled
                    if ($cheese_config['discord_ticket']) {
                        $ticket_created = createDiscordTicket($userWallet, $quest, "All $required_eggs eggs", $screenshot_path ?? null);
                        $response['discord_ticket'] = $ticket_created;
                    }

                    $response['quest_completed'] = true;
                    $response['quest_reward'] = $quest['reward'];
                    $response['winner_message'] = $cheese_config['winner_message'] ?? "🎯 Congratulations! You found all $required_eggs cheese eggs!";
                    $response['progress'] = "$required_eggs/$required_eggs eggs found!";
                } else {
                    // Still need more cheese eggs
                    $response['quest_completed'] = false;
                    $response['progress'] = "$egg_count/$required_eggs eggs found";
                    $response['message'] = "Great! You found $egg_count of $required_eggs cheese eggs. Keep hunting!";
                }
            }
        }
    }

    error_log("Cheese click processed successfully: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Cheese click error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => '❌ Database error',
        'details' => $e->getMessage(),
        'user_wallet' => $userWallet,
        'egg_id' => $eggId
    ]);
}

// Function to save screenshot
function saveScreenshot($screenshot_data, $userWallet, $quest_id) {
    try {
        // Remove data URL prefix
        $screenshot_data = str_replace('data:image/png;base64,', '', $screenshot_data);
        $screenshot_data = str_replace('data:image/jpeg;base64,', '', $screenshot_data);
        
        // Decode base64
        $image_data = base64_decode($screenshot_data);
        
        // Create screenshots directory if it doesn't exist
        $screenshots_dir = __DIR__ . '/../screenshots/';
        if (!is_dir($screenshots_dir)) {
            mkdir($screenshots_dir, 0755, true);
        }
        
        // Generate filename
        $filename = "cheese_hunt_{$quest_id}_{$userWallet}_" . time() . ".png";
        $filepath = $screenshots_dir . $filename;
        
        // Save file
        if (file_put_contents($filepath, $image_data)) {
            return "screenshots/$filename";
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Screenshot save failed: " . $e->getMessage());
        return null;
    }
}

// Function to create Discord ticket
function createDiscordTicket($userWallet, $quest, $eggId, $screenshot_path) {
    try {
        // This would integrate with your Discord bot
        // For now, we'll log the ticket creation
        $ticket_data = [
            'user_wallet' => $userWallet,
            'quest_id' => $quest['quest_id'],
            'quest_description' => $quest['description'],
            'egg_id' => $eggId,
            'reward' => $quest['reward'],
            'screenshot' => $screenshot_path,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log ticket creation
        $tickets_dir = __DIR__ . '/../discord/tickets/';
        if (!is_dir($tickets_dir)) {
            mkdir($tickets_dir, 0755, true);
        }
        
        $ticket_file = $tickets_dir . "cheese_hunt_ticket_{$quest['quest_id']}_{$userWallet}.json";
        file_put_contents($ticket_file, json_encode($ticket_data, JSON_PRETTY_PRINT));
        
        return [
            'success' => true,
            'ticket_id' => "cheese_hunt_{$quest['quest_id']}_{$userWallet}",
            'message' => 'Discord ticket created successfully'
        ];
    } catch (Exception $e) {
        error_log("Discord ticket creation failed: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>