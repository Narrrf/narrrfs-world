<?php
/**
 * Public MouseFight Genesis Leagues V2 shadow snapshot API.
 *
 * Plain language for DEVS FOR DECADES:
 *
 * This endpoint is a READ-ONLY presentation layer for the already-published
 * MouseFight Genesis League V2 shadow snapshots.
 *
 * It does NOT calculate:
 * - League Power;
 * - percentile boundaries;
 * - H2 global hysteresis;
 * - I6 individual hysteresis;
 * - promotions / relegations;
 * - progression meters.
 *
 * Those values are decided before publication by the validated V2 publisher.
 *
 * This endpoint only:
 * 1. finds immutable V2 shadow snapshot files;
 * 2. validates their public contract;
 * 3. selects the highest valid snapshot sequence;
 * 4. returns that snapshot unchanged.
 *
 * This endpoint must never:
 * - write database rows;
 * - mutate Genesis ownership;
 * - mutate permanent Traits or Abilities;
 * - mutate Lab progression;
 * - mutate Genetic Items;
 * - touch DSPOINC / SPOINC;
 * - touch PVP escrow / settlement;
 * - touch event burns / refunds;
 * - touch Fight Recovery;
 * - execute MouseFight combat.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

const MOUSEFIGHT_LEAGUES_V2_ALLOWED_CONTRACTS = [
    'mousefight_genesis_leagues_v2_shadow_baseline_v1',
    'mousefight_genesis_leagues_v2_shadow_daily_v1',
];

const MOUSEFIGHT_LEAGUES_V2_EXPECTED_LEAGUE_COUNT = 9;
const MOUSEFIGHT_LEAGUES_V2_EXPECTED_THRESHOLD_COUNT = 8;

$projectRoot = dirname(__DIR__, 2);

$snapshotDirectory =
    $projectRoot
    . '/league-audit';

$snapshotPattern =
    $snapshotDirectory
    . '/mousefight-leagues-v2-shadow-snapshot-*.json';

/**
 * Return one JSON response and stop execution.
 */
function mousefight_leagues_v2_json(
    array $payload,
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

    exit;
}

/**
 * Return true when published thresholds are strictly increasing integers.
 */
function mousefight_leagues_v2_valid_thresholds(
    array $thresholds
): bool {
    if (
        count($thresholds)
        !== MOUSEFIGHT_LEAGUES_V2_EXPECTED_THRESHOLD_COUNT
    ) {
        return false;
    }

    $previous = null;

    foreach ($thresholds as $threshold) {
        if (!is_numeric($threshold)) {
            return false;
        }

        $current = (int)$threshold;

        if (
            $previous !== null
            && $current <= $previous
        ) {
            return false;
        }

        $previous = $current;
    }

    return true;
}

/**
 * Validate one published V2 shadow snapshot.
 *
 * Plain language:
 * Invalid, incomplete, future-unknown, or partially written snapshots must
 * never silently become the public League authority.
 */
