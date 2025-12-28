<?php
session_start();
header('Content-Type: application/json');

// Local development fallback
$isLocalDevelopment = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                      strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
$LOCAL_TEST_DISCORD_ID = '328601656659017732'; // Narrrf's Discord ID for local testing

// Get user_id from session, POST, GET, or JSON body
$user_id = $_SESSION['discord_id'] ?? '';

// Check if user_id is provided in POST/GET (works for both local and production)
$request_user_id = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try POST data first
    $request_user_id = $_POST['user_id'] ?? '';
    // If not in POST, try JSON body
    if (!$request_user_id) {
        $json_input = json_decode(file_get_contents('php://input'), true);
        $request_user_id = $json_input['user_id'] ?? '';
    }
} else {
    // GET request
    $request_user_id = $_GET['user_id'] ?? '';
}

// Use request user_id if provided (takes priority over session)
if ($request_user_id) {
    $user_id = $request_user_id;
}

// For local development, use Narrrf's account if no session exists
if (!$user_id && $isLocalDevelopment) {
    $user_id = $LOCAL_TEST_DISCORD_ID;
}

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

// 7. Get staking stats (frozen balance, available balance)
// Check if staking table exists first (for backward compatibility)
$tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tbl_dspoinc_stakes'")->fetchArray();
if ($tableCheck) {
    $frozenStmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) AS frozen_balance,
               COUNT(*) AS active_stakes_count
        FROM tbl_dspoinc_stakes 
        WHERE user_id = ? AND status = 'active'
    ");
    $frozenStmt->bindValue(1, $user_id, SQLITE3_TEXT);
    $frozenResult = $frozenStmt->execute()->fetchArray(SQLITE3_ASSOC);
    $frozen_balance = (int)($frozenResult['frozen_balance'] ?? 0);
    $active_stakes_count = (int)($frozenResult['active_stakes_count'] ?? 0);
} else {
    // Table doesn't exist yet (backward compatibility)
    $frozen_balance = 0;
    $active_stakes_count = 0;
}
$available_balance = $total_dspoinc - $frozen_balance;

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
  'available_dspoinc' => (int)$available_balance,
  'frozen_dspoinc' => (int)$frozen_balance,
  'active_stakes_count' => $active_stakes_count,
  'stats'        => [
    'scoreAdjustments' => (int)$adj,
    'sources'          => (int)$sources,
    'roles'            => count($roles),
  ]
]);
?>
