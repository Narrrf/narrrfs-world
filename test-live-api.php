<?php
// Test the live API directly
header('Content-Type: text/plain');

echo "🧪 Testing Live API Response...\n\n";

$discordId = "328601656659017732"; // narrrf's Discord ID
$apiUrl = "https://narrrfs.world/api/user-game-missions.php";

echo "Testing Discord ID: $discordId\n";
echo "API URL: $apiUrl\n\n";

// Test with POST request
$postData = json_encode(['user_id' => $discordId]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Sending POST request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n";

if (curl_error($ch)) {
    echo "CURL Error: " . curl_error($ch) . "\n";
}

curl_close($ch);

echo "\n🧪 Test complete!\n";
?>
