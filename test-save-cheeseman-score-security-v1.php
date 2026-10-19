<?php
declare(strict_types=1);

function cheeseman_test_assert(
    bool $condition,
    string $message
): void {
    if (!$condition) {
        throw new RuntimeException(
            'FAIL: ' . $message
        );
    }
}

$path = __DIR__
    . '/api/dev/save-cheeseman-score.php';

$source = file_get_contents($path);

cheeseman_test_assert(
    is_string($source),
    'Cheese Runner API source is readable'
);

cheeseman_test_assert(
    str_contains(
        $source,
        "require_once __DIR__ . '/../config/session.php'"
    ),
    'shared session bootstrap is required'
);

cheeseman_test_assert(
    !str_contains(
        $source,
        'session_start()'
    ),
    'route does not bypass shared session bootstrap'
);

cheeseman_test_assert(
    !str_contains(
        $source,
        'LOCAL_TEST_DISCORD_ID'
    ) &&
    !str_contains(
        $source,
        'is_localhost_env'
    ),
    'request Host cannot activate an authentication bypass'
);

cheeseman_test_assert(
    str_contains(
        $source,
        'hash_equals('
    ) &&
    str_contains(
        $source,
        '$sessionDiscordId'
    ) &&
    str_contains(
        $source,
        '$requestDiscordId'
    ),
    'request identity is bound to authenticated session'
);

cheeseman_test_assert(
    str_contains(
        $source,
        'origin_rejected'
    ) &&
    str_contains(
        $source,
        'cross_site_rejected'
    ),
    'Origin and Fetch-Site boundaries are explicit'
);

cheeseman_test_assert(
    str_contains(
        $source,
        "CHEESEMAN_CONVERSION_RATE = 25"
    ),
    'production Cheese Runner conversion remains /25'
);

cheeseman_test_assert(
    preg_match(
        '/SELECT\s+role_name\s+FROM\s+tbl_user_roles\s+WHERE\s+user_id\s*=\s*\?/s',
        $source
    ) === 1,
    'role multiplier comes from server-owned roles'
);

cheeseman_test_assert(
    !str_contains(
        $source,
        "\$request['dspoinc_score']"
    ),
    'browser cannot choose final DSPOINC reward'
);

cheeseman_test_assert(
    !str_contains(
        $source,
        "\$request['role_multiplier']"
    ),
    'browser cannot choose role multiplier'
);

cheeseman_test_assert(
    !str_contains(
        $source,
        "\$request['discord_name']"
    ),
    'browser display name is not persistence authority'
);

cheeseman_test_assert(
    preg_match(
        '/FROM\s+tbl_seasons\s+WHERE\s+is_active\s*=\s*1.*LIMIT\s+2/s',
        $source
    ) === 1,
    'active season lookup fails closed on ambiguity'
);

cheeseman_test_assert(
    !str_contains(
        $source,
        "'Season 12'"
    ) &&
    !str_contains(
        $source,
        "'season_2'"
    ),
    'route has no historical season fallback'
);

preg_match_all(
    '/INSERT\s+INTO\s+([a-zA-Z0-9_]+)/i',
    $source,
    $matches
);

$targets = array_values(
    array_unique(
        array_map(
            'strtolower',
            $matches[1] ?? []
        )
    )
);

sort($targets);

$expectedTargets = [
    'tbl_score_adjustments',
    'tbl_tetris_scores',
    'tbl_user_scores',
];

sort($expectedTargets);

cheeseman_test_assert(
    $targets === $expectedTargets,
    'only approved Cheese Runner persistence targets are written'
);

cheeseman_test_assert(
    preg_match(
        '/\b(?:UPDATE|DELETE|REPLACE)\b\s+/i',
        $source
    ) !== 1,
    'route is append-only'
);

cheeseman_test_assert(
    preg_match(
        '/\b(?:CREATE|ALTER|DROP)\s+(?:TABLE|INDEX)/i',
        $source
    ) !== 1,
    'route performs no schema mutation'
);

cheeseman_test_assert(
    str_contains(
        $source,
        'beginTransaction()'
    ) &&
    str_contains(
        $source,
        'commit()'
    ) &&
    str_contains(
        $source,
        'rollBack()'
    ),
    'all writes use transactional safety'
);

cheeseman_test_assert(
    str_contains(
        $source,
        'CHEESEMAN_REPLAY_WINDOW_SECONDS = 30'
    ) &&
    str_contains(
        $source,
        'duplicate_reward_state_unverified'
    ),
    'duplicate/replay reward state fails closed'
);

cheeseman_test_assert(
    str_contains(
        $source,
        "'game_reward'"
    ),
    'ledger source remains game_reward'
);

cheeseman_test_assert(
    str_contains(
        $source,
        '$season,'
    ),
    'server-selected active season is explicitly written'
);

echo "PASS: Cheese Runner score route is session-bound, server-priced, replay-guarded, and atomically audited\n";
