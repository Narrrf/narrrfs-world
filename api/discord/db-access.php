<?php
header('Content-Type: application/json');

// Verify bot token
$headers = getallheaders();
$auth_token = isset($headers['Authorization']) ? $headers['Authorization'] : '';
if ($auth_token !== $_ENV['DISCORD_BOT_SECRET']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Connect to database
$db = new SQLite3('/var/www/html/db/narrrf_world.sqlite');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$action = isset($data['action']) ? $data['action'] : '';

switch ($action) {
    case 'query':
        $query = isset($data['query']) ? $data['query'] : '';
        $params = isset($data['params']) ? $data['params'] : [];
        
        try {
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }
            $result = $stmt->execute();
            
            $rows = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rows[] = $row;
            }
            
            echo json_encode(['success' => true, 'data' => $rows]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

$db->close(); 