<?php
declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-authorization.php';

function quest_hunt_overview_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$policyPath = __DIR__ . '/api/config/admin-route-policies.php';
$gate = 'NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_READ_ENABLED';
$priorGate = getenv($gate);
putenv($gate . '=1');
$policy = (require $policyPath)['routes']['owner_control.quest_hunt_overview.read.v1'] ?? null;
quest_hunt_overview_test_assert(is_array($policy), 'overview read policy exists');
quest_hunt_overview_test_assert(narrrfs_validate_admin_route_policy($policy) !== null, 'overview read policy is valid');
quest_hunt_overview_test_assert($policy['methods'] === ['GET', 'HEAD'], 'policy permits exactly GET and HEAD');
quest_hunt_overview_test_assert($policy['mutating'] === false && $policy['csrf'] === false, 'policy remains non-mutating without CSRF');

$now = 2000000000;
$owner = str_repeat('1', 17);
$session = [
    'discord_id' => $owner,
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => $now,
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => $now,
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => $now + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS,
];
foreach (['GET', 'HEAD'] as $method) {
    $decision = narrrfs_admin_authorization_decision($policy, $session, ['method' => $method], [$owner], $now);
    quest_hunt_overview_test_assert($decision['allowed'] === true, $method . ' permits trusted owner');
}
foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
    $decision = narrrfs_admin_authorization_decision($policy, $session, ['method' => $method], [$owner], $now);
    quest_hunt_overview_test_assert($decision['allowed'] === false && $decision['status'] === 405, $method . ' is denied before route dispatch');
}
quest_hunt_overview_test_assert(narrrfs_admin_authorization_decision($policy, [], ['method' => 'GET'], [$owner], $now)['status'] === 401, 'unauthenticated request is denied before route dispatch');
$normalSession = $session;
$normalSession['discord_id'] = str_repeat('2', 17);
quest_hunt_overview_test_assert(narrrfs_admin_authorization_decision($policy, $normalSession, ['method' => 'GET'], [$owner], $now)['status'] === 403, 'normal user is denied before route dispatch');
putenv($gate);
$disabledPolicy = (require $policyPath)['routes']['owner_control.quest_hunt_overview.read.v1'] ?? null;
quest_hunt_overview_test_assert(narrrfs_admin_authorization_decision($disabledPolicy, $session, ['method' => 'GET'], [$owner], $now)['status'] === 404, 'disabled policy denies before route dispatch');
$priorGate === false ? putenv($gate) : putenv($gate . '=' . $priorGate);

define('NARRRFS_QUEST_HUNT_OVERVIEW_LIBRARY_ONLY', true);
require __DIR__ . '/api/owner-control/v1/quests/overview.php';
$payload = quest_hunt_overview_payload([
    'activeCount' => 2,
    'activeTypeCounts' => [['type' => 'cheese_hunt', 'count' => 2]],
    'claimStatusCounts' => ['pending' => 1, 'approved' => 3, 'rejected' => 0],
    'aggregateClickCount' => 9,
    'recentWindowClickCount' => 4,
]);
quest_hunt_overview_test_assert(array_keys($payload) === ['success', 'capability', 'version', 'mode', 'writes', 'quests', 'claims', 'hunt'], 'response top-level field allowlist is exact');
quest_hunt_overview_test_assert(array_keys($payload['quests']) === ['active_count', 'active_type_counts'], 'quest field allowlist is exact');
quest_hunt_overview_test_assert(array_keys($payload['claims']['status_counts']) === ['pending', 'approved', 'rejected'], 'claim status field allowlist is exact');
quest_hunt_overview_test_assert(array_keys($payload['hunt']) === ['aggregate_click_count', 'recent_window_click_count'], 'hunt field allowlist is exact');

$route = file_get_contents(__DIR__ . '/api/owner-control/v1/quests/overview.php');
$containment = file_get_contents(__DIR__ . '/deploy/apache/narrrfs-emergency-containment.conf');
quest_hunt_overview_test_assert(is_string($route) && is_string($containment), 'route and containment sources are readable');
foreach (["narrrfs_require_admin_request('owner_control.quest_hunt_overview.read.v1')", 'SQLITE3_OPEN_READONLY', 'PRAGMA query_only = ON', 'Cache-Control: no-store', 'LIMIT 16', "'writes' => false"] as $required) {
    quest_hunt_overview_test_assert(str_contains($route, $required), 'route contains required read-only boundary: ' . $required);
}
foreach (['SELECT *', 'tbl_users', 'tbl_user_scores', 'tbl_score_adjustments', 'tbl_discord_claim_notifications', 'INSERT ', 'UPDATE ', 'DELETE ', 'REPLACE ', 'BEGIN', 'COMMIT', 'ROLLBACK', 'CREATE ', 'ALTER ', 'DROP ', 'ATTACH ', 'VACUUM'] as $forbidden) {
    quest_hunt_overview_test_assert(!str_contains($route, $forbidden), 'route excludes protected/write SQL: ' . $forbidden);
}
quest_hunt_overview_test_assert(str_contains($containment, '/api/owner-control/v1/quests/overview\\.php'), 'containment exception names only the exact overview path');
echo "PASS: owner-control Quest/Hunt overview read contract V1\n";
