<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function respond($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

$rpcUrls = [
    'https://rpc.ankr.com/solana',
    'https://api.mainnet-beta.solana.com'
];

foreach ($rpcUrls as $rpcUrl) {
    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getLatestBlockhash',
        'params' => [
            ['commitment' => 'confirmed']
        ]
    ];

    $ch = curl_init($rpcUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 200) {
        continue;
    }

    $data = json_decode($response, true);
    $value = $data['result']['value'] ?? null;

    if (!is_array($value) || empty($value['blockhash'])) {
        continue;
    }

    respond([
        'success' => true,
        'blockhash' => $value['blockhash'],
        'lastValidBlockHeight' => $value['lastValidBlockHeight'] ?? null,
        'rpc' => $rpcUrl
    ]);
}

respond([
    'success' => false,
    'error' => 'Failed to fetch recent blockhash from all configured RPC endpoints.'
], 502);