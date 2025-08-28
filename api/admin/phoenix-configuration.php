<?php
// 🔥 PHOENIX INVADERS CONFIGURATION API
// Manages Phoenix wave system configuration for Space Invaders

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Function to ensure JSON response
function sendJsonResponse($success, $data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Function to handle exceptions
function handleException($e, $context = 'Unknown operation') {
    error_log("Phoenix Configuration API Error in $context: " . $e->getMessage());
    sendJsonResponse(false, null, "Error in $context: " . $e->getMessage(), 500);
}

// Include database connection
try {
    require_once __DIR__ . '/../config/database.php';
} catch (Exception $e) {
    handleException($e, 'Database configuration loading');
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get Phoenix configuration
            try {
                $db = getSQLite3Connection();
                if (!$db) {
                    throw new Exception('Database connection failed');
                }
                
                // Check if Phoenix configuration table exists, if not create it
                $db->exec("CREATE TABLE IF NOT EXISTS phoenix_configuration (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    wave_frequency INTEGER DEFAULT 3,
                    base_phoenix_count INTEGER DEFAULT 5,
                    max_phoenix_per_wave INTEGER DEFAULT 20,
                    egg_laying_rate REAL DEFAULT 0.3,
                    egg_hatch_time INTEGER DEFAULT 300,
                    mini_phoenix_health INTEGER DEFAULT 50,
                    difficulty_scaling REAL DEFAULT 1.2,
                    phoenix_health INTEGER DEFAULT 100,
                    phoenix_speed REAL DEFAULT 2.0,
                    formation_patterns TEXT DEFAULT '[\"v\",\"diamond\",\"spiral\",\"cluster\",\"dive\"]',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                
                // Get Phoenix configuration
                $stmt = $db->prepare("SELECT * FROM phoenix_configuration ORDER BY id DESC LIMIT 1");
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($row) {
                    $config = [
                        'waveFrequency' => (int)$row['wave_frequency'],
                        'basePhoenixCount' => (int)$row['base_phoenix_count'],
                        'maxPhoenixPerWave' => (int)$row['max_phoenix_per_wave'],
                        'eggLayingRate' => (float)$row['egg_laying_rate'],
                        'eggHatchTime' => (int)$row['egg_hatch_time'],
                        'miniPhoenixHealth' => (int)$row['mini_phoenix_health'],
                        'difficultyScaling' => (float)$row['difficulty_scaling'],
                        'phoenixHealth' => (int)$row['phoenix_health'],
                        'phoenixSpeed' => (float)$row['phoenix_speed'],
                        'formationPatterns' => json_decode($row['formation_patterns'], true) ?: ['v', 'diamond', 'spiral', 'cluster', 'dive']
                    ];
                } else {
                    // Return default configuration if none exists
                    $config = [
                        'waveFrequency' => 3,
                        'basePhoenixCount' => 5,
                        'maxPhoenixPerWave' => 20,
                        'eggLayingRate' => 0.3,
                        'eggHatchTime' => 300,
                        'miniPhoenixHealth' => 50,
                        'difficultyScaling' => 1.2,
                        'phoenixHealth' => 100,
                        'phoenixSpeed' => 2.0,
                        'formationPatterns' => ['v', 'diamond', 'spiral', 'cluster', 'dive']
                    ];
                }
                
                sendJsonResponse(true, ['config' => $config]);
                
            } catch (Exception $e) {
                handleException($e, 'Getting Phoenix configuration');
            }
            break;
            
        case 'POST':
            // Save Phoenix configuration
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['action']) || $input['action'] !== 'save') {
                    sendJsonResponse(false, null, 'Invalid action specified', 400);
                }
                
                if (!isset($input['config'])) {
                    sendJsonResponse(false, null, 'No configuration data provided', 400);
                }
                
                $config = $input['config'];
                
                // Validate configuration data
                $requiredFields = [
                    'waveFrequency', 'basePhoenixCount', 'maxPhoenixPerWave',
                    'eggLayingRate', 'eggHatchTime', 'miniPhoenixHealth',
                    'difficultyScaling', 'phoenixHealth', 'phoenixSpeed', 'formationPatterns'
                ];
                
                foreach ($requiredFields as $field) {
                    if (!isset($config[$field])) {
                        sendJsonResponse(false, null, "Missing required field: $field", 400);
                    }
                }
                
                $db = getSQLite3Connection();
                if (!$db) {
                    throw new Exception('Database connection failed');
                }
                
                // Ensure table exists
                $db->exec("CREATE TABLE IF NOT EXISTS phoenix_configuration (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    wave_frequency INTEGER DEFAULT 3,
                    base_phoenix_count INTEGER DEFAULT 5,
                    max_phoenix_per_wave INTEGER DEFAULT 20,
                    egg_laying_rate REAL DEFAULT 0.3,
                    egg_hatch_time INTEGER DEFAULT 300,
                    mini_phoenix_health INTEGER DEFAULT 50,
                    difficulty_scaling REAL DEFAULT 1.2,
                    phoenix_health INTEGER DEFAULT 100,
                    phoenix_speed REAL DEFAULT 2.0,
                    formation_patterns TEXT DEFAULT '[\"v\",\"diamond\",\"spiral\",\"cluster\",\"dive\"]',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                
                // Clear existing configuration and insert new one
                $db->exec("DELETE FROM phoenix_configuration");
                
                $stmt = $db->prepare("INSERT INTO phoenix_configuration (
                    wave_frequency, base_phoenix_count, max_phoenix_per_wave,
                    egg_laying_rate, egg_hatch_time, mini_phoenix_health,
                    difficulty_scaling, phoenix_health, phoenix_speed, formation_patterns
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bindValue(1, $config['waveFrequency'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $config['basePhoenixCount'], SQLITE3_INTEGER);
                $stmt->bindValue(3, $config['maxPhoenixPerWave'], SQLITE3_INTEGER);
                $stmt->bindValue(4, $config['eggLayingRate'], SQLITE3_FLOAT);
                $stmt->bindValue(5, $config['eggHatchTime'], SQLITE3_INTEGER);
                $stmt->bindValue(6, $config['miniPhoenixHealth'], SQLITE3_INTEGER);
                $stmt->bindValue(7, $config['difficultyScaling'], SQLITE3_FLOAT);
                $stmt->bindValue(8, $config['phoenixHealth'], SQLITE3_INTEGER);
                $stmt->bindValue(9, $config['phoenixSpeed'], SQLITE3_FLOAT);
                $stmt->bindValue(10, json_encode($config['formationPatterns']), SQLITE3_TEXT);
                
                $result = $stmt->execute();
                
                if ($result) {
                    sendJsonResponse(true, ['message' => 'Phoenix configuration saved successfully']);
                } else {
                    sendJsonResponse(false, null, 'Failed to save Phoenix configuration', 500);
                }
                
            } catch (Exception $e) {
                handleException($e, 'Saving Phoenix configuration');
            }
            break;
            
        default:
            sendJsonResponse(false, null, 'Method not allowed', 405);
            break;
    }
    
} catch (Exception $e) {
    handleException($e, 'Main operation');
}
?>
