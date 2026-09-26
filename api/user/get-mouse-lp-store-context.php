<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../lib/mouse-lp-store-service.php';
require_once __DIR__ . '/../lib/genesis-image-url.php';
header('Content-Type: application/json'); header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(405); header('Allow: GET'); echo json_encode(['success'=>false,'error'=>'method_not_allowed']); exit; }
$userId=mouse_lp_store_session_user(); if ($userId==='') { http_response_code(401); echo json_encode(['success'=>false,'error'=>'unauthenticated']); exit; }
try {
 $pdo=getDatabaseConnection(); $season=mouse_lp_store_active_season($pdo);
 $stmt=$pdo->prepare("SELECT o.token_id, o.collection, n.custom_name, o.image_url FROM tbl_nft_ownership o INNER JOIN tbl_nft_custom_names n ON n.token_id=o.token_id AND n.collection=o.collection AND n.user_id=o.user_id WHERE o.user_id=? AND LOWER(COALESCE(o.collection,''))='genesis' AND COALESCE(o.is_verified,0)=1 AND TRIM(COALESCE(n.custom_name,''))<>'' ORDER BY n.custom_name ASC, o.token_id ASC"); $stmt->execute([$userId]);
 $mice=[]; foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){ $totals=mouse_lp_store_totals($pdo,(string)$row['token_id'],(int)$season['season_id']); $mice[]=array_merge(['token_id'=>(string)$row['token_id'],'collection'=>'genesis','custom_name'=>(string)$row['custom_name'],'image_url'=>narrrfs_resolve_genesis_image_url('genesis', $row['image_url'] ?? '') ?: null],$totals); }
 echo json_encode(['success'=>true,'current_season'=>['season_id'=>(int)$season['season_id'],'season_name'=>(string)$season['season_name']],'available_dspoinc'=>mouse_lp_store_available_dspoinc($pdo,$userId),'mice'=>$mice]);
} catch(Throwable $e) { error_log('Mouse LP Store context: '.$e->getMessage()); http_response_code(503); echo json_encode(['success'=>false,'error'=>'context_unavailable']); }
