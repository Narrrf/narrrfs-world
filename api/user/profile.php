<?php
session_start();
header('Content-Type: application/json');
$user_id = $_SESSION['discord_id'] ?? $_GET['user_id'] ?? '';
if (!$user_id) { echo json_encode(['error'=>'Not logged in']); exit; }

$db = new SQLite3(__DIR__ . '/../../db/narrrf_world.sqlite');
// 1. Basic info
$userRow = $db->querySingle("SELECT username, MIN(timestamp) AS joined FROM tbl_users WHERE discord_id = '$user_id'", true);
// 2. Roles
$roles = [];
$res = $db->query("SELECT role_name FROM tbl_user_roles WHERE user_id = '$user_id'");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $roles[] = $row['role_name'];
// 3. Traits (if you have them)
$traits = [];
$tr = $db->query("SELECT trait FROM tbl_user_traits WHERE user_id = '$user_id'");
while ($row = $tr->fetchArray(SQLITE3_ASSOC)) $traits[] = $row['trait'];
// 4. Stats
$adj = $db->querySingle("SELECT COUNT(*) FROM tbl_score_adjustments WHERE user_id = '$user_id'");
$sources = $db->querySingle("SELECT COUNT(DISTINCT source) FROM tbl_user_scores WHERE user_id = '$user_id'");
$db->close();

echo json_encode([
  'discord_name' => $userRow['username'] ?? 'Unknown',
  'member_since' => substr($userRow['joined'] ?? '', 0, 10),
  'roles' => $roles,
  'traits' => $traits,
  'stats' => [
    'scoreAdjustments' => $adj,
    'sources' => $sources,
    'roles' => count($roles),
  ]
]);
?>
