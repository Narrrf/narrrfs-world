<?php
require_once __DIR__ . '/../config/session.php'; require_once __DIR__ . '/../lib/mouse-lp-store-service.php';
header('Content-Type: application/json'); header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); header('Allow: POST'); echo json_encode(['success'=>false,'error'=>'method_not_allowed']); exit; }
$userId=mouse_lp_store_session_user();if($userId===''){http_response_code(401);echo json_encode(['success'=>false,'error'=>'unauthenticated']);exit;}
try{$result=mouse_lp_store_purchase(getDatabaseConnection(),$userId,mouse_lp_store_input());echo json_encode(array_merge(['success'=>true],$result));}
catch(DomainException $e){http_response_code(409);echo json_encode(['success'=>false,'error'=>$e->getMessage()]);}
catch(InvalidArgumentException $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>'invalid_request']);}
catch(RuntimeException $e){http_response_code(409);echo json_encode(['success'=>false,'error'=>'purchase_rejected','message'=>$e->getMessage()]);}
catch(Throwable $e){error_log('Mouse LP purchase: '.$e->getMessage());http_response_code(500);echo json_encode(['success'=>false,'error'=>'purchase_unavailable']);}
