<?php
require_once __DIR__ . '/../config/session.php'; require_once __DIR__ . '/../lib/mouse-lp-store-service.php';
header('Content-Type: application/json'); header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(405); header('Allow: GET'); echo json_encode(['success'=>false,'error'=>'method_not_allowed']); exit; }
$userId=mouse_lp_store_session_user();if($userId===''){http_response_code(401);echo json_encode(['success'=>false,'error'=>'unauthenticated']);exit;}
$limit=max(1,min(100,(int)($_GET['limit']??20)));$offset=max(0,(int)($_GET['offset']??0));
try{$pdo=getDatabaseConnection();$count=$pdo->prepare("SELECT COUNT(*) FROM tbl_mouse_lp_store_purchases WHERE purchaser_user_id=? AND payment_method='lp'");$count->execute([$userId]);$total=(int)$count->fetchColumn();$stmt=$pdo->prepare("SELECT purchase_id,source_type,item_title_snapshot,amount_paid,selected_token_id,mouse_name_snapshot,completed_at FROM tbl_mouse_lp_store_purchases WHERE purchaser_user_id=? AND payment_method='lp' ORDER BY completed_at DESC,purchase_id DESC LIMIT ? OFFSET ?");$stmt->bindValue(1,$userId);$stmt->bindValue(2,$limit,PDO::PARAM_INT);$stmt->bindValue(3,$offset,PDO::PARAM_INT);$stmt->execute();echo json_encode(['success'=>true,'purchases'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'pagination'=>['limit'=>$limit,'offset'=>$offset,'total'=>$total,'has_more'=>$offset+$limit<$total]]);}
catch(Throwable $e){error_log('Mouse LP history: '.$e->getMessage());http_response_code(503);echo json_encode(['success'=>false,'error'=>'history_unavailable']);}
