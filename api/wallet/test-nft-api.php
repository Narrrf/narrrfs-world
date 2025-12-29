<?php
/**
 * Simple test endpoint to verify NFT API works locally
 * Access: http://localhost/api/wallet/test-nft-api.php?wallet=YOUR_WALLET_ADDRESS
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$testWallet = $_GET['wallet'] ?? '3xALCAWami4H4LpMVjxnbqKcaEZ8jqqUm7aG8jUtC9Wq'; // Your wallet
$testCollection = $_GET['collection'] ?? 'AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML'; // Genesis Genetic

echo json_encode([
    'test' => 'NFT API Local Test',
    'wallet' => $testWallet,
    'collection' => $testCollection,
    'message' => 'Calling get-nfts.php...'
], JSON_PRETTY_PRINT);

// Call the actual API
$apiUrl = "http://localhost/api/wallet/get-nfts.php?wallet=$testWallet&collection=$testCollection";
$response = @file_get_contents($apiUrl);

if ($response === false) {
    echo json_encode([
        'error' => 'Failed to call get-nfts.php',
        'suggestion' => 'Make sure Apache is running and the URL is correct'
    ], JSON_PRETTY_PRINT);
} else {
    $data = json_decode($response, true);
    echo "\n\n--- API Response ---\n";
    echo json_encode($data, JSON_PRETTY_PRINT);
}
?>

