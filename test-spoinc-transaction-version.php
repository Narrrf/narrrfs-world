<?php
/** Pure version contract tests, no HTTP, database or wallet access. */
require __DIR__ . '/api/partner/spoinc/transaction-version.php';
function version_check(bool $condition, string $label): void {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    echo 'PASS: ' . $label . PHP_EOL;
}
version_check(spoinc_requested_transaction_version([]) === 'v0', 'missing defaults to V0');
foreach (['v0', 'v1'] as $v) version_check(spoinc_requested_transaction_version(['transactionVersion' => $v]) === $v, "accept $v");
foreach ([null, [], 1, 'V1', 'legacy', '', 'v2'] as $v) version_check(spoinc_requested_transaction_version(['transactionVersion' => $v]) === null, 'reject invalid version');
version_check(spoinc_partner_version_matches('v0', null), 'legacy response without version');
version_check(!spoinc_partner_version_matches('v1', null), 'missing V1 response fails closed');
version_check(!spoinc_partner_version_matches('v1', 'v0'), 'mismatched response fails closed');
version_check(spoinc_partner_version_matches('v1', 'v1'), 'matching V1 response');
$header = "\1\0\0"; $key = str_repeat("\0", 32); $blockhash = str_repeat("\3", 32); $signature = str_repeat("\0", 64);
$v1 = "\x81" . $header . str_repeat("\0", 4) . $blockhash . "\0\1" . $key . $signature;
$v0 = "\1" . $signature . "\x80" . $header . "\1" . $key . $blockhash . "\0\0";
version_check(spoinc_partner_payload_version_matches('v1', base64_encode($v1), str_repeat('1', 32)), 'accept valid V1 framing and signer');
version_check(!spoinc_partner_payload_version_matches('v1', base64_encode($v1), 'wrong'), 'reject wrong V1 signer');
version_check(spoinc_partner_payload_version_matches('v0', base64_encode($v0), str_repeat('1', 32)), 'accept valid V0 framing and signer');
version_check(!spoinc_partner_payload_version_matches('v1', base64_encode("\1" . str_repeat("\0", 100))), 'reject V0 in V1 request');
version_check(!spoinc_partner_payload_version_matches('v0', base64_encode("\x81" . str_repeat("\0", 100))), 'reject V1 in V0 request');
version_check(!spoinc_partner_payload_version_matches('v1', base64_encode("\x81" . str_repeat("\0", 4096))), 'reject oversized V1');
version_check(!spoinc_partner_payload_version_matches('v1', '%%%'), 'reject invalid base64');