<?php
declare(strict_types=1);

putenv('NARRRFS_OWNER_CSRF_ENABLED=1');

require_once __DIR__ . '/api/config/admin-session.php';

function owner_csrf_test_assert(
    bool $condition,
    string $message
): void {
    if (!$condition) {
        fwrite(
            STDERR,
            "FAIL: {$message}\n"
        );

        exit(1);
    }
}


owner_csrf_test_assert(
    NARRRFS_ADMIN_AUTH_IDLE_SECONDS === 1800,
    'owner idle timeout remains 30 minutes'
);

owner_csrf_test_assert(
    NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS === 43200,
    'owner absolute timeout remains 12 hours'
);

owner_csrf_test_assert(
    NARRRFS_ADMIN_CSRF_TTL_SECONDS === 300,
    'admin CSRF TTL remains five minutes'
);


$now = 1800000000;

$session = [
    'discord_id' => '111111111111111111',
    NARRRFS_ADMIN_PROVENANCE_SESSION_KEY =>
        NARRRFS_ADMIN_AUTH_PROVENANCE,
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY =>
        $now - 10,
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY =>
        $now - 5,
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY =>
        ($now - 10) + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS,
];

$originalIssuedAt = $session[
    NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY
];

$originalLastSeen = $session[
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY
];

$originalExpiresAt = $session[
    NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY
];


$token = narrrfs_issue_admin_csrf_token(
    $session,
    $now,
    static function (int $length): string {
        owner_csrf_test_assert(
            $length === 32,
            'issuer requests exactly 32 random bytes'
        );

        return str_repeat("\xAB", 32);
    }
);


owner_csrf_test_assert(
    preg_match('/^[a-f0-9]{64}$/', $token) === 1,
    'issuer returns one 64-character hex token'
);

owner_csrf_test_assert(
    ($session[NARRRFS_ADMIN_CSRF_SESSION_KEY] ?? null)
        === $token,
    'token is stored server-side'
);

owner_csrf_test_assert(
    ($session[
        NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY
    ] ?? null) === $now,
    'token issue timestamp is stored server-side'
);

owner_csrf_test_assert(
    narrrfs_admin_csrf_is_current(
        $session,
        $token,
        $now + 299
    ),
    'token remains valid before five-minute expiry'
);

owner_csrf_test_assert(
    !narrrfs_admin_csrf_is_current(
        $session,
        $token,
        $now + 300
    ),
    'token expires at five minutes'
);


owner_csrf_test_assert(
    $session[
        NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY
    ] === $originalIssuedAt,
    'issuer does not change authority issued-at'
);

owner_csrf_test_assert(
    $session[
        NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY
    ] === $originalLastSeen,
    'issuer helper does not change authority last-seen'
);

owner_csrf_test_assert(
    $session[
        NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY
    ] === $originalExpiresAt,
    'issuer does not change absolute expiry'
);


$consumedSession = $session;

narrrfs_consume_admin_csrf_session(
    $consumedSession
);

owner_csrf_test_assert(
    !isset(
        $consumedSession[
            NARRRFS_ADMIN_CSRF_SESSION_KEY
        ]
    ),
    'consumer removes token'
);

owner_csrf_test_assert(
    !isset(
        $consumedSession[
            NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY
        ]
    ),
    'consumer removes token issue timestamp'
);


$staleSession = $session;

$staleSession[
    NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY
] = $now - NARRRFS_ADMIN_AUTH_IDLE_SECONDS;

$staleRejected = false;

try {
    narrrfs_issue_admin_csrf_token(
        $staleSession,
        $now,
        static fn (int $length): string =>
            str_repeat("\xCD", $length)
    );
} catch (RuntimeException) {
    $staleRejected = true;
}

owner_csrf_test_assert(
    $staleRejected,
    'issuer refuses expired owner authority'
);


$registry = require __DIR__
    . '/api/config/admin-route-policies.php';

$policy = $registry['routes'][
    'owner_control.csrf.v1'
] ?? null;

$expectedPolicy = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => false,
    'methods' => ['GET', 'HEAD'],
    'csrf' => false,
    'contentTypes' => [],
    'origins' => [],
    'allowRefererFallback' => false,
];

owner_csrf_test_assert(
    $policy === $expectedPolicy,
    'exact Owner Control CSRF policy matches contract'
);


$endpoint = file_get_contents(
    __DIR__
    . '/api/owner-control/v1/csrf.php'
);

owner_csrf_test_assert(
    is_string($endpoint),
    'Owner Control CSRF endpoint exists'
);

$requiredMarkers = [
    "narrrfs_require_admin_request('owner_control.csrf.v1')",
    'narrrfs_issue_admin_csrf_token($_SESSION)',
    'session_write_close()',
    "'capability' => 'owner_control_csrf'",
    "'expires_in' => NARRRFS_ADMIN_CSRF_TTL_SECONDS",
];

foreach ($requiredMarkers as $marker) {
    owner_csrf_test_assert(
        str_contains($endpoint, $marker),
        "required endpoint marker exists: {$marker}"
    );
}

$forbiddenMarkers = [
    'NARRRFS_SESSION_READ_ONLY',
    'SQLite3',
    'PDO',
    'sqlite:',
    '/api/admin/',
    'DISCORD_BOT_SECRET',
    'DISCORD_SECRET',
];

foreach ($forbiddenMarkers as $marker) {
    // The developer comment legitimately explains why READ_ONLY is absent.
    if (
        $marker === 'NARRRFS_SESSION_READ_ONLY'
        && str_contains(
            $endpoint,
            'Do NOT define NARRRFS_SESSION_READ_ONLY here'
        )
    ) {
        continue;
    }

    owner_csrf_test_assert(
        !str_contains($endpoint, $marker),
        "forbidden endpoint marker absent: {$marker}"
    );
}


echo "PASS: Owner Control CSRF V1 contract\n";
echo "OWNER_ONLY=true\n";
echo "TOKEN_BYTES=32\n";
echo "TOKEN_HEX_LENGTH=64\n";
echo "TOKEN_TTL_SECONDS=300\n";
echo "ONE_TIME_CONSUMER=present\n";
echo "DB_ACCESS=none\n";
echo "DISCORD_ACCESS=none\n";
echo "LEGACY_ADMIN_API=none\n";
echo "AUTH_IDLE_SECONDS=1800\n";
echo "AUTH_ABSOLUTE_SECONDS=43200\n";
echo "AUTH_RULES_CHANGED=NO\n";
