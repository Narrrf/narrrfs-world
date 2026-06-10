<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
// IMPORTANT:
// Prefer the known local Windows DB first during XAMPP development,
// then fall back to Render/Linux and relative project paths.
$db_candidates = [
    'C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite',
    'C:\\xampp-server\\htdocs\\narrrfs-world\\db\\narrrf_world.sqlite',
    '/var/www/html/db/narrrf_world.sqlite',
    __DIR__ . '/../../db/narrrf_world.sqlite',
    __DIR__ . '/../db/narrrf_world.sqlite'
];

$db_path = null;
foreach ($db_candidates as $candidate) {
    if (file_exists($candidate)) {
        $db_path = $candidate;
        break;
    }
}

if ($db_path === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Database file not found in known locations'
    ]);
    exit;
}

/**
 * Convert mixed request values into a real boolean.
 * This keeps Cheese Hunt config stable even when values arrive as strings like
 * "true", "false", "1", or "0".
 */
function normalizeBoolean($value, bool $default = false): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value)) {
        return $value === 1;
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));

        if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['false', '0', 'no', 'off', ''], true)) {
            return false;
        }
    }

    return $default;
}

try {
    $db = new SQLite3($db_path);
    $db->enableExceptions(true);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Handle JSON input
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($content_type, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
}

$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $type = trim((string)($input['type'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $link = trim((string)($input['link'] ?? ''));
        $reward = intval($input['reward'] ?? 0);
        $created_by = trim((string)($input['created_by'] ?? ''));

        // Role granting options
        $grant_role = normalizeBoolean($input['grant_role'] ?? false, false);
        $role_id = trim((string)($input['role_id'] ?? ''));
        if ($role_id === '') {
            $role_id = null;
        }

        // Cheese quest specific fields
        $cheese_config = null;

        if ($type === 'cheese_hunt') {
            $movement_pattern = trim((string)($input['movement_pattern'] ?? 'random'));
            $movement_speed = trim((string)($input['movement_speed'] ?? 'normal'));
            $hidden_areas = normalizeBoolean($input['hidden_areas'] ?? true, true);
            $cheese_count = intval($input['cheese_count'] ?? 3);
            $discord_ticket = normalizeBoolean($input['discord_ticket'] ?? true, true);
            $winner_message = trim((string)($input['winner_message'] ?? '🎯 Congratulations! You found the cheese!'));

            /**
 * Supported Cheese Hunt movement patterns.
 *
 * Plain language for DEVS:
 * These values must stay synced with the Admin Interface selector and the
 * homepage Cheese Hunt movement engine. If the frontend can send a pattern
 * but this backend list does not allow it, quest creation fails before the
 * funny moving cheese game can start.
 */
$allowed_patterns = [
    'random',
    'edge_hunter',
    'sneaky',
    'corner_lurker',
    'scatter_zone',
    'fair_shuffle'
];
            $allowed_speeds = ['slow', 'normal', 'fast'];

            if (!in_array($movement_pattern, $allowed_patterns, true)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid cheese movement pattern'
                ]);
                break;
            }

            if (!in_array($movement_speed, $allowed_speeds, true)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid cheese movement speed'
                ]);
                break;
            }

/**
 * Cheese Hunt required-click limit.
 *
 * Plain language for DEVS:
 * cheese_count now means how many valid cheese clicks are required to complete
 * the quest. The homepage still displays the existing 3 synced moving cheeses,
 * but players may continue clicking valid cheeses over time until this total
 * is reached. Backend click tracking remains authoritative.
 */
if ($cheese_count < 1 || $cheese_count > 100) {
    echo json_encode([
        'success' => false,
        'error' => 'Cheese Hunt currently supports 1 to 100 required cheese clicks'
    ]);
    break;
}

            if ($winner_message === '') {
                $winner_message = '🎯 Congratulations! You found the cheese!';
            }

            $cheese_config = [
                'movement_pattern' => $movement_pattern,
                'movement_speed' => $movement_speed,
                'hidden_areas' => $hidden_areas,
                'cheese_count' => $cheese_count,
                'discord_ticket' => $discord_ticket,
                'winner_message' => $winner_message
            ];
        }

        // Validation
        if ($type === '' || $description === '' || $reward <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Type, description, and reward are required. Reward must be positive.'
            ]);
            break;
        }

        if ($grant_role && empty($role_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'Role ID is required when grant_role is enabled'
            ]);
            break;
        }

        try {
            // Insert new quest with cheese configuration and role_id
            $stmt = $db->prepare('
                INSERT INTO tbl_quests (type, description, link, reward, created_by, is_active, created_at, cheese_config, role_id) 
                VALUES (?, ?, ?, ?, ?, 1, datetime("now"), ?, ?)
            ');

            $stmt->bindValue(1, $type, SQLITE3_TEXT);
            $stmt->bindValue(2, $description, SQLITE3_TEXT);
            $stmt->bindValue(3, $link, SQLITE3_TEXT);
            $stmt->bindValue(4, $reward, SQLITE3_INTEGER);
            $stmt->bindValue(5, $created_by, SQLITE3_TEXT);
            $stmt->bindValue(6, $cheese_config ? json_encode($cheese_config) : null, SQLITE3_TEXT);
            $stmt->bindValue(7, $grant_role ? $role_id : null, SQLITE3_TEXT);

            $result = $stmt->execute();

            if ($result) {
                $quest_id = $db->lastInsertRowID();
                echo json_encode([
                    'success' => true,
                    'message' => 'Quest created successfully',
                    'quest_id' => $quest_id,
                    'quest' => [
                        'quest_id' => $quest_id,
                        'type' => $type,
                        'description' => $description,
                        'link' => $link,
                        'reward' => $reward,
                        'created_by' => $created_by,
                        'is_active' => 1,
                        'cheese_config' => $cheese_config,
                        'role_id' => $grant_role ? $role_id : null
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to create quest'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'delete':
        $quest_id = intval($input['quest_id'] ?? 0);

        if ($quest_id <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Valid quest ID is required'
            ]);
            break;
        }

        try {
            $stmt = $db->prepare('UPDATE tbl_quests SET is_active = 0 WHERE quest_id = ?');
            $stmt->bindValue(1, $quest_id, SQLITE3_INTEGER);
            $result = $stmt->execute();

            if ($result && $db->changes() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Quest deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Quest not found or already deleted'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        break;
}

$db->close();
?>