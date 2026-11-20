<?php
/**
 * Read-only Solana wire helpers for comparing an observed transaction to the
 * partner-prepared message. No database or network calls are made here.
 */
function spoinc_binding_base58(string $bytes): string
{
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $zeros = 0;
    $length = strlen($bytes);
    while ($zeros < $length && ord($bytes[$zeros]) === 0) { $zeros++; }
    $digits = [];
    for ($i = $zeros; $i < $length; $i++) {
        $carry = ord($bytes[$i]);
        foreach ($digits as $j => $digit) {
            $value = $digit * 256 + $carry;
            $digits[$j] = $value % 58;
            $carry = intdiv($value, 58);
        }
        while ($carry > 0) { $digits[] = $carry % 58; $carry = intdiv($carry, 58); }
    }
    $result = str_repeat('1', $zeros);
    for ($i = count($digits) - 1; $i >= 0; $i--) { $result .= $alphabet[$digits[$i]]; }
    return $result;
}

function spoinc_binding_shortvec(string $wire, int &$offset): ?int
{
    $value = 0;
    for ($i = 0; $i < 3; $i++) {
        if ($offset >= strlen($wire)) { return null; }
        $byte = ord($wire[$offset++]);
        $value |= ($byte & 0x7f) << (7 * $i);
        if (($byte & 0x80) === 0) {
            if (($i > 0 && $byte === 0) || $value > 65535) { return null; }
            return $value;
        }
    }
    return null;
}

/** Extract immutable message bytes, first signature and signer keys; reject malformed framing. */
function spoinc_binding_extract(string $encoded): ?array
{
    $wire = base64_decode($encoded, true);
    if ($wire === false || strlen($wire) < 68 || strlen($wire) > 4096) { return null; }
    if (ord($wire[0]) === 0x81) {
        $signatureCount = ord($wire[1]);
        $messageEnd = strlen($wire) - 64 * $signatureCount;
        if ($signatureCount < 1 || $messageEnd < 42) { return null; }
        $message = substr($wire, 0, $messageEnd);
        $signature = substr($wire, $messageEnd, 64);
        $accountCount = ord($message[41]);
        $accountsOffset = 42;
        $version = 1;
    } else {
        $offset = 0;
        $signatureCount = spoinc_binding_shortvec($wire, $offset);
        if ($signatureCount === null || $signatureCount < 1 || $offset + 64 * $signatureCount >= strlen($wire)) { return null; }
        $signature = substr($wire, $offset, 64);
        $message = substr($wire, $offset + 64 * $signatureCount);
        $versionByte = ord($message[0]);
        if (($versionByte & 0x80) && $versionByte !== 0x80) { return null; }
        $version = ($versionByte === 0x80) ? 0 : -1;
        $headerOffset = $version === 0 ? 1 : 0;
        if (strlen($message) < $headerOffset + 4 || ord($message[$headerOffset]) !== $signatureCount) { return null; }
        $accountsOffset = $headerOffset + 3;
        $accountCount = spoinc_binding_shortvec($message, $accountsOffset);
        if ($accountCount === null) { return null; }
    }
    if ($accountCount < $signatureCount || $accountsOffset + 32 * $accountCount > strlen($message)) { return null; }
    $signers = [];
    for ($i = 0; $i < $signatureCount; $i++) {
        $signers[] = spoinc_binding_base58(substr($message, $accountsOffset + $i * 32, 32));
    }
    return ['version' => $version, 'message' => $message, 'signature' => spoinc_binding_base58($signature), 'signers' => $signers];
}

/** Confirm observed wire message is identical to the saved partner payload and signed by its claimant. */
function spoinc_binding_matches(string $prepared, string $observed, string $signature, string $wallet): bool
{
    $expected = spoinc_binding_extract($prepared);
    $actual = spoinc_binding_extract($observed);
    return $expected !== null && $actual !== null
        && $expected['version'] === $actual['version']
        && hash_equals($expected['message'], $actual['message'])
        && hash_equals($actual['signature'], $signature)
        && in_array($wallet, $actual['signers'], true);
}
/** Validate a decoded JSON-RPC getTransaction result against the saved intent. */
function spoinc_binding_rpc_matches(array $rpc, array $intent, string $signature, bool $expectFailed = false): bool
{
    if (!isset($rpc['result']) || !is_array($rpc['result'])) { return false; }
    $result = $rpc['result'];
    if (!isset($result['meta']) || !is_array($result['meta']) || !array_key_exists('err', $result['meta'])) { return false; }
    if (($result['meta']['err'] !== null) !== $expectFailed) { return false; }
    $wire = $result['transaction'] ?? null;
    if (!is_array($wire) || count($wire) !== 2 || !is_string($wire[0]) || $wire[1] !== 'base64') { return false; }
    $stored = json_decode((string)($intent['raw_response_json'] ?? ''), true);
    $prepared = is_array($stored) ? ($stored['safe_response']['transaction'] ?? '') : '';
    $wallet = $intent['wallet'] ?? '';
    if (!is_string($prepared) || !is_string($wallet) || $prepared === '' || $wallet === '') { return false; }
    return spoinc_binding_matches($prepared, $wire[0], $signature, $wallet);
}

