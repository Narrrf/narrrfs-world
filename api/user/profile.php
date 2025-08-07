<?php
session_start();
header('Content-Type: application/json');

// Only session user can fetch profile!
$user_id = $_SESSION['discord_id'] ?? '';
if (!$user_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$db = new SQLite3(__DIR__ . '/../../db/narrrf_world.sqlite');

// 1. Basic info
$stmt = $db->prepare("SELECT username, avatar_url, created_at FROM tbl_users WHERE discord_id = ?");
$stmt->bindValue(1, $user_id, SQLITE3_TEXT);
$userRow = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

// 2. Discord join date = member since date
$member_since = isset($userRow['created_at']) && $userRow['created_at'] ? substr($userRow['created_at'], 0, 10) : "";

// 3. Roles
$roles = [];
$roleStmt = $db->prepare("SELECT role_name FROM tbl_user_roles WHERE user_id = ?");
$roleStmt->bindValue(1, $user_id, SQLITE3_TEXT);
$res = $roleStmt->execute();
while ($row = $res->fetchArray(SQLITE3_ASSOC)) $roles[] = $row['role_name'];

// 4. Traits
$traits = [];
$traitStmt = $db->prepare("SELECT trait FROM tbl_user_traits WHERE user_id = ?");
$traitStmt->bindValue(1, $user_id, SQLITE3_TEXT);
$tr = $traitStmt->execute();
while ($row = $tr->fetchArray(SQLITE3_ASSOC)) $traits[] = $row['trait'];

// 5. Stats
$adjStmt = $db->prepare("SELECT COUNT(*) FROM tbl_score_adjustments WHERE user_id = ?");
$adjStmt->bindValue(1, $user_id, SQLITE3_TEXT);
$adj = $adjStmt->execute()->fetchArray(SQLITE3_NUM)[0];

$srcStmt = $db->prepare("SELECT COUNT(DISTINCT source) FROM tbl_user_scores WHERE user_id = ?");
$srcStmt->bindValue(1, $user_id, SQLITE3_TEXT);
$sources = $srcStmt->execute()->fetchArray(SQLITE3_NUM)[0];

// 6. Calculate total DSPOINC from tbl_user_scores
$dspoincStmt = $db->prepare("SELECT SUM(score) FROM tbl_user_scores WHERE user_id = ?");
$dspoincStmt->bindValue(1, $user_id, SQLITE3_TEXT);
$dspoincResult = $dspoincStmt->execute()->fetchArray(SQLITE3_NUM);
$total_dspoinc = $dspoincResult[0] ?? 0;

// NOW close the DB!
$db->close();

// Output JSON
echo json_encode([
  'discord_id'   => $user_id,
  'discord_name' => $userRow['username'] ?? 'Unknown',
  'avatar_url'   => $userRow['avatar_url'] ?? '',
  'member_since' => $member_since,
  'roles'        => $roles,
  'traits'       => $traits,
  'total_dspoinc' => (int)$total_dspoinc,
  'stats'        => [
    'scoreAdjustments' => (int)$adj,
    'sources'          => (int)$sources,
    'roles'            => count($roles),
  ]
]);
?>
