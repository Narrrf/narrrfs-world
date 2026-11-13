<?php
declare(strict_types=1);
function t(bool $ok, string $message): void { if (!$ok) throw new RuntimeException('FAIL: ' . $message); }
$ui = file_get_contents(__DIR__ . '/owner-control/v1/lab-store-live-offers.php');
foreach (['owner_control.lab_store_live_offers.ui.v1','/api/owner-control/v1/lab-store/live-offers.php','credentials:\'same-origin\'','cache:\'no-store\'','offer_id','lp_price','dspoinc_price','stock','Show more','Open Draft Workspace','textContent','Media unavailable','addEventListener(\'error\''] as $needle) t(str_contains($ui, $needle), $needle);
foreach (['<form','csrf','method:\'POST\'','draft-create.php','draft-update.php','data-purchase','data-publish','data-activate','data-disable','innerHTML','/api/admin/'] as $needle) t(!str_contains($ui, $needle), 'forbidden ' . $needle);
$drafts = file_get_contents(__DIR__ . '/owner-control/v1/lab-store-drafts.php');
t(str_contains($drafts, 'lab-store-live-offers.php'), 'draft cross-link');
echo "PASS: Lab Store M2-R live offers UI\n";
