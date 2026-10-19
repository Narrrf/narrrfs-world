<?php
declare(strict_types=1);

function labyrinth_test_assert(
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
    . '/api/dev/save-labyrinth-blast-score.php';

$source = file_get_contents($path);

labyrinth_test_assert(
    is_string($source),
    'Labyrinth Blast source is readable'
);

labyrinth_test_assert(
    str_contains(
        $source,
        "require_once __DIR__ . '/../config/session.php'"
    ),
    'shared session bootstrap is required'
);

labyrinth_test_assert(
    !str_contains(
        $source,
        'session_start()'
    ),
    'route does not bypass shared session bootstrap'
);

labyrinth_test_assert(
    !str_contains(
        $source,
        'LOCAL_TEST_DISCORD_ID'
    ) &&
    !str_contains(
        $source,
        'LOCAL_TEST_ROLE_MULTIPLIER'
    ) &&
    !str_contains(
        $source,
        'is_localhost_env'
    ),
    'request Host cannot activate authentication or economy fallbacks'
);

labyrinth_test_assert(
    str_contains(
        $source,
        'hash_equals('
    ),
    'request identity is bound to authenticated session'
);

labyrinth_test_assert(
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

labyrinth_test_assert(
    str_contains(
        $source,
        'LABYRINTH_CONVERSION_RATE = 10'
    ),
    'Labyrinth conversion remains /10'
);

labyrinth_test_assert(
    str_contains(
        $source,
        'LABYRINTH_MAX_DSP_REWARD = 5000'
    ),
    'Labyrinth maximum reward remains 5000'
);

labyrinth_test_assert(
    preg_match(
        '/SELECT\s+role_name\s+FROM\s+tbl_user_roles\s+WHERE\s+user_id\s*=\s*\?/s',
        $source
    ) === 1,
    'role multiplier comes from server-owned roles'
);

labyrinth_test_assert(
    !str_contains(
        $source,
        "\$request['dspoinc_score']"
    ),
    'browser cannot choose final DSPOINC reward'
);

labyrinth_test_assert(
    !str_contains(
        $source,
        "\$request['role_multiplier']"
    ),
    'browser cannot choose role multiplier'
);

labyrinth_test_assert(
    !str_contains(
        $source,
        "\$request['discord_name']"
    ),
    'browser display name is not persistence authority'
);

labyrinth_test_assert(
    preg_match(
        '/FROM\s+tbl_seasons\s+WHERE\s+is_active\s*=\s*1.*LIMIT\s+2/s',
        $source
    ) === 1,
    'active season lookup fails closed on ambiguity'
);

labyrinth_test_assert(
    !str_contains(
        $source,
        "'Season 12'"
    ) &&
    !str_contains(
        $source,
        "'Season 5'"
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

labyrinth_test_assert(
    $targets === $expectedTargets,
    'only approved Labyrinth persistence targets are written'
);

labyrinth_test_assert(
    preg_match(
        '/\b(?:UPDATE|DELETE|REPLACE)\b\s+/i',
        $source
    ) !== 1,
    'route is append-only'
);

labyrinth_test_assert(
    preg_match(
        '/\b(?:CREATE|ALTER|DROP)\s+(?:TABLE|INDEX)/i',
        $source
    ) !== 1,
    'route performs no schema mutation'
);

labyrinth_test_assert(
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

labyrinth_test_assert(
    str_contains(
        $source,
        'LABYRINTH_REPLAY_WINDOW_SECONDS = 30'
    ) &&
    str_contains(
        $source,
        'duplicate_reward_state_unverified'
    ),
    'duplicate/replay reward state fails closed'
);

labyrinth_test_assert(
    str_contains(
        $source,
        "'game_reward'"
    ),
    'ledger source remains game_reward'
);

labyrinth_test_assert(
    str_contains(
        $source,
        "'won', 'lost'"
    ),
    'existing won/lost backend status contract remains available'
);

echo "PASS: Labyrinth Blast route is session-bound, server-priced, replay-guarded, and atomically audited\n";
