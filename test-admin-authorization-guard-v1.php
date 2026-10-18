<?php
declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-authorization.php';

function guard_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

function guard_test_decision(array $overrides = []): array
{
    $owner = '111111111111111111';
    $csrf = str_repeat('a', 64);
    $now = 2000000000;
    $policy = [
        'enabled' => true,
        'ownerOnly' => true,
        'mutating' => true,
        'methods' => ['POST'],
        'csrf' => true,
        'contentTypes' => ['application/json'],
        'origins' => ['https://example.invalid'],
        'allowRefererFallback' => false,
    ];
    $session = [
        'discord_id' => $owner,
        NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
        NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => $now,
        NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => $now,
        NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => $now + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS,
        NARRRFS_ADMIN_CSRF_SESSION_KEY => $csrf,
        NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY => $now,
    ];
    $request = [
        'method' => 'POST',
        'contentType' => 'application/json; charset=utf-8',
        'origin' => 'https://example.invalid',
        'referer' => null,
        'csrfToken' => $csrf,
    ];
    $ownerIds = [$owner];

    return narrrfs_admin_authorization_decision(
        array_key_exists('policy', $overrides) ? $overrides['policy'] : $policy,
        array_key_exists('session', $overrides) ? $overrides['session'] : $session,
        array_key_exists('request', $overrides) ? $overrides['request'] : $request,
        array_key_exists('ownerIds', $overrides) ? $overrides['ownerIds'] : $ownerIds,
        array_key_exists('now', $overrides) ? $overrides['now'] : $now
    );
}

function guard_test_denied(array $decision, int $status, string $label): void
{
    guard_test_assert($decision['allowed'] === false, $label . ' is denied');
    guard_test_assert($decision['status'] === $status, $label . ' status');
}

$valid = guard_test_decision();
guard_test_assert($valid['allowed'] === true, 'exact synthetic owner request is allowed by pure policy');
guard_test_assert($valid['consumeCsrf'] === true, 'mutating request consumes one-time CSRF token');

guard_test_denied(guard_test_decision(['ownerIds' => []]), 503, 'missing owner allowlist');
guard_test_assert(narrrfs_parse_admin_owner_ids('bad-value') === [], 'malformed owner allowlist fails closed');
guard_test_assert(narrrfs_parse_admin_owner_ids('111111111111111111,bad') === [], 'partly malformed allowlist fails closed');
guard_test_denied(guard_test_decision(['policy' => null]), 404, 'missing policy');
guard_test_denied(guard_test_decision(['policy' => ['enabled' => true]]), 404, 'malformed policy');

$noProvenance = [
    'discord_id' => '111111111111111111',
    NARRRFS_ADMIN_CSRF_SESSION_KEY => str_repeat('a', 64),
];
guard_test_denied(guard_test_decision(['session' => $noProvenance]), 401, 'absent session provenance');
$wrongProvenance = $noProvenance;
$wrongProvenance[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY] = 'legacy_admin_session';
guard_test_denied(guard_test_decision(['session' => $wrongProvenance]), 401, 'wrong session provenance');

$normalUser = [
    'discord_id' => '222222222222222222',
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => 2000000000,
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => 2000000000,
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => 2000043200,
    NARRRFS_ADMIN_CSRF_SESSION_KEY => str_repeat('a', 64),
    NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY => 2000000000,
    'admin_logged_in' => true,
    'admin_role' => 'owner',
];
guard_test_denied(guard_test_decision(['session' => $normalUser]), 403, 'normal user with legacy admin flags');

$serverIdentityMismatch = $normalUser;
$serverIdentityMismatch['client_claimed_owner'] = '111111111111111111';
guard_test_denied(guard_test_decision(['session' => $serverIdentityMismatch]), 403, 'server-derived non-owner identity defeats client owner mismatch');

$expiredOwner = guard_test_decision(['now' => 2000001800]);
guard_test_denied($expiredOwner, 401, 'idle-expired owner authority');
$malformedTimestamp = [
    'discord_id' => '111111111111111111',
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => '2000000000',
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => 2000000000,
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => 2000043200,
];
guard_test_denied(guard_test_decision(['session' => $malformedTimestamp]), 401, 'non-integer owner timestamp');

$spoofChannels = [
    'queryOwner' => '111111111111111111',
    'bodyOwner' => '111111111111111111',
    'headerOwner' => '111111111111111111',
    'cookieOwner' => '111111111111111111',
    'displayName' => 'owner',
    'localStorageOwner' => '111111111111111111',
];
$spoofRequest = [
    'method' => 'POST',
    'contentType' => 'application/json',
    'origin' => 'https://example.invalid',
    'csrfToken' => str_repeat('a', 64),
] + $spoofChannels;
guard_test_denied(
    guard_test_decision(['session' => $normalUser, 'request' => $spoofRequest]),
    403,
    'client-controlled identity channels'
);

$wrongMethod = guard_test_decision(['request' => [
    'method' => 'GET',
    'contentType' => 'application/json',
    'origin' => 'https://example.invalid',
    'csrfToken' => str_repeat('a', 64),
]]);
guard_test_denied($wrongMethod, 405, 'wrong method');
guard_test_assert($wrongMethod['allow'] === ['POST'], 'wrong method returns exact Allow set');

$tracePolicy = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => true,
    'methods' => ['TRACE'],
    'csrf' => true,
    'contentTypes' => ['application/json'],
    'origins' => ['https://example.invalid'],
    'allowRefererFallback' => false,
];
guard_test_denied(guard_test_decision(['policy' => $tracePolicy]), 404, 'unapproved mutation method policy');

guard_test_denied(guard_test_decision(['request' => [
    'method' => 'POST',
    'contentType' => 'text/plain',
    'origin' => 'https://example.invalid',
    'csrfToken' => str_repeat('a', 64),
]]), 415, 'disallowed content type');

foreach ([null, '', 'https://evil.invalid'] as $origin) {
    guard_test_denied(guard_test_decision(['request' => [
        'method' => 'POST',
        'contentType' => 'application/json',
        'origin' => $origin,
        'referer' => 'https://example.invalid/path',
        'csrfToken' => str_repeat('a', 64),
        'host' => 'localhost',
    ]]), 403, 'origin rejection');
}

foreach ([null, '', 'wrong', str_repeat('b', 64)] as $csrfToken) {
    guard_test_denied(guard_test_decision(['request' => [
        'method' => 'POST',
        'contentType' => 'application/json',
        'origin' => 'https://example.invalid',
        'csrfToken' => $csrfToken,
    ]]), 403, 'CSRF rejection');
}

$refererPolicy = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => true,
    'methods' => ['POST'],
    'csrf' => true,
    'contentTypes' => ['application/json'],
    'origins' => ['https://example.invalid'],
    'allowRefererFallback' => true,
];
$refererAllowed = guard_test_decision(['policy' => $refererPolicy, 'request' => [
    'method' => 'POST',
    'contentType' => 'application/json',
    'origin' => null,
    'referer' => 'https://example.invalid/admin/path',
    'csrfToken' => str_repeat('a', 64),
]]);
guard_test_assert($refererAllowed['allowed'] === true, 'explicit Referer fallback uses exact allowed origin');

$malformedOriginRejected = guard_test_decision(['policy' => $refererPolicy, 'request' => [
    'method' => 'POST',
    'contentType' => 'application/json',
    'origin' => 'not-an-origin',
    'referer' => 'https://example.invalid/admin/path',
    'csrfToken' => str_repeat('a', 64),
]]);
guard_test_denied($malformedOriginRejected, 403, 'malformed present Origin cannot use Referer fallback');

$optionsDecision = guard_test_decision(['request' => [
    'method' => 'OPTIONS',
    'contentType' => 'application/json',
    'origin' => 'https://example.invalid',
    'csrfToken' => str_repeat('a', 64),
]]);
guard_test_denied($optionsDecision, 405, 'OPTIONS has no authority');
guard_test_assert($optionsDecision['consumeCsrf'] === false, 'denial does not consume or mutate CSRF state');

$replaySession = [
    'discord_id' => '111111111111111111',
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => 2000000000,
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => 2000000000,
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => 2000043200,
    NARRRFS_ADMIN_CSRF_SESSION_KEY => str_repeat('a', 64),
    NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY => 2000000000,
];
narrrfs_consume_admin_csrf($replaySession, true);
guard_test_assert(!array_key_exists(NARRRFS_ADMIN_CSRF_SESSION_KEY, $replaySession), 'successful mutation consumes CSRF token');
guard_test_assert(!array_key_exists(NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY, $replaySession), 'successful mutation consumes CSRF issue time');
guard_test_denied(guard_test_decision(['session' => $replaySession]), 403, 'consumed CSRF token cannot be replayed');

$readPolicy = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => false,
    'methods' => ['GET'],
    'csrf' => false,
    'contentTypes' => [],
    'origins' => [],
    'allowRefererFallback' => false,
];
$serviceOnlyRequest = ['method' => 'GET', 'authorization' => 'Bearer service-token-placeholder'];
guard_test_denied(
    guard_test_decision(['policy' => $readPolicy, 'session' => [], 'request' => $serviceOnlyRequest]),
    401,
    'service bearer is not browser owner authority'
);

guard_test_assert(narrrfs_load_admin_route_policies(__DIR__ . '/missing-admin-policies.php') === [], 'missing policy include fails closed');

$guardSource = file_get_contents(__DIR__ . '/api/config/admin-authorization.php');
guard_test_assert(is_string($guardSource), 'guard source is readable');
guard_test_assert(str_contains($guardSource, "catch (Throwable)"), 'internal exceptions fail closed');
guard_test_assert(str_contains($guardSource, "Cache-Control: no-store"), 'failures are no-store');
guard_test_assert(!str_contains($guardSource, 'HTTP_HOST'), 'guard has no Host or localhost bypass');

echo "PASS: admin authorization guard Wave A\n";
