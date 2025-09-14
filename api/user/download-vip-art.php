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

// Serve file securely - Environment-aware path
$isProduction = (strpos($_SERVER['HTTP_HOST'], 'narrrfs.world') !== false || strpos($_SERVER['HTTP_HOST'], 'render.com') !== false);
$filename = $isProduction 
  ? '/var/www/html/private/vip_hd_art/original_vip_nft.png'
  : __DIR__ . '/../../private/vip_hd_art/original_vip_nft.png';

// Debug information (remove in production)
if (!$isProduction) {
  error_log("VIP Download Debug - Host: " . $_SERVER['HTTP_HOST'] . ", Path: " . $filename . ", Exists: " . (file_exists($filename) ? 'YES' : 'NO'));
}

if (!file_exists($filename)) {
  http_response_code(404);
  exit("File missing! Call Masterchiefe! Path: " . $filename);
}

header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="Narrrf_VIP_NFT_Original.png"');
header('Content-Length: ' . filesize($filename));
readfile($filename);
exit;
?>
