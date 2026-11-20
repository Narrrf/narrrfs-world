<?php
require_once __DIR__ . '/transaction-binding.php';

/** Validate the documented partner version without accepting aliases or nonstrings. */
function spoinc_requested_transaction_version(array $request): ?string
{
    if (!array_key_exists('transactionVersion', $request)) { return 'v0'; }
    $value = $request['transactionVersion'];
    return is_string($value) && in_array($value, ['v0', 'v1'], true) ? $value : null;
}

/** V0 tolerates older replies without a version; V1 must identify itself explicitly. */
function spoinc_partner_version_matches(string $requested, $returned): bool
{
    if ($returned === null || $returned === '') { return $requested === 'v0'; }
    return is_string($returned) && hash_equals($requested, $returned);
}
/** Check the documented V1 wire prefix, while preserving legacy/V0 replies. */
function spoinc_partner_payload_version_matches(string $requested, string $payload, string $expectedWallet = ''): bool
{
    $wire = base64_decode($payload, true);
    if ($wire === false || $wire === '' || strlen($wire) > ($requested === 'v0' ? 1232 : 4096)) { return false; }
    $transaction = spoinc_binding_extract($payload);
    if ($transaction === null || ($expectedWallet !== '' && !in_array($expectedWallet, $transaction['signers'], true))) { return false; }
    return ($requested === 'v1' && $transaction['version'] === 1)
        || ($requested === 'v0' && in_array($transaction['version'], [0, -1], true));
}