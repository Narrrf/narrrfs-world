<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/admin-session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function end_capture(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if (getenv('NARRRFS_CHEESE_HUNT_PLAYER_ENABLED') !== '1') {
    end_capture(404, ['success' => false, 'error' => 'unavailable']);
}

$contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST' || $contentType !== 'application/json') {
    end_capture(405, ['success' => false, 'error' => 'method_not_allowed']);
}

$host = strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''), 2)[0]);
$expectedOrigin = ($host === 'localhost' || $host === '127.0.0.1')
    ? 'http://localhost'
    : ($host === 'narrrfs.world' ? 'https://narrrfs.world' : null);
$rawOrigin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));

if (
    $expectedOrigin === null ||
    $rawOrigin === '' ||
    !hash_equals($expectedOrigin, strtolower(rtrim($rawOrigin, '/')))
) {
    end_capture(403, ['success' => false, 'error' => 'origin_rejected']);
}

if (!narrrfs_session_has_trusted_discord_oauth_provenance($_SESSION)) {
    end_capture(401, ['success' => false, 'error' => 'authentication_required']);
}

$playerId = (string)$_SESSION['discord_id'];
session_write_close();

$db = null;

try {
    $in = json_decode((string)file_get_contents('php://input'), true, 4, JSON_THROW_ON_ERROR);
    if (!is_array($in)) {
        end_capture(400, ['success' => false, 'error' => 'invalid_request']);
    }

    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../lib/owner-cheese-hunt-v1.php';

    $db = new SQLite3(getDatabasePath(), SQLITE3_OPEN_READWRITE);
    $db->busyTimeout(5000);
    $out = cheese_hunt_capture($db, $playerId, $in);
    echo json_encode(['success' => true, 'result' => $out], JSON_UNESCAPED_SLASHES);
} catch (JsonException) {
    end_capture(400, ['success' => false, 'error' => 'invalid_request']);
} catch (CheeseHuntException $e) {
    end_capture($e->status, ['success' => false, 'error' => $e->publicCode]);
} catch (Throwable) {
    end_capture(500, ['success' => false, 'error' => 'capture_unavailable']);
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
