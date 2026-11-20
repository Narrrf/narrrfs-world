<?php
/** Disposable pure-function tests; no database, network or wallet access. */
require __DIR__ . '/api/partner/spoinc/transaction-binding.php';
function check_binding(bool $ok, string $label): void {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    echo 'PASS: ' . $label . PHP_EOL;
}
$walletBytes = str_repeat("\0", 32);
$wallet = str_repeat('1', 32);
$sigBytes = str_repeat("\2", 64);
$sig = spoinc_binding_base58($sigBytes);
$header = "\1\0\0";
$blockhash = str_repeat("\3", 32);
$fixtures = [
    'v0' => ["\1" . str_repeat("\0", 64) . "\x80" . $header . "\1" . $walletBytes . $blockhash . "\0\0",
             "\1" . $sigBytes . "\x80" . $header . "\1" . $walletBytes . $blockhash . "\0\0"],
    'v1' => ["\x81" . $header . str_repeat("\0", 4) . $blockhash . "\0\1" . $walletBytes . str_repeat("\0", 64),
             "\x81" . $header . str_repeat("\0", 4) . $blockhash . "\0\1" . $walletBytes . $sigBytes]
];
foreach ($fixtures as $version => [$prepared, $observed]) {
    $p = base64_encode($prepared); $o = base64_encode($observed);
    check_binding(spoinc_binding_matches($p, $o, $sig, $wallet), "$version matches prepared message");
    check_binding(!spoinc_binding_matches($p, $o, $sig, 'wrong'), "$version rejects wrong signer");
    check_binding(!spoinc_binding_matches($p, $o, 'wrong', $wallet), "$version rejects wrong signature");
    $changed = $observed; $changed[strlen($changed) - ($version === 'v1' ? 65 : 3)] = "\4";
    check_binding(!spoinc_binding_matches($p, base64_encode($changed), $sig, $wallet), "$version rejects modified message");
    $intent = ['wallet' => $wallet, 'raw_response_json' => json_encode(['safe_response' => ['transaction' => $p]])];
    $rpc = ['result' => ['meta' => ['err' => null], 'transaction' => [$o, 'base64']]];
    check_binding(spoinc_binding_rpc_matches($rpc, $intent, $sig), "$version verifies RPC payload");
    $rpc['result']['meta']['err'] = ['InstructionError' => [0, 'failed']];
    check_binding(!spoinc_binding_rpc_matches($rpc, $intent, $sig), "$version rejects failed RPC transaction");
    unset($intent['raw_response_json']);
    check_binding(!spoinc_binding_rpc_matches(['result' => ['meta' => ['err' => null], 'transaction' => [$o, 'base64']]], $intent, $sig), "$version rejects missing prepared transaction");
}
check_binding(spoinc_binding_extract('!!invalid') === null, 'rejects invalid base64');
check_binding(spoinc_binding_extract(base64_encode("\x81\1")) === null, 'rejects truncated V1');
check_binding(!spoinc_binding_rpc_matches(['result' => null], [], 'x'), 'rejects missing RPC result');
$intent = ['wallet' => 'wallet', 'spoinc_amount' => '1.2500', 'raw_request_json' => json_encode(['token_mint' => 'mint'])];
$config = ['token_mint' => 'mint', 'token_decimals' => 6];
$balance = static fn(int $index, string $amount): array => ['accountIndex' => $index, 'owner' => 'wallet', 'mint' => 'mint', 'uiTokenAmount' => ['amount' => $amount, 'decimals' => 6]];
$rpc = ['result' => ['meta' => ['preTokenBalances' => [$balance(3, '3000000'), $balance(4, '1000000')], 'postTokenBalances' => [$balance(3, '1750000'), $balance(4, '1000000')]]]];
check_binding(spoinc_binding_rpc_debit_matches($rpc, $intent, $config), 'exact multi-account owner/mint/amount debit');
$wrong = $intent; $wrong['spoinc_amount'] = '1.2501';
check_binding(!spoinc_binding_rpc_debit_matches($rpc, $wrong, $config), 'rejects wrong debit amount');
$wrong = $config; $wrong['token_mint'] = 'other';
check_binding(!spoinc_binding_rpc_debit_matches($rpc, $intent, $wrong), 'rejects wrong configured mint');
$wrong = $intent; $wrong['wallet'] = 'other';
check_binding(!spoinc_binding_rpc_debit_matches($rpc, $wrong, $config), 'rejects wrong debit owner');
$wrong = $rpc; unset($wrong['result']['meta']['postTokenBalances'][0]['owner']);
check_binding(!spoinc_binding_rpc_debit_matches($wrong, $intent, $config), 'rejects missing target-mint owner');
$wrong = $rpc; array_pop($wrong['result']['meta']['postTokenBalances']);
check_binding(!spoinc_binding_rpc_debit_matches($wrong, $intent, $config), 'rejects unpaired token account');
$wrong = $rpc; $wrong['result']['meta']['preTokenBalances'][] = $balance(3, '3000000');
check_binding(!spoinc_binding_rpc_debit_matches($wrong, $intent, $config), 'rejects duplicate account');
check_binding(spoinc_binding_amount_units('1.2500', 6) === '1250000', 'converts decimal amount exactly');
check_binding(spoinc_binding_amount_units('0.0001', 2) === null, 'rejects inexpressible precision');
check_binding(spoinc_binding_decimal_add('99999999999999999999', '1') === '100000000000000000000', 'adds large amounts without floats');