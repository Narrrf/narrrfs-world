<?php
session_start();
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);

// Ensure user logged in
if (!isset($_SESSION['discord_id'])) {
  http_response_code(403);
  exit("Not logged in. No cheese for you!");
}
$user_id = $_SESSION['discord_id'];

// Check VIP status
$stmt = $db->prepare("SELECT 1 FROM tbl_user_roles WHERE user_id = ? AND (role_name = 'VIP Holder' OR role_name = 'VIP_pass') LIMIT 1");
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
  http_response_code(403);
  exit("Sorry, only VIPs get this legendary cheese art.");
}

// Serve file securely
$filename = '/var/www/html/private/vip_hd_art/original_vip_nft.png'; // Update path if needed
if (!file_exists($filename)) {
  http_response_code(404);
  exit("File missing! Call Masterchiefe!");
}

header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="Narrrf_VIP_NFT_Original.png"');
header('Content-Length: ' . filesize($filename));
readfile($filename);
exit;
?>
