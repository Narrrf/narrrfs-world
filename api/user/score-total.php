<?php
// Use a safe relative path so it works both locally and on Render!
$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
$db = new PDO('sqlite:' . $dbPath);

// Get user_id (Discord ID) from GET param
$user_id = $_GET['user_id'] ?? '';
if (!$user_id) {
  echo json_encode(['error' => 'Missing user_id']);
  exit;
}

// Get total DSPOINC for this user
$stmt = $db->prepare("SELECT SUM(score) AS total_dspoinc FROM tbl_user_scores WHERE user_id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

// Default to 0 if not found
$total_dspoinc = (int)($row['total_dspoinc'] ?? 0);

// Respond with DSPOINC (main score) and SPOINC equivalent
echo json_encode([
  'total_dspoinc' => $total_dspoinc,
  'total_spoinc' => floor($total_dspoinc / 10000)
]);
?>