function mousefight_leagues_v2_validate_snapshot(
    array $payload,
    int $filenameSequence
): bool {
    if (($payload['success'] ?? null) !== true) {
        return false;
    }

    $contract = $payload['contract'] ?? null;

    if (!is_array($contract)) {
        return false;
    }

    $contractVersion = trim(
        (string)($contract['version'] ?? '')
    );

    if (
        !in_array(
            $contractVersion,
            MOUSEFIGHT_LEAGUES_V2_ALLOWED_CONTRACTS,
            true
        )
    ) {
        return false;
    }

    if (($contract['read_only'] ?? null) !== true) {
        return false;
    }

    if (($contract['shadow_only'] ?? null) !== true) {
        return false;
    }

    if (
        (int)($contract['league_count'] ?? 0)
        !== MOUSEFIGHT_LEAGUES_V2_EXPECTED_LEAGUE_COUNT
    ) {
        return false;
    }

    if (
        ($contract['identity'] ?? null)
        !== 'token_id_plus_collection'
    ) {
        return false;
    }

    $snapshot = $payload['snapshot'] ?? null;

    if (!is_array($snapshot)) {
        return false;
    }

    $snapshotSequence =
        (int)($snapshot['sequence'] ?? 0);

    if (
        $snapshotSequence <= 0
        || $snapshotSequence !== $filenameSequence
    ) {
        return false;
    }

    $publishedThresholds =
        $snapshot['published_thresholds'] ?? null;

    if (
        !is_array($publishedThresholds)
        || !mousefight_leagues_v2_valid_thresholds(
            $publishedThresholds
        )
    ) {
        return false;
    }

    $tiers = $payload['tiers'] ?? null;

    if (
        !is_array($tiers)
        || count($tiers)
            !== MOUSEFIGHT_LEAGUES_V2_EXPECTED_LEAGUE_COUNT
    ) {
        return false;
    }

    $summary = $payload['summary'] ?? null;

    if (!is_array($summary)) {
        return false;
    }

    $summaryTiers =
        $summary['tiers'] ?? null;

    if (
        !is_array($summaryTiers)
        || count($summaryTiers)
            !== MOUSEFIGHT_LEAGUES_V2_EXPECTED_LEAGUE_COUNT
    ) {
        return false;
    }

    $mice = $payload['mice'] ?? null;

    if (!is_array($mice)) {
        return false;
    }

    if (
        (int)($summary['named_mice'] ?? -1)
        !== count($mice)
    ) {
        return false;
    }

    if (
        (int)($snapshot['population'] ?? -1)
        !== count($mice)
    ) {
        return false;
    }

    $identities = [];

    foreach ($mice as $mouse) {
        if (!is_array($mouse)) {
            return false;
        }

        $tokenId = trim(
            (string)($mouse['token_id'] ?? '')
        );

        $collection = trim(
            (string)($mouse['collection'] ?? 'genesis')
        );

        if (
            $tokenId === ''
            || $collection === ''
        ) {
            return false;
        }

        $identity =
            $tokenId
            . '|'
            . $collection;

        if (isset($identities[$identity])) {
            return false;
        }

        $identities[$identity] = true;

        $league = $mouse['league'] ?? null;
        $progression = $mouse['progression'] ?? null;
        $movement = $mouse['movement'] ?? null;

        if (
            !is_array($league)
            || !is_array($progression)
            || !is_array($movement)
        ) {
            return false;
        }

        $publishedRank =
            (int)($league['rank'] ?? 0);

        if (
            $publishedRank < 1
            || $publishedRank
                > MOUSEFIGHT_LEAGUES_V2_EXPECTED_LEAGUE_COUNT
        ) {
            return false;
        }

        $progressPercent =
            $progression['progress_percent'] ?? null;

        if (
            !is_numeric($progressPercent)
            || (float)$progressPercent < 0.0
            || (float)$progressPercent > 100.0
        ) {
            return false;
        }
    }

    $movements = $payload['movements'] ?? null;

    if (!is_array($movements)) {
        return false;
    }

    return true;
}

try {
    if (
        !is_dir($snapshotDirectory)
        || !is_readable($snapshotDirectory)
    ) {
        throw new RuntimeException(
            'V2 League snapshot directory unavailable.'
        );
    }

    $snapshotFiles = glob(
        $snapshotPattern,
        GLOB_NOSORT
    );

    if (
        !is_array($snapshotFiles)
        || count($snapshotFiles) === 0
    ) {
        throw new RuntimeException(
            'No V2 League snapshot files found.'
        );
    }

    $validSnapshots = [];

    foreach ($snapshotFiles as $snapshotPath) {
        $filename = basename($snapshotPath);

        if (
            preg_match(
                '/^mousefight-leagues-v2-shadow-snapshot-(\d+)\.json$/',
                $filename,
                $matches
            ) !== 1
        ) {
            continue;
        }

        $filenameSequence =
            (int)$matches[1];

        if (
            $filenameSequence <= 0
            || !is_file($snapshotPath)
            || !is_readable($snapshotPath)
        ) {
            continue;
        }

        $rawSnapshot =
            file_get_contents($snapshotPath);

        if ($rawSnapshot === false) {
            continue;
        }

        $payload = json_decode(
            $rawSnapshot,
            true
        );

        if (
            !is_array($payload)
            || json_last_error() !== JSON_ERROR_NONE
        ) {
            continue;
        }

        if (
            !mousefight_leagues_v2_validate_snapshot(
                $payload,
                $filenameSequence
            )
        ) {
            continue;
        }

        if (
            isset(
                $validSnapshots[
                    $filenameSequence
                ]
            )
        ) {
            throw new RuntimeException(
                'Duplicate V2 League snapshot sequence.'
            );
        }

        $validSnapshots[
            $filenameSequence
        ] = $payload;
    }

    if (count($validSnapshots) === 0) {
        throw new RuntimeException(
            'No valid V2 League snapshot available.'
        );
    }

    krsort(
        $validSnapshots,
        SORT_NUMERIC
    );

    $latestSnapshot =
        reset($validSnapshots);

    if (!is_array($latestSnapshot)) {
        throw new RuntimeException(
            'Unable to resolve latest V2 League snapshot.'
        );
    }

    mousefight_leagues_v2_json(
        $latestSnapshot
    );
} catch (Throwable $error) {
    error_log(
        'MouseFight Genesis Leagues V2 API error: '
        . $error->getMessage()
    );

    mousefight_leagues_v2_json(
        [
            'success' => false,
            'error' =>
                'Unable to load the published MouseFight Genesis League V2 snapshot.',
        ],
        500
    );
}
