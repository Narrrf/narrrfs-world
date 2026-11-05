<?php
declare(strict_types=1);error_reporting(0);ini_set('display_errors','0');header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
require_once __DIR__.'/../config/admin-session.php';require_once __DIR__.'/../lib/christmas-advent-2026-claim.php';
function christmas26_claim_json(int $c,array $b):void{http_response_code($c);echo json_encode($b);exit;}
if(!christmas26_claim_payout_is_enabled(getenv(CHRISTMAS26_PAYOUT_GATE)?:null,getenv(CHRISTMAS26_POLICY_VERSION_ENV)?:null))christmas26_claim_json(404,['success'=>false,'error'=>'claim_unavailable']);
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Allow: POST');christmas26_claim_json(405,['success'=>false,'error'=>'method_not_allowed']);}
if(!narrrfs_session_has_trusted_discord_oauth_provenance($_SESSION))christmas26_claim_json(401,['success'=>false,'error'=>'authentication_required']);
if(!christmas26_claim_origin_is_allowed($_SERVER)||!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE']??'')),'application/json'))christmas26_claim_json(403,['success'=>false,'error'=>'request_rejected']);
$raw=trim((string)file_get_contents('php://input'));$body=json_decode($raw,true);if(!str_starts_with($raw,'{')||!is_array($body)||$body!==[])christmas26_claim_json(400,['success'=>false,'error'=>'request_rejected']);
if(!christmas26_claim_csrf_is_valid($_SESSION,$_SERVER['HTTP_X_CSRF_TOKEN']??null))christmas26_claim_json(403,['success'=>false,'error'=>'csrf_rejected']);
christmas26_claim_json(503,['success'=>false,'error'=>'claim_policy_unavailable']);