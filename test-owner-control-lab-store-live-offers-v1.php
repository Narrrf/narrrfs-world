<?php
declare(strict_types=1);
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-authorization.php';
function t(bool $ok, string $message): void { if (!$ok) throw new RuntimeException('FAIL: ' . $message); }
$gate = 'NARRRFS_OWNER_LAB_STORE_LIVE_OFFERS_READ_ENABLED';
$old = getenv($gate);
putenv($gate . '=1');
$policy = (require __DIR__ . '/api/config/admin-route-policies.php')['routes']['owner_control.lab_store_live_offers.read.v1'] ?? null;
$id = '111111111111111111'; $now = 2000000000;
$session = ['discord_id' => $id, NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE, NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => $now, NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => $now, NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => $now + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS];
t(is_array($policy), 'policy');
foreach (['GET','HEAD'] as $method) t(narrrfs_admin_authorization_decision($policy, $session, ['method' => $method], [$id], $now)['allowed'] === true, $method);
foreach (['POST','PUT','PATCH','DELETE'] as $method) t(narrrfs_admin_authorization_decision($policy, $session, ['method' => $method], [$id], $now)['status'] === 405, $method);
t(narrrfs_admin_authorization_decision($policy, [], ['method' => 'GET'], [$id], $now)['status'] === 401, 'anonymous');
t(narrrfs_admin_authorization_decision($policy, $session, ['method' => 'GET'], ['222222222222222222'], $now)['status'] === 403, 'non-owner');
putenv($gate);
$disabled = (require __DIR__ . '/api/config/admin-route-policies.php')['routes']['owner_control.lab_store_live_offers.read.v1'] ?? null;
t(narrrfs_admin_authorization_decision($disabled, $session, ['method' => 'GET'], [$id], $now)['status'] === 404, 'default-off');
$old === false ? putenv($gate) : putenv($gate . '=' . $old);
$route = file_get_contents(__DIR__ . '/api/owner-control/v1/lab-store/live-offers.php');
foreach (['SQLITE3_OPEN_READONLY','PRAGMA query_only = ON','FROM tbl_mouse_lp_store_offers','Cache-Control: no-store','offer_id','lp_price','dspoinc_price','stock','LIMIT :limit',"'writes' => false"] as $needle) t(str_contains($route, $needle), $needle);
foreach (['SELECT *','tbl_mouse_lp_store_purchases','tbl_user_inventory','tbl_user_genetic_items','tbl_user_scores','tbl_score_adjustments','INSERT ','UPDATE ','DELETE ','BEGIN','COMMIT','ROLLBACK','source_item_id\' =>'] as $needle) t(!str_contains($route, $needle), 'forbidden ' . $needle);
echo "PASS: Lab Store M2-R live offers API\n";
