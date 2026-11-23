<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/admin-authorization.php';
$actor=narrrfs_require_admin_request('owner_control.player_adjustment.points.v1');
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store, max-age=0');
try {
    $input=json_decode((string)file_get_contents('php://input'),true,8,JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new JsonException();
    require_once __DIR__.'/../../config/database.php'; require_once __DIR__.'/../../lib/owner-player-adjustments-v1.php';
    $db=new SQLite3(getDatabasePath(),SQLITE3_OPEN_READWRITE);
    $result=owner_player_adjust($db,$input,$actor,'points'); $db->close();
    echo json_encode(['success'=>true,'result'=>$result],JSON_THROW_ON_ERROR);
} catch (JsonException) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'invalid_request']);
} catch (OwnerPlayerAdjustmentException $e) { if (isset($db)) $db->close(); http_response_code($e->status); echo json_encode(['success'=>false,'error'=>$e->publicCode]);
} catch (Throwable) { if (isset($db)) $db->close(); error_log('[OWNER ADJUSTMENT] points failed'); http_response_code(500); echo json_encode(['success'=>false,'error'=>'adjustment_unavailable']); }
