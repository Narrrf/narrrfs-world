<?php
declare(strict_types=1);
function t(bool $ok, string $message): void { if (!$ok) throw new RuntimeException('FAIL: ' . $message); }
$ui = file_get_contents(__DIR__ . '/owner-control/v1/lab-store-live-offers.php');
foreach (['owner_control.lab_store_live_offers.ui.v1','/api/owner-control/v1/lab-store/live-offers.php','credentials:\'same-origin\'','cache:\'no-store\'','offer_id','lp_price','dspoinc_price','stock','Show more','Open Draft Workspace','textContent','Media unavailable','addEventListener(\'error\''] as $needle) t(str_contains($ui, $needle), $needle);
foreach (['<form','draft-create.php','draft-update.php','data-purchase','data-publish','innerHTML','/api/admin/'] as $needle) t(!str_contains($ui, $needle), 'forbidden ' . $needle);
t(str_contains($ui, 'live-offer-csrf.php') && str_contains($ui, 'live-offer-active.php'), 'scoped M2A endpoints');
t(str_contains($ui, 'pendingToggleKeys') && str_contains($ui, 'toggleTargetKey') && str_contains($ui, 'activeToggleTarget') && str_contains($ui, 'selectToggleTarget') && str_contains($ui, 'pendingToggleKeys.clear()') && str_contains($ui, 'pendingToggleKeys.delete(target);if(activeToggleTarget===target)activeToggleTarget=null;return') && str_contains($ui, 'Retry uses the same request key'), 'M2A retry, cross-target replacement, and cancel-clear lifecycle');
$drafts = file_get_contents(__DIR__ . '/owner-control/v1/lab-store-drafts.php');
t(str_contains($drafts, 'lab-store-live-offers.php'), 'draft cross-link');
echo "PASS: Lab Store M2-R live offers UI\n";
