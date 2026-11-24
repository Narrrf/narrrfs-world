<?php
declare(strict_types=1);

require_once __DIR__ . '/api/config/admin-authorization.php';

function ch_policy_ok(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException('FAIL: ' . $msg);
    }
}

$owner = '111111111111111111';
$now = 2000000000;
$token = str_repeat('b', 64);

$flags = [
    'NARRRFS_OWNER_CHEESE_HUNT_UI_ENABLED',
    'NARRRFS_OWNER_CHEESE_HUNT_READ_ENABLED',
    'NARRRFS_OWNER_CHEESE_HUNT_WRITE_ENABLED',
    'NARRRFS_OWNER_CHEESE_HUNT_CLAIM_REVIEW_ENABLED',
];

foreach ($flags as $flag) {
    putenv($flag);
}
$policies = require __DIR__ . '/api/config/admin-route-policies.php';
foreach (['ui', 'read', 'csrf', 'mission', 'review'] as $kind) {
    ch_policy_ok(!$policies['routes']['owner_control.cheese_hunt.' . $kind . '.v1']['enabled'], 'default off ' . $kind);
}

foreach ($flags as $flag) {
    putenv($flag . '=1');
}
$policies = require __DIR__ . '/api/config/admin-route-policies.php';

$baseSession = [
    'discord_id' => $owner,
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => $now,
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => $now,
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => $now + 43200,
];

// Read & UI routes
foreach (['ui', 'read'] as $kind) {
    $policy = $policies['routes']['owner_control.cheese_hunt.' . $kind . '.v1'];
    ch_policy_ok(narrrfs_admin_authorization_decision($policy, $baseSession, ['method' => 'GET'], [$owner], $now)['allowed'], 'owner GET ' . $kind);
    ch_policy_ok(!narrrfs_admin_authorization_decision($policy, [], ['method' => 'GET'], [$owner], $now)['allowed'], 'anon GET ' . $kind);
    $other = $baseSession;
    $other['discord_id'] = '222222222222222222';
    ch_policy_ok(narrrfs_admin_authorization_decision($policy, $other, ['method' => 'GET'], [$owner], $now)['status'] === 403, 'nonowner GET ' . $kind);
    ch_policy_ok(narrrfs_admin_authorization_decision($policy, $baseSession, ['method' => 'POST'], [$owner], $now)['status'] === 405, 'POST denied ' . $kind);
}

// CSRF issuer
$csrfPolicy = $policies['routes']['owner_control.cheese_hunt.csrf.v1'];
ch_policy_ok(narrrfs_admin_authorization_decision($csrfPolicy, $baseSession, ['method' => 'POST', 'contentType' => 'application/json', 'origin' => 'https://narrrfs.world'], [$owner], $now)['allowed'], 'owner csrf POST');
ch_policy_ok(narrrfs_admin_authorization_decision($csrfPolicy, $baseSession, ['method' => 'GET', 'contentType' => 'application/json', 'origin' => 'https://narrrfs.world'], [$owner], $now)['status'] === 405, 'csrf GET denied');
ch_policy_ok(narrrfs_admin_authorization_decision($csrfPolicy, $baseSession, ['method' => 'POST', 'contentType' => 'application/json', 'origin' => 'https://evil.invalid'], [$owner], $now)['status'] === 403, 'csrf bad origin');

// Mutating routes: mission & review
foreach (['mission', 'review'] as $kind) {
    $key = 'owner_control.cheese_hunt.' . $kind . '.v1';
    $policy = $policies['routes'][$key];
    $session = $baseSession + [
        NARRRFS_ADMIN_CSRF_SESSION_KEY => $token,
        NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY => $now,
        NARRRFS_ADMIN_CSRF_SCOPE_SESSION_KEY => $key,
    ];
    $req = [
        'method' => 'POST',
        'contentType' => 'application/json',
        'origin' => 'https://narrrfs.world',
        'csrfToken' => $token,
    ];

    $decision = narrrfs_admin_authorization_decision($policy, $session, $req, [$owner], $now);
    ch_policy_ok($decision['allowed'] && $decision['consumeCsrf'], 'owner mutating ' . $kind);
    ch_policy_ok(narrrfs_admin_authorization_decision($policy, [], $req, [$owner], $now)['status'] === 401, 'anon ' . $kind);

    $other = $session;
    $other['discord_id'] = '222222222222222222';
    ch_policy_ok(narrrfs_admin_authorization_decision($policy, $other, $req, [$owner], $now)['status'] === 403, 'nonowner ' . $kind);

    foreach ([['method' => 'GET'], ['origin' => 'https://evil.invalid'], ['origin' => ''], ['csrfToken' => 'bad']] as $override) {
        ch_policy_ok(!narrrfs_admin_authorization_decision($policy, $session, array_replace($req, $override), [$owner], $now)['allowed'], 'bad req ' . $kind);
    }

    $wrongScope = $session;
    $wrongScope[NARRRFS_ADMIN_CSRF_SCOPE_SESSION_KEY] = $kind === 'mission'
        ? 'owner_control.cheese_hunt.review.v1'
        : 'owner_control.cheese_hunt.mission.v1';
    ch_policy_ok(narrrfs_admin_authorization_decision($policy, $wrongScope, $req, [$owner], $now)['status'] === 403, 'cross-scope rejected ' . $kind);

    narrrfs_consume_admin_csrf($session, true);
    ch_policy_ok(narrrfs_admin_authorization_decision($policy, $session, $req, [$owner], $now)['status'] === 403, 'replay rejected ' . $kind);
}

echo "PASS: Cheese Hunt policies default-off and owner/method/origin/scope/CSRF boundaries\n";