/** Fetch confirmed wire data; failures are inconclusive and must not cause settlement. */
function spoinc_binding_fetch_rpc(string $signature): ?array
{
    if (!function_exists('curl_init')) { return null; }
    $rpc = trim((string)getenv('SOLANA_RPC_URL'));
    if ($rpc === '') { $rpc = trim((string)getenv('HELIUS_RPC_URL')); }
    if ($rpc === '') { $rpc = 'https://api.mainnet-beta.solana.com'; }
    $body = json_encode(['jsonrpc' => '2.0', 'id' => 'spoinc-binding', 'method' => 'getTransaction',
        'params' => [$signature, ['encoding' => 'base64', 'commitment' => 'confirmed', 'maxSupportedTransactionVersion' => 1]]]);
    $ch = curl_init($rpc);
    if ($ch === false || $body === false) { return null; }
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'], CURLOPT_POSTFIELDS => $body]);
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $error !== '' || $status < 200 || $status >= 300) { return null; }
    $decoded = json_decode($raw, true);
    return is_array($decoded) && !isset($decoded['error']) ? $decoded : null;
}
/** Add unsigned decimal integers without floating point or an optional PHP extension. */
function spoinc_binding_decimal_add(string $a, string $b): string
{
    $i = strlen($a) - 1; $j = strlen($b) - 1; $carry = 0; $out = '';
    while ($i >= 0 || $j >= 0 || $carry > 0) {
        $sum = ($i >= 0 ? ord($a[$i--]) - 48 : 0) + ($j >= 0 ? ord($b[$j--]) - 48 : 0) + $carry;
        $out = (string)($sum % 10) . $out; $carry = intdiv($sum, 10);
    }
    $out = ltrim($out, '0');
    return $out === '' ? '0' : $out;
}

/** Convert a decimal token amount to base units exactly, without floats. */
function spoinc_binding_amount_units(string $amount, int $decimals): ?string
{
    if ($decimals < 0 || $decimals > 18 || !preg_match('/^\d{1,20}(?:\.\d{1,4})?$/D', $amount)) { return null; }
    [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
    if (strlen($fraction) > $decimals) {
        if (trim(substr($fraction, $decimals), '0') !== '') { return null; }
        $fraction = substr($fraction, 0, $decimals);
    }
    $units = ltrim($whole . str_pad($fraction, $decimals, '0'), '0');
    return $units === '' ? null : $units;
}

/** Verify exact owner/mint token debit using paired RPC pre/post token balances. */
function spoinc_binding_rpc_debit_matches(array $rpc, array $intent, array $config): bool
{
    $request = json_decode((string)($intent['raw_request_json'] ?? ''), true);
    $mint = is_array($request) ? ($request['token_mint'] ?? null) : null;
    $wallet = $intent['wallet'] ?? null;
    $configuredMint = $config['token_mint'] ?? null;
    $decimals = $config['token_decimals'] ?? null;
    if (!is_string($mint) || !is_string($configuredMint) || $mint === '' || !hash_equals($mint, $configuredMint)
        || !is_string($wallet) || $wallet === '' || (!is_int($decimals) && !(is_string($decimals) && ctype_digit($decimals)))) { return false; }
    $decimals = (int)$decimals;
    $expected = spoinc_binding_amount_units((string)($intent['spoinc_amount'] ?? ''), $decimals);
    if ($expected === null) { return false; }
    $meta = $rpc['result']['meta'] ?? null;
    if (!is_array($meta) || !isset($meta['preTokenBalances'], $meta['postTokenBalances'])
        || !is_array($meta['preTokenBalances']) || !is_array($meta['postTokenBalances'])) { return false; }
    $maps = [];
    foreach (['preTokenBalances', 'postTokenBalances'] as $side) {
        $map = [];
        foreach ($meta[$side] as $balance) {
            if (!is_array($balance)) { return false; }
            if (($balance['mint'] ?? null) !== $mint) { continue; }
            if (!isset($balance['owner']) || !is_string($balance['owner']) || $balance['owner'] === '') { return false; }
            if ($balance['owner'] !== $wallet) { continue; }
            $index = $balance['accountIndex'] ?? null;
            $token = $balance['uiTokenAmount'] ?? null;
            if (!is_int($index) || $index < 0 || isset($map[$index]) || !is_array($token)
                || ($token['decimals'] ?? null) !== $decimals || !isset($token['amount'])
                || !is_string($token['amount']) || !preg_match('/^\d{1,20}$/D', $token['amount'])) { return false; }
            $map[$index] = $token['amount'];
        }
        $maps[$side] = $map;
    }
    $pre = $maps['preTokenBalances']; $post = $maps['postTokenBalances'];
    if (!$pre || count($pre) !== count($post)) { return false; }
    $preTotal = '0'; $postTotal = '0';
    foreach ($pre as $index => $balance) {
        if (!array_key_exists($index, $post)) { return false; }
        $preTotal = spoinc_binding_decimal_add($preTotal, $balance);
        $postTotal = spoinc_binding_decimal_add($postTotal, $post[$index]);
    }
    return hash_equals($preTotal, spoinc_binding_decimal_add($postTotal, $expected));
}