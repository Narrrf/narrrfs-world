<?php
declare(strict_types=1);
require_once __DIR__ . '/api/lib/owner-genetic-slot-grant-v1.php';
function check(bool $ok, string $label): void { if (!$ok) { throw new RuntimeException('FAIL ' . $label); } echo 'PASS: ' . $label . "\n"; }
function fixtures(): SQLite3 {
    $db = new SQLite3(':memory:');
    $db->enableExceptions(true);
    $db->exec('CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY)');
    $db->exec('CREATE TABLE tbl_user_genetic_upgrade_slots (user_id TEXT PRIMARY KEY, upgrade_slots INTEGER, created_at TEXT, updated_at TEXT, updated_by TEXT)');
    $db->exec('CREATE TABLE tbl_user_genetic_upgrade_slot_audit (audit_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, old_upgrade_slots INTEGER, new_upgrade_slots INTEGER, admin_user_id TEXT, action_type TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    $db->exec("INSERT INTO tbl_users (discord_id) VALUES ('12345678901234567')");
    return $db;
}
function request(int $old, int $new): array { return ['user_id'=>'12345678901234567','expected_upgrade_slots'=>$old,'new_upgrade_slots'=>$new,'reason'=>'1k_mint_contest']; }
function reject(SQLite3 $db, array $request, string $code): void {
    try { narrrfs_owner_grant_genetic_slot($db, $request, '98765432109876543'); }
    catch (NarrrfsOwnerGeneticSlotGrantException $e) { check($e->publicCode() === $code, $code); return; }
    throw new RuntimeException('FAIL expected rejection ' . $code);
}
$db = fixtures();
$r = narrrfs_owner_grant_genetic_slot($db, request(1,2), '98765432109876543');
check($r['old_upgrade_slots']===1 && $r['new_upgrade_slots']===2, 'missing row 1->2');
reject($db, request(1,2), 'stale_entitlement');
$r = narrrfs_owner_grant_genetic_slot($db, request(2,3), '98765432109876543');
check($r['new_upgrade_slots']===3, '2->3');
reject($db, request(2,3), 'stale_entitlement');
reject($db, request(3,4), 'invalid_grant');
reject($db, request(2,1), 'invalid_grant');
check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_user_genetic_upgrade_slot_audit')===2, 'exactly two audit rows');
check((string)$db->querySingle('SELECT admin_user_id FROM tbl_user_genetic_upgrade_slot_audit ORDER BY audit_id DESC LIMIT 1')==='98765432109876543', 'server owner identity audited');
check((int)$db->querySingle('SELECT upgrade_slots FROM tbl_user_genetic_upgrade_slots')===3, 'final entitlement 3');
$db->exec("UPDATE tbl_user_genetic_upgrade_slots SET upgrade_slots=99");
reject($db, request(1,2), 'invalid_entitlement_state');
$db->close();
$db = fixtures();
$db->exec('CREATE TRIGGER fail_slot_audit BEFORE INSERT ON tbl_user_genetic_upgrade_slot_audit BEGIN SELECT RAISE(ABORT, \'audit_failure\'); END');
$auditFailureCaught = false;
try {
    narrrfs_owner_grant_genetic_slot($db, request(1,2), '98765432109876543');
} catch (Throwable $error) {
    check(str_contains($error->getMessage(), 'audit_failure'), 'expected audit trigger failure');
    $auditFailureCaught = true;
}
check($auditFailureCaught, 'audit trigger rejects grant');
check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_user_genetic_upgrade_slots')===0, 'audit failure rolls back slot write');
check((int)$db->querySingle('SELECT COUNT(*) FROM tbl_user_genetic_upgrade_slot_audit')===0, 'audit failure leaves no audit row');
$db->close();
echo "PASS: Disposable-only R5.4 tests complete.\n";
