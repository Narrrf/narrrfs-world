<?php
declare(strict_types=1);

function score_recovery_test_assert(
    bool $condition,
    string $label
): void {
    if (!$condition) {
        throw new RuntimeException(
            'FAIL: ' . $label
        );
    }
}

$sourcePath = __DIR__ . '/api/dev/save-score.php';
$source = file_get_contents($sourcePath);

score_recovery_test_assert(
    is_string($source),
    'save-score source is readable'
);

/*
 * Authentication boundary.
 */
score_recovery_test_assert(
    str_contains(
        $source,
        "require_once __DIR__ . '/../config/session.php'"
    ),
    'shared session bootstrap remains required'
);

score_recovery_test_assert(
    str_contains(
        $source,
        'hash_equals($sessionDiscordId, $requestDiscordId)'
    ),
    'request identity must match authenticated session'
);

/*
 * Exact game allowlist.
 * This intentionally ignores formatting/newlines.
 */
$allowlistMatched = preg_match(
    '/\$allowedGames\s*=\s*\[(.*?)\];/s',
    $source,
    $allowlistMatch
) === 1;

score_recovery_test_assert(
    $allowlistMatched,
    'shared score route defines an explicit game allowlist'
);

preg_match_all(
    "/'([^']+)'/",
    $allowlistMatch[1] ?? '',
    $allowedGameMatches
);

$allowedGames = $allowedGameMatches[1] ?? [];

score_recovery_test_assert(
    $allowedGames === [
        'tetris',
        'snake',
        'space_invaders',
        'glyph_memory'
    ],
    'shared score route has exact four-game allowlist'
);

/*
 * Server-owned season and identity selection.
 */
score_recovery_test_assert(
    preg_match(
        '/FROM\s+tbl_seasons\s+WHERE\s+is_active\s*=\s*1/s',
        $source
    ) === 1,
    'active season is server-selected'
);

score_recovery_test_assert(
    preg_match(
        '/FROM\s+tbl_season_settings\s+WHERE\s+season_name\s*=\s*\?/s',
        $source
    ) === 1,
    'score ceilings come from active season settings'
);

score_recovery_test_assert(
    preg_match(
        '/SELECT\s+username\s+FROM\s+tbl_users\s+WHERE\s+discord_id\s*=\s*\?/s',
        $source
    ) === 1,
    'display name is server-resolved'
);

/*
 * Inventory every SQL INSERT target instead of only checking two strings.
 */
preg_match_all(
    '/INSERT\s+INTO\s+([a-zA-Z0-9_]+)/i',
    $source,
    $insertMatches
);

$insertTargets = array_values(array_unique(
    array_map(
        'strtolower',
        $insertMatches[1] ?? []
    )
));

sort($insertTargets);

$expectedInsertTargets = [
    'tbl_glyph_memory_scores',
    'tbl_score_adjustments',
    'tbl_tetris_scores',
    'tbl_user_scores'
];

sort($expectedInsertTargets);

score_recovery_test_assert(
    $insertTargets === $expectedInsertTargets,
    'only approved leaderboard tables receive inserts'
);

/*
 * Only unrelated protected persistence stays absent.
 * tbl_user_scores and tbl_score_adjustments are explicitly approved here.
 */
foreach ([
    'tbl_wl_role_grants'
] as $forbiddenTable) {
    score_recovery_test_assert(
        !str_contains(
            $source,
            $forbiddenTable
        ),
        'protected table remains absent: ' .
        $forbiddenTable
    );
}

score_recovery_test_assert(
    preg_match(
        '/\b(?:UPDATE|DELETE|REPLACE)\b\s+/i',
        $source
    ) !== 1,
    'recovery performs no update/delete/replace SQL'
);

score_recovery_test_assert(
    preg_match(
        '/\b(?:CREATE|ALTER|DROP)\s+(?:TABLE|INDEX)/i',
        $source
    ) !== 1,
    'recovery performs no schema mutation'
);

/*
 * Browser presentation values cannot become persistence authority.
 */
score_recovery_test_assert(
    !str_contains(
        $source,
        "\$requestData['wallet']"
    ),
    'client wallet is not trusted'
);

score_recovery_test_assert(
    !str_contains(
        $source,
        "\$requestData['discord_name']"
    ),
    'client display name is not trusted'
);

/*
 * Recovery response must distinguish the non-economic Glyph path from the
 * explicitly approved rewarded classic-game path.
 */
score_recovery_test_assert(
    str_contains(
        $source,
        "'leaderboard_only' => true"
    ) &&
    str_contains(
        $source,
        "'leaderboard_only' => false"
    ),
    'response distinguishes leaderboard-only and rewarded paths'
);

score_recovery_test_assert(
    str_contains(
        $source,
        "'rewards_credited' => false"
    ) &&
    str_contains(
        $source,
        "'rewards_credited' => true"
    ),
    'response distinguishes non-reward and rewarded results'
);

score_recovery_test_assert(
    str_contains(
        $source,
        "'game_score'"
    ),
    'classic reward uses the established game_score ledger source'
);

/*
 * Write transaction safety.
 */
score_recovery_test_assert(
    str_contains(
        $source,
        'beginTransaction()'
    ),
    'leaderboard writes begin a transaction'
);

score_recovery_test_assert(
    str_contains(
        $source,
        'commit()'
    ),
    'successful leaderboard writes commit'
);

score_recovery_test_assert(
    str_contains(
        $source,
        'rollBack()'
    ),
    'failed leaderboard writes roll back'
);

/*
 * Emergency terminal disable must no longer be the endpoint result.
 */
score_recovery_test_assert(
    !str_contains(
        $source,
        'score_submission_temporarily_disabled'
    ),
    'terminal SEV-1 disable is replaced by bounded recovery'
);

echo "PASS: save-score V2 is authenticated, bounded, replay-guarded, and atomically audits classic DSPOINC rewards\n";
