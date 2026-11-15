<?php
declare(strict_types=1);
function check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException('FAIL: ' . $message); }
$ui = file_get_contents(__DIR__ . '/owner-control/v1/lab-store-live-offers.php');
foreach (['NARRRFS_OWNER_LAB_STORE_LIVE_OFFERS_COMMERCIAL_ENABLED', 'commercialEditor(o)', 'Edit Price / Stock', 'Save Price / Stock', 'live-offer-commercial-csrf.php', 'live-offer-commercial.php', "action:'edit_commercial'", 'expected_version:o.owner_control_version', 'commercialKey(payload)', "out.error==='version_conflict'", 'Reload the offers', 'pendingCommercial=null', 'credentials:\'same-origin\''] as $needle) {
    check(str_contains($ui, $needle), $needle);
}
check(str_contains($ui, "confirm('Save price and stock for this live offer?')"), 'confirmation');
check(str_contains($ui, 'Activate offer') && str_contains($ui, 'Disable offer'), 'M2A controls retained');
check(str_contains($ui, 'private drafts, not live offers') && str_contains($ui, 'aria-label="Lab Store"'), 'navigation and draft boundary');
check(!str_contains($ui, '/api/admin/') && !str_contains($ui, 'innerHTML'), 'no legacy route or unsafe HTML');
echo "PASS: Lab Store commercial UI contract\n";
