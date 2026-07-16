<?php
/**
 * MouseFight DSPOINC Economy API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint performs controlled MouseFight economy operations inside one
 * SQLite transaction. It does not accept arbitrary SQL.
 *
 * Current supported actions:
 * - event_join
 * - event_cancel
 * - pvp_create
 * - pvp_accept_settle
 * - pvp_cancel
 *
 * Event economy:
 * - A positive event buy-in is removed from available DSPOINC.
 * - The payment is permanently burned unless the entire waiting event is
 *   cancelled by an authorized moderator or event creator.
 * - Event buy-ins never increase the configured champion prize.
 *
 * Important:
 * - tbl_user_scores is the authoritative DSPOINC ledger.
 * - tbl_score_adjustments is the user-visible audit history.
 * - tbl_mousefight_dspoinc_burns is the event burn/refund audit.
 * - tbl_mousefight_dspoinc_stakes is the PVP escrow audit.
 * - tbl_mousefight_dspoinc_pvp_settlements is the PVP winner payout audit.
 * - Frozen active DSPOINC cannot be spent.
 */

if (function_exists('ob_end_clean')) {
    @ob_end_clean();
}

if (function_exists('ob_clean')) {
    @ob_clean();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

const MOUSEFIGHT_ECONOMY_GAME = 'mousefight';
const MOUSEFIGHT_EVENT_MODE = 'admin_bracket_event';
const MOUSEFIGHT_WAITING_STATUS = 'waiting';
const MOUSEFIGHT_CANCELLED_STATUS = 'cancelled';

const MOUSEFIGHT_EVENT_BURN_SOURCE_PREFIX =
    'mousefight_event_buy_in_burn';

const MOUSEFIGHT_EVENT_BURN_REASON_PREFIX =
    'MouseFight event buy-in burned - Fight ID:';

const MOUSEFIGHT_EVENT_REFUND_SOURCE_PREFIX =
    'mousefight_event_buy_in_refund';

const MOUSEFIGHT_EVENT_REFUND_REASON_PREFIX =
    'MouseFight cancelled event buy-in refund - Fight ID:';


const MOUSEFIGHT_PVP_MODE = 'pvp_challenge';
const MOUSEFIGHT_FINISHED_STATUS = 'finished';

const MOUSEFIGHT_PVP_STAKE_SOURCE_PREFIX =
    'mousefight_pvp_stake';

const MOUSEFIGHT_PVP_STAKE_REASON_PREFIX =
    'MouseFight PVP stake escrowed - Fight ID:';

const MOUSEFIGHT_PVP_PAYOUT_SOURCE_PREFIX =
    'mousefight_pvp_payout';

const MOUSEFIGHT_PVP_PAYOUT_REASON_PREFIX =
    'MouseFight PVP pot paid - Fight ID:';

const MOUSEFIGHT_PVP_REFUND_SOURCE_PREFIX =
    'mousefight_pvp_refund';

const MOUSEFIGHT_PVP_REFUND_REASON_PREFIX =
    'MouseFight cancelled PVP stake refund - Fight ID:';

/**
 * Return a JSON response and stop execution.
 */
function mousefight_economy_output(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);

    if (function_exists('ob_end_clean')) {
        @ob_end_clean();
    }

    if (function_exists('ob_clean')) {
        @ob_clean();
    }

    echo json_encode($payload);
    exit;
}

/**
 * Return the Discord bot authorization header.
 */
function mousefight_economy_get_auth_token(): string
{
    $headers = [];

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }

    foreach (['Authorization', 'authorization'] as $headerName) {
        if (isset($headers[$headerName])) {
            return trim((string)$headers[$headerName]);
        }
    }

    return '';
}

/**
 * Return a required clean string.
 */
function mousefight_economy_required_string(
    array $data,
    string $key,
    int $maximumLength = 1000
): string {
    $value = trim((string)($data[$key] ?? ''));

    if ($value === '') {
        throw new RuntimeException("Missing required value: {$key}");
    }

    if (strlen($value) > $maximumLength) {
        throw new RuntimeException("Value is too long: {$key}");
    }

    return $value;
}

/**
 * Return a clean optional string.
 */
function mousefight_economy_optional_string(
    array $data,
    string $key,
    int $maximumLength = 5000
): string {
    $value = trim((string)($data[$key] ?? ''));

    if (strlen($value) > $maximumLength) {
        throw new RuntimeException("Value is too long: {$key}");
    }

    return $value;
}

/**
 * Return an integer in a safe range.
 */
function mousefight_economy_integer(
    $value,
    int $minimum = 0,
    int $maximum = 100000000
): int {
    if (!is_numeric($value)) {
        return $minimum;
    }

    return max($minimum, min($maximum, (int)$value));
}

/**
 * Return a boolean request value.
 */
function mousefight_economy_boolean($value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(trim((string)$value));

    return in_array(
        $normalized,
        ['1', 'true', 'yes', 'on'],
        true
    );
}

/**
 * Prepare one SQLite statement or throw.
 */
function mousefight_economy_prepare(
    SQLite3 $db,
    string $sql
): SQLite3Stmt {
    $statement = $db->prepare($sql);

    if (!$statement) {
        throw new RuntimeException(
            'Failed to prepare statement: ' . $db->lastErrorMsg()
        );
    }

    return $statement;
}

/**
 * Bind positional values using safe SQLite value types.
 */
function mousefight_economy_bind_values(
    SQLite3Stmt $statement,
    array $values
): void {
    foreach (array_values($values) as $index => $value) {
        $parameterIndex = $index + 1;

        if ($value === null) {
            $statement->bindValue(
                $parameterIndex,
                null,
                SQLITE3_NULL
            );
            continue;
        }

        if (is_int($value)) {
            $statement->bindValue(
                $parameterIndex,
                $value,
                SQLITE3_INTEGER
            );
            continue;
        }

        if (is_float($value)) {
            $statement->bindValue(
                $parameterIndex,
                $value,
                SQLITE3_FLOAT
            );
            continue;
        }

        $statement->bindValue(
            $parameterIndex,
            (string)$value,
            SQLITE3_TEXT
        );
    }
}

/**
 * Execute a prepared statement or throw.
 */
function mousefight_economy_execute(
    SQLite3 $db,
    SQLite3Stmt $statement
) {
    $result = $statement->execute();

    if ($result === false) {
        throw new RuntimeException(
            'Failed to execute statement: ' . $db->lastErrorMsg()
        );
    }

    return $result;
}

/**
 * Return one associative row or null.
 */
function mousefight_economy_fetch_one(
    SQLite3 $db,
    string $sql,
    array $values = []
): ?array {
    $statement = mousefight_economy_prepare($db, $sql);
    mousefight_economy_bind_values($statement, $values);

    $result = mousefight_economy_execute($db, $statement);
    $row = $result->fetchArray(SQLITE3_ASSOC);

    return is_array($row) ? $row : null;
}

/**
 * Return all associative rows.
 */
function mousefight_economy_fetch_all(
    SQLite3 $db,
    string $sql,
    array $values = []
): array {
    $statement = mousefight_economy_prepare($db, $sql);
    mousefight_economy_bind_values($statement, $values);

    $result = mousefight_economy_execute($db, $statement);
    $rows = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Execute one write statement and return the inserted row ID when available.
 */
function mousefight_economy_write(
    SQLite3 $db,
    string $sql,
    array $values = []
): int {
    $statement = mousefight_economy_prepare($db, $sql);
    mousefight_economy_bind_values($statement, $values);
    mousefight_economy_execute($db, $statement);

    return (int)$db->lastInsertRowID();
}

/**
 * Calculate current total, frozen and available DSPOINC.
 *
 * Available DSPOINC:
 * total ledger score minus active frozen stake amounts.
 */
function mousefight_economy_get_balance(
    SQLite3 $db,
    string $userId
): array {
    $scoreRow = mousefight_economy_fetch_one(
        $db,
        "
        SELECT COALESCE(SUM(score), 0) AS total_dspoinc
        FROM tbl_user_scores
        WHERE user_id = ?
        ",
        [$userId]
    );

    $frozenRow = mousefight_economy_fetch_one(
        $db,
        "
        SELECT COALESCE(SUM(amount), 0) AS frozen_dspoinc
        FROM tbl_dspoinc_stakes
        WHERE user_id = ?
          AND status = 'active'
        ",
        [$userId]
    );

    $totalDspoinc = (int)($scoreRow['total_dspoinc'] ?? 0);
    $frozenDspoinc = (int)($frozenRow['frozen_dspoinc'] ?? 0);
    $availableDspoinc = max(
        0,
        $totalDspoinc - $frozenDspoinc
    );

    return [
        'total_dspoinc' => $totalDspoinc,
        'frozen_dspoinc' => $frozenDspoinc,
        'available_dspoinc' => $availableDspoinc
    ];
}

/**
 * Validate and normalize one MouseFight participant payload.
 */
function mousefight_economy_build_participant(array $input): array
{
    return [
        'user_id' => mousefight_economy_required_string(
            $input,
            'user_id',
            64
        ),
        'username' => mousefight_economy_optional_string(
            $input,
            'username',
            200
        ),
        'display_name' => mousefight_economy_optional_string(
            $input,
            'display_name',
            200
        ),
        'avatar_url' => mousefight_economy_optional_string(
            $input,
            'avatar_url',
            2000
        ),
        'wallet' => mousefight_economy_optional_string(
            $input,
            'wallet',
            200
        ),
        'token_id' => mousefight_economy_required_string(
            $input,
            'token_id',
            200
        ),
        'collection' => mousefight_economy_optional_string(
            $input,
            'collection',
            100
        ) ?: 'genesis',
        'custom_name' => mousefight_economy_optional_string(
            $input,
            'custom_name',
            200
        ),
        'metadata_name' => mousefight_economy_optional_string(
            $input,
            'metadata_name',
            200
        ),
        'image_url' => mousefight_economy_optional_string(
            $input,
            'image_url',
            2000
        ),
        'mouse_warrior_power' => mousefight_economy_integer(
            $input['mouse_warrior_power'] ?? 0,
            0,
            100000000
        ),
        'trait_power' => mousefight_economy_integer(
            $input['trait_power'] ?? 0,
            0,
            100000000
        ),
        'ability_power' => mousefight_economy_integer(
            $input['ability_power'] ?? 0,
            0,
            100000000
        ),
        'genetic_support_power' => mousefight_economy_integer(
            $input['genetic_support_power'] ?? 0,
            0,
            100000000
        ),
        'highest_trait_level' => mousefight_economy_integer(
            $input['highest_trait_level'] ?? 0,
            0,
            100000
        ),
        'fitness_unlocked' => mousefight_economy_boolean(
            $input['fitness_unlocked'] ?? false
        ) ? 1 : 0,
        'inventory_enabled' => mousefight_economy_boolean(
            $input['inventory_enabled'] ?? true
        ) ? 1 : 0,
        'max_inventory_items' => mousefight_economy_integer(
            $input['max_inventory_items'] ?? 0,
            0,
            10
        ),
        'snapshot_json' => mousefight_economy_optional_string(
            $input,
            'snapshot_json',
            1000000
        )
    ];
}

/**
 * Insert one participant after all economic validation has passed.
 */
function mousefight_economy_insert_participant(
    SQLite3 $db,
    string $fightId,
    array $participant
): int {
    return mousefight_economy_write(
        $db,
        "
        INSERT INTO tbl_mousefight_participants (
            fight_id,
            user_id,
            username,
            display_name,
            avatar_url,
            wallet,
            token_id,
            collection,
            custom_name,
            metadata_name,
            image_url,
            mouse_warrior_power,
            trait_power,
            ability_power,
            genetic_support_power,
            highest_trait_level,
            fitness_unlocked,
            inventory_enabled,
            max_inventory_items,
            status,
            wins,
            losses,
            kills,
            current_round,
            snapshot_json,
            joined_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, 'alive',
            0, 0, 0, 1, ?, datetime('now')
        )
        ",
        [
            $fightId,
            $participant['user_id'],
            $participant['username'],
            $participant['display_name'],
            $participant['avatar_url'],
            $participant['wallet'],
            $participant['token_id'],
            $participant['collection'],
            $participant['custom_name'],
            $participant['metadata_name'],
            $participant['image_url'],
            $participant['mouse_warrior_power'],
            $participant['trait_power'],
            $participant['ability_power'],
            $participant['genetic_support_power'],
            $participant['highest_trait_level'],
            $participant['fitness_unlocked'],
            $participant['inventory_enabled'],
            $participant['max_inventory_items'],
            $participant['snapshot_json']
        ]
    );
}

/**
 * Join one waiting MouseFight event atomically.
 *
 * A paid join writes:
 * - negative tbl_user_scores row;
 * - remove tbl_score_adjustments row;
 * - tbl_mousefight_dspoinc_burns row;
 * - participant row.
 *
 * A free join writes only the participant row.
 */
function mousefight_economy_event_join(
    SQLite3 $db,
    array $data
): array {
    $fightId = mousefight_economy_required_string(
        $data,
        'fight_id',
        100
    );

    $participantInput = $data['participant'] ?? null;

    if (!is_array($participantInput)) {
        throw new RuntimeException(
            'Missing MouseFight participant payload.'
        );
    }

    $participant = mousefight_economy_build_participant(
        $participantInput
    );

    $fight = mousefight_economy_fetch_one(
        $db,
        "
        SELECT
            fight_id,
            created_by_user_id,
            status,
            mode,
            buy_in_dspoinc,
            max_players
        FROM tbl_mousefights
        WHERE fight_id = ?
        LIMIT 1
        ",
        [$fightId]
    );

    if (!$fight) {
        throw new RuntimeException(
            'MouseFight event was not found.'
        );
    }

    if (
        (string)$fight['mode'] !==
        MOUSEFIGHT_EVENT_MODE
    ) {
        throw new RuntimeException(
            'This MouseFight is not an event.'
        );
    }

    if (
        (string)$fight['status'] !==
        MOUSEFIGHT_WAITING_STATUS
    ) {
        throw new RuntimeException(
            'This MouseFight event is no longer waiting.'
        );
    }

    $existingParticipant = mousefight_economy_fetch_one(
        $db,
        "
        SELECT id
        FROM tbl_mousefight_participants
        WHERE fight_id = ?
          AND user_id = ?
        LIMIT 1
        ",
        [
            $fightId,
            $participant['user_id']
        ]
    );

    if ($existingParticipant) {
        throw new RuntimeException(
            'You already joined this MouseFight event.'
        );
    }

    $existingToken = mousefight_economy_fetch_one(
        $db,
        "
        SELECT id
        FROM tbl_mousefight_participants
        WHERE fight_id = ?
          AND token_id = ?
          AND collection = ?
        LIMIT 1
        ",
        [
            $fightId,
            $participant['token_id'],
            $participant['collection']
        ]
    );

    if ($existingToken) {
        throw new RuntimeException(
            'This Genesis mouse is already in the event.'
        );
    }

    $participantCountRow = mousefight_economy_fetch_one(
        $db,
        "
        SELECT COUNT(*) AS participant_count
        FROM tbl_mousefight_participants
        WHERE fight_id = ?
        ",
        [$fightId]
    );

    $participantCount = (int)(
        $participantCountRow['participant_count'] ?? 0
    );

    $maximumPlayers = max(
        2,
        (int)($fight['max_players'] ?? 2)
    );

    if ($participantCount >= $maximumPlayers) {
        throw new RuntimeException(
            'This MouseFight event is already full.'
        );
    }

    $buyInAmount = mousefight_economy_integer(
        $fight['buy_in_dspoinc'] ?? 0,
        0,
        100000000
    );

    $scoreId = null;
    $adjustmentId = null;
    $burnId = null;
    $balance = mousefight_economy_get_balance(
        $db,
        $participant['user_id']
    );

    if ($buyInAmount > 0) {
        $existingBurn = mousefight_economy_fetch_one(
            $db,
            "
            SELECT burn_id, status
            FROM tbl_mousefight_dspoinc_burns
            WHERE fight_id = ?
              AND user_id = ?
            LIMIT 1
            ",
            [
                $fightId,
                $participant['user_id']
            ]
        );

        if ($existingBurn) {
            throw new RuntimeException(
                'A MouseFight event buy-in already exists for this user.'
            );
        }

        if (
            $balance['available_dspoinc'] <
            $buyInAmount
        ) {
            throw new RuntimeException(
                'Insufficient available DSPOINC for this event buy-in.'
            );
        }

        $scoreSource =
            MOUSEFIGHT_EVENT_BURN_SOURCE_PREFIX .
            ':' .
            $fightId;

        $adjustmentReason =
            MOUSEFIGHT_EVENT_BURN_REASON_PREFIX .
            ' ' .
            $fightId;

        $scoreId = mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_user_scores (
                user_id,
                game,
                score,
                timestamp,
                source
            ) VALUES (
                ?,
                ?,
                ?,
                datetime('now'),
                ?
            )
            ",
            [
                $participant['user_id'],
                MOUSEFIGHT_ECONOMY_GAME,
                -$buyInAmount,
                $scoreSource
            ]
        );

        $adjustmentId = mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_score_adjustments (
                user_id,
                admin_id,
                amount,
                action,
                reason,
                timestamp
            ) VALUES (
                ?,
                ?,
                ?,
                'remove',
                ?,
                datetime('now')
            )
            ",
            [
                $participant['user_id'],
                (string)$fight['created_by_user_id'],
                $buyInAmount,
                $adjustmentReason
            ]
        );

        $burnId = mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_mousefight_dspoinc_burns (
                fight_id,
                user_id,
                username,
                amount,
                status,
                score_id,
                adjustment_id,
                burned_at,
                created_at,
                updated_at
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                'burned',
                ?,
                ?,
                datetime('now'),
                datetime('now'),
                datetime('now')
            )
            ",
            [
                $fightId,
                $participant['user_id'],
                $participant['username'],
                $buyInAmount,
                $scoreId,
                $adjustmentId
            ]
        );
    }

    $participantId =
        mousefight_economy_insert_participant(
            $db,
            $fightId,
            $participant
        );

    $availableAfter = max(
        0,
        $balance['available_dspoinc'] -
        $buyInAmount
    );

    return [
        'fight_id' => $fightId,
        'participant_id' => $participantId,
        'user_id' => $participant['user_id'],
        'token_id' => $participant['token_id'],
        'buy_in_dspoinc' => $buyInAmount,
        'burn_id' => $burnId,
        'score_id' => $scoreId,
        'adjustment_id' => $adjustmentId,
        'balance_before' => $balance,
        'available_after' => $availableAfter
    ];
}

/**
 * Cancel one waiting event and refund every unrefunded event burn.
 */
function mousefight_economy_event_cancel(
    SQLite3 $db,
    array $data
): array {
    $fightId = mousefight_economy_required_string(
        $data,
        'fight_id',
        100
    );

    $actorUserId = mousefight_economy_required_string(
        $data,
        'actor_user_id',
        64
    );

    $actorIsModerator = mousefight_economy_boolean(
        $data['actor_is_moderator'] ?? false
    );

    $fight = mousefight_economy_fetch_one(
        $db,
        "
        SELECT
            fight_id,
            created_by_user_id,
            status,
            mode
        FROM tbl_mousefights
        WHERE fight_id = ?
        LIMIT 1
        ",
        [$fightId]
    );

    if (!$fight) {
        throw new RuntimeException(
            'MouseFight event was not found.'
        );
    }

    if (
        (string)$fight['mode'] !==
        MOUSEFIGHT_EVENT_MODE
    ) {
        throw new RuntimeException(
            'This MouseFight is not an event.'
        );
    }

    if (
        (string)$fight['status'] !==
        MOUSEFIGHT_WAITING_STATUS
    ) {
        throw new RuntimeException(
            'This MouseFight event cannot be cancelled anymore.'
        );
    }

    $isCreator =
        (string)$fight['created_by_user_id'] ===
        $actorUserId;

    if (!$actorIsModerator && !$isCreator) {
        throw new RuntimeException(
            'You are not authorized to cancel this MouseFight event.'
        );
    }

    $burnRows = mousefight_economy_fetch_all(
        $db,
        "
        SELECT
            burn_id,
            user_id,
            username,
            amount
        FROM tbl_mousefight_dspoinc_burns
        WHERE fight_id = ?
          AND status = 'burned'
        ORDER BY burn_id ASC
        ",
        [$fightId]
    );

    $refunds = [];

    foreach ($burnRows as $burnRow) {
        $refundAmount = (int)$burnRow['amount'];
        $refundUserId = (string)$burnRow['user_id'];

        $refundSource =
            MOUSEFIGHT_EVENT_REFUND_SOURCE_PREFIX .
            ':' .
            $fightId;

        $refundReason =
            MOUSEFIGHT_EVENT_REFUND_REASON_PREFIX .
            ' ' .
            $fightId;

        $refundScoreId = mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_user_scores (
                user_id,
                game,
                score,
                timestamp,
                source
            ) VALUES (
                ?,
                ?,
                ?,
                datetime('now'),
                ?
            )
            ",
            [
                $refundUserId,
                MOUSEFIGHT_ECONOMY_GAME,
                $refundAmount,
                $refundSource
            ]
        );

        $refundAdjustmentId = mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_score_adjustments (
                user_id,
                admin_id,
                amount,
                action,
                reason,
                timestamp
            ) VALUES (
                ?,
                ?,
                ?,
                'add',
                ?,
                datetime('now')
            )
            ",
            [
                $refundUserId,
                $actorUserId,
                $refundAmount,
                $refundReason
            ]
        );

        mousefight_economy_write(
            $db,
            "
            UPDATE tbl_mousefight_dspoinc_burns
            SET
                status = 'refunded',
                refunded_at = datetime('now'),
                refund_score_id = ?,
                refund_adjustment_id = ?,
                updated_at = datetime('now')
            WHERE burn_id = ?
              AND status = 'burned'
            ",
            [
                $refundScoreId,
                $refundAdjustmentId,
                (int)$burnRow['burn_id']
            ]
        );

        $refunds[] = [
            'burn_id' => (int)$burnRow['burn_id'],
            'user_id' => $refundUserId,
            'amount' => $refundAmount,
            'refund_score_id' => $refundScoreId,
            'refund_adjustment_id' =>
                $refundAdjustmentId
        ];
    }

    mousefight_economy_write(
        $db,
        "
        UPDATE tbl_mousefights
        SET
            status = ?,
            ended_at = datetime('now')
        WHERE fight_id = ?
          AND status = ?
        ",
        [
            MOUSEFIGHT_CANCELLED_STATUS,
            $fightId,
            MOUSEFIGHT_WAITING_STATUS
        ]
    );

    return [
        'fight_id' => $fightId,
        'status' => MOUSEFIGHT_CANCELLED_STATUS,
        'refund_count' => count($refunds),
        'refund_total' => array_sum(
            array_column($refunds, 'amount')
        ),
        'refunds' => $refunds
    ];
}


/**
 * Return one PVP fight row or throw.
 *
 * Plain language for DEVS FOR DECADES:
 * PVP economy always trusts the persisted fight configuration, never a wager
 * amount supplied by Discord after challenge creation.
 */
function mousefight_economy_get_pvp_fight(
    SQLite3 $db,
    string $fightId
): array {
    $fight = mousefight_economy_fetch_one(
        $db,
        "
        SELECT
            fight_id,
            created_by_user_id,
            challenged_user_id,
            status,
            mode,
            wager_dspoinc
        FROM tbl_mousefights
        WHERE fight_id = ?
        LIMIT 1
        ",
        [$fightId]
    );

    if (!$fight) {
        throw new RuntimeException('MouseFight PVP challenge was not found.');
    }

    if ((string)$fight['mode'] !== MOUSEFIGHT_PVP_MODE) {
        throw new RuntimeException('This MouseFight is not a PVP challenge.');
    }

    return $fight;
}

/**
 * Debit one PVP stake and record its escrow audit row.
 *
 * This helper is called only inside BEGIN IMMEDIATE transactions.
 */
function mousefight_economy_create_pvp_stake(
    SQLite3 $db,
    array $fight,
    array $participant,
    string $participantRole,
    int $stakeAmount
): array {
    if (!in_array($participantRole, ['challenger', 'opponent'], true)) {
        throw new RuntimeException('Invalid MouseFight PVP participant role.');
    }

    if ($stakeAmount <= 0) {
        return [
            'stake_id' => null,
            'score_id' => null,
            'adjustment_id' => null,
            'balance_before' => mousefight_economy_get_balance(
                $db,
                $participant['user_id']
            ),
            'available_after' => null
        ];
    }

    $existingStake = mousefight_economy_fetch_one(
        $db,
        "
        SELECT stake_id, status
        FROM tbl_mousefight_dspoinc_stakes
        WHERE fight_id = ?
          AND (
              user_id = ?
              OR participant_role = ?
          )
        LIMIT 1
        ",
        [
            (string)$fight['fight_id'],
            $participant['user_id'],
            $participantRole
        ]
    );

    if ($existingStake) {
        throw new RuntimeException(
            'A MouseFight PVP stake already exists for this participant role.'
        );
    }

    $balance = mousefight_economy_get_balance(
        $db,
        $participant['user_id']
    );

    if ($balance['available_dspoinc'] < $stakeAmount) {
        throw new RuntimeException(
            'Insufficient available DSPOINC for this MouseFight PVP stake.'
        );
    }

    $fightId = (string)$fight['fight_id'];
    $scoreSource =
        MOUSEFIGHT_PVP_STAKE_SOURCE_PREFIX .
        ':' .
        $participantRole .
        ':' .
        $fightId;

    $adjustmentReason =
        MOUSEFIGHT_PVP_STAKE_REASON_PREFIX .
        ' ' .
        $fightId .
        ' (' .
        $participantRole .
        ')';

    $scoreId = mousefight_economy_write(
        $db,
        "
        INSERT INTO tbl_user_scores (
            user_id,
            game,
            score,
            timestamp,
            source
        ) VALUES (?, ?, ?, datetime('now'), ?)
        ",
        [
            $participant['user_id'],
            MOUSEFIGHT_ECONOMY_GAME,
            -$stakeAmount,
            $scoreSource
        ]
    );

    $adjustmentId = mousefight_economy_write(
        $db,
        "
        INSERT INTO tbl_score_adjustments (
            user_id,
            admin_id,
            amount,
            action,
            reason,
            timestamp
        ) VALUES (?, ?, ?, 'remove', ?, datetime('now'))
        ",
        [
            $participant['user_id'],
            (string)$fight['created_by_user_id'],
            $stakeAmount,
            $adjustmentReason
        ]
    );

    $stakeId = mousefight_economy_write(
        $db,
        "
        INSERT INTO tbl_mousefight_dspoinc_stakes (
            fight_id,
            user_id,
            username,
            participant_role,
            amount,
            status,
            score_id,
            adjustment_id,
            staked_at,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, 'staked', ?, ?, datetime('now'), datetime('now'), datetime('now'))
        ",
        [
            $fightId,
            $participant['user_id'],
            $participant['username'],
            $participantRole,
            $stakeAmount,
            $scoreId,
            $adjustmentId
        ]
    );

    return [
        'stake_id' => $stakeId,
        'score_id' => $scoreId,
        'adjustment_id' => $adjustmentId,
        'balance_before' => $balance,
        'available_after' => max(
            0,
            $balance['available_dspoinc'] - $stakeAmount
        )
    ];
}

/**
 * Create one complete waiting PVP challenge atomically.
 *
 * Plain language for DEVS FOR DECADES:
 * This action owns the authoritative challenge creation transaction. It inserts
 * the waiting fight row, challenger fighter snapshot, challenger debit, and
 * challenger escrow audit together. If any write fails, the outer transaction
 * rolls back every write.
 *
 * The caller may provide only documented challenge configuration. Status, mode,
 * player count, event buy-in, event prize, and token payout fields are forced by
 * this endpoint and cannot be overridden by Discord.
 */
function mousefight_economy_pvp_create(
    SQLite3 $db,
    array $data
): array {
    $fightInput = $data['fight'] ?? null;
    $participantInput = $data['participant'] ?? null;

    if (!is_array($fightInput)) {
        throw new RuntimeException('Missing MouseFight PVP fight payload.');
    }

    if (!is_array($participantInput)) {
        throw new RuntimeException('Missing MouseFight challenger payload.');
    }

    $fightId = mousefight_economy_required_string(
        $fightInput,
        'fight_id',
        100
    );

    $createdByUserId = mousefight_economy_required_string(
        $fightInput,
        'created_by_user_id',
        64
    );

    $challengedUserId = mousefight_economy_required_string(
        $fightInput,
        'challenged_user_id',
        64
    );

    $channelId = mousefight_economy_required_string(
        $fightInput,
        'channel_id',
        64
    );

    $messageId = mousefight_economy_optional_string(
        $fightInput,
        'message_id',
        64
    );

    $matchFormat = mousefight_economy_required_string(
        $fightInput,
        'match_format',
        32
    );

    if (!in_array(
        $matchFormat,
        ['best_of_1', 'best_of_3', 'best_of_5'],
        true
    )) {
        throw new RuntimeException('Invalid MouseFight PVP match format.');
    }

    $stakeAmount = mousefight_economy_integer(
        $fightInput['wager_dspoinc'] ?? 0,
        0,
        100000000
    );

    $inventoryEnabled = mousefight_economy_boolean(
        $fightInput['inventory_enabled'] ?? true
    ) ? 1 : 0;

    $maximumInventoryItems = $inventoryEnabled === 1
        ? mousefight_economy_integer(
            $fightInput['max_inventory_items'] ?? 5,
            1,
            10
        )
        : 0;

    $minimumHighestTraitLevel = mousefight_economy_integer(
        $fightInput['min_highest_trait_level'] ?? 5,
        0,
        100000
    );

    $fitnessRequired = $minimumHighestTraitLevel > 0 ? 1 : 0;
    $allLevelsAccepted = $minimumHighestTraitLevel === 0 ? 1 : 0;

    $durationSeconds = mousefight_economy_integer(
        $fightInput['duration_seconds'] ?? 300,
        30,
        86400
    );

    $requireVerifiedWallet = mousefight_economy_boolean(
        $fightInput['require_verified_wallet'] ?? true
    ) ? 1 : 0;

    $metadataJson = mousefight_economy_optional_string(
        $fightInput,
        'metadata_json',
        1000000
    );

    $challenger = mousefight_economy_build_participant(
        $participantInput
    );

    if ($createdByUserId !== $challenger['user_id']) {
        throw new RuntimeException(
            'The PVP fight creator must match the challenger participant.'
        );
    }

    if ($challengedUserId === $createdByUserId) {
        throw new RuntimeException(
            'A MouseFight challenger cannot challenge themselves.'
        );
    }

    $existingFight = mousefight_economy_fetch_one(
        $db,
        'SELECT fight_id FROM tbl_mousefights WHERE fight_id = ? LIMIT 1',
        [$fightId]
    );

    if ($existingFight) {
        throw new RuntimeException(
            'This MouseFight PVP challenge already exists.'
        );
    }

    mousefight_economy_write(
        $db,
        "
        INSERT INTO tbl_mousefights (
            fight_id,
            created_by_user_id,
            challenged_user_id,
            channel_id,
            message_id,
            status,
            mode,
            title,
            bracket_mode,
            match_format,
            wager_dspoinc,
            prize_dspoinc,
            token_symbol,
            token_amount,
            inventory_enabled,
            all_levels_accepted,
            fitness_required,
            min_highest_trait_level,
            max_inventory_items,
            max_players,
            duration_seconds,
            required_role_id,
            require_verified_wallet,
            created_at,
            metadata_json,
            buy_in_dspoinc
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, NULL, NULL, ?,
            ?, 0, NULL, 0,
            ?, ?, ?, ?, ?,
            2, ?, NULL, ?,
            datetime('now'), ?, 0
        )
        ",
        [
            $fightId,
            $createdByUserId,
            $challengedUserId,
            $channelId,
            $messageId !== '' ? $messageId : null,
            MOUSEFIGHT_WAITING_STATUS,
            MOUSEFIGHT_PVP_MODE,
            $matchFormat,
            $stakeAmount,
            $inventoryEnabled,
            $allLevelsAccepted,
            $fitnessRequired,
            $minimumHighestTraitLevel,
            $maximumInventoryItems,
            $durationSeconds,
            $requireVerifiedWallet,
            $metadataJson !== '' ? $metadataJson : null
        ]
    );

    $fight = [
        'fight_id' => $fightId,
        'created_by_user_id' => $createdByUserId,
        'challenged_user_id' => $challengedUserId,
        'status' => MOUSEFIGHT_WAITING_STATUS,
        'mode' => MOUSEFIGHT_PVP_MODE,
        'wager_dspoinc' => $stakeAmount
    ];

    $stake = mousefight_economy_create_pvp_stake(
        $db,
        $fight,
        $challenger,
        'challenger',
        $stakeAmount
    );

    $participantId = mousefight_economy_insert_participant(
        $db,
        $fightId,
        $challenger
    );

    return [
        'fight_id' => $fightId,
        'status' => MOUSEFIGHT_WAITING_STATUS,
        'mode' => MOUSEFIGHT_PVP_MODE,
        'participant_id' => $participantId,
        'user_id' => $challenger['user_id'],
        'token_id' => $challenger['token_id'],
        'stake_dspoinc' => $stakeAmount,
        'stake' => $stake
    ];
}

/**
 * Accept a PVP challenge and settle the complete equal-stake pot atomically.
 *
 * The opponent debit, opponent participant row, winner credit, settlement row,
 * stake status updates and final fight state are committed together.
 */
function mousefight_economy_pvp_accept_settle(
    SQLite3 $db,
    array $data
): array {
    $fightId = mousefight_economy_required_string($data, 'fight_id', 100);
    $winnerUserId = mousefight_economy_required_string($data, 'winner_user_id', 64);
    $winnerUsername = mousefight_economy_optional_string($data, 'winner_username', 200);
    $winnerTokenId = mousefight_economy_optional_string($data, 'winner_token_id', 200);
    $participantInput = $data['participant'] ?? null;

    if (!is_array($participantInput)) {
        throw new RuntimeException('Missing MouseFight opponent payload.');
    }

    $opponent = mousefight_economy_build_participant($participantInput);
    $fight = mousefight_economy_get_pvp_fight($db, $fightId);

    if ((string)$fight['status'] !== MOUSEFIGHT_WAITING_STATUS) {
        throw new RuntimeException('This MouseFight PVP challenge cannot be accepted anymore.');
    }

    if ((string)($fight['challenged_user_id'] ?? '') !== $opponent['user_id']) {
        throw new RuntimeException('Only the challenged player can fund and accept this MouseFight.');
    }

    $challengerUserId = (string)$fight['created_by_user_id'];

    if (!in_array($winnerUserId, [$challengerUserId, $opponent['user_id']], true)) {
        throw new RuntimeException('The PVP winner must be one of the two challenge participants.');
    }

    $challengerParticipant = mousefight_economy_fetch_one(
        $db,
        "SELECT id, token_id FROM tbl_mousefight_participants WHERE fight_id = ? AND user_id = ? LIMIT 1",
        [$fightId, $challengerUserId]
    );

    if (!$challengerParticipant) {
        throw new RuntimeException('The MouseFight challenger participant snapshot is missing.');
    }

    $existingOpponent = mousefight_economy_fetch_one(
        $db,
        "SELECT id FROM tbl_mousefight_participants WHERE fight_id = ? AND user_id = ? LIMIT 1",
        [$fightId, $opponent['user_id']]
    );

    if ($existingOpponent) {
        throw new RuntimeException('The challenged player is already registered in this fight.');
    }

    $existingToken = mousefight_economy_fetch_one(
        $db,
        "SELECT id FROM tbl_mousefight_participants WHERE fight_id = ? AND token_id = ? AND collection = ? LIMIT 1",
        [$fightId, $opponent['token_id'], $opponent['collection']]
    );

    if ($existingToken) {
        throw new RuntimeException('This Genesis mouse is already in the PVP fight.');
    }

    $existingSettlement = mousefight_economy_fetch_one(
        $db,
        "SELECT settlement_id FROM tbl_mousefight_dspoinc_pvp_settlements WHERE fight_id = ? LIMIT 1",
        [$fightId]
    );

    if ($existingSettlement) {
        throw new RuntimeException('This MouseFight PVP pot is already settled.');
    }

    $stakeAmount = mousefight_economy_integer(
        $fight['wager_dspoinc'] ?? 0,
        0,
        100000000
    );

    if ($stakeAmount > 0) {
        $challengerStake = mousefight_economy_fetch_one(
            $db,
            "
            SELECT stake_id, amount, status
            FROM tbl_mousefight_dspoinc_stakes
            WHERE fight_id = ?
              AND participant_role = 'challenger'
              AND user_id = ?
            LIMIT 1
            ",
            [$fightId, $challengerUserId]
        );

        if (!$challengerStake || (string)$challengerStake['status'] !== 'staked') {
            throw new RuntimeException('The challenger PVP stake is missing or no longer active.');
        }

        if ((int)$challengerStake['amount'] !== $stakeAmount) {
            throw new RuntimeException('The challenger PVP stake does not match the configured wager.');
        }
    }

    $opponentStake = mousefight_economy_create_pvp_stake(
        $db,
        $fight,
        $opponent,
        'opponent',
        $stakeAmount
    );

    $opponentParticipantId = mousefight_economy_insert_participant(
        $db,
        $fightId,
        $opponent
    );

    $payoutScoreId = null;
    $payoutAdjustmentId = null;
    $settlementId = null;
    $totalPot = $stakeAmount * 2;

    if ($stakeAmount > 0) {
        $payoutSource = MOUSEFIGHT_PVP_PAYOUT_SOURCE_PREFIX . ':' . $fightId;
        $payoutReason = MOUSEFIGHT_PVP_PAYOUT_REASON_PREFIX . ' ' . $fightId;

        $payoutScoreId = mousefight_economy_write(
            $db,
            "INSERT INTO tbl_user_scores (user_id, game, score, timestamp, source) VALUES (?, ?, ?, datetime('now'), ?)",
            [$winnerUserId, MOUSEFIGHT_ECONOMY_GAME, $totalPot, $payoutSource]
        );

        $payoutAdjustmentId = mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_score_adjustments (
                user_id, admin_id, amount, action, reason, timestamp
            ) VALUES (?, ?, ?, 'add', ?, datetime('now'))
            ",
            [
                $winnerUserId,
                $challengerUserId,
                $totalPot,
                $payoutReason
            ]
        );

        $settlementId = mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_mousefight_dspoinc_pvp_settlements (
                fight_id,
                winner_user_id,
                winner_username,
                stake_per_player,
                total_pot,
                score_id,
                adjustment_id,
                status,
                settled_at,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', datetime('now'), datetime('now'), datetime('now'))
            ",
            [
                $fightId,
                $winnerUserId,
                $winnerUsername,
                $stakeAmount,
                $totalPot,
                $payoutScoreId,
                $payoutAdjustmentId
            ]
        );

        mousefight_economy_write(
            $db,
            "
            UPDATE tbl_mousefight_dspoinc_stakes
            SET status = 'settled', updated_at = datetime('now')
            WHERE fight_id = ?
              AND status = 'staked'
            ",
            [$fightId]
        );
    }

    mousefight_economy_write(
        $db,
        "
        UPDATE tbl_mousefights
        SET
            status = ?,
            started_at = COALESCE(started_at, datetime('now')),
            ended_at = datetime('now'),
            winner_user_id = ?,
            winner_token_id = ?
        WHERE fight_id = ?
          AND status = ?
        ",
        [
            MOUSEFIGHT_FINISHED_STATUS,
            $winnerUserId,
            $winnerTokenId !== '' ? $winnerTokenId : null,
            $fightId,
            MOUSEFIGHT_WAITING_STATUS
        ]
    );

    return [
        'fight_id' => $fightId,
        'status' => MOUSEFIGHT_FINISHED_STATUS,
        'opponent_participant_id' => $opponentParticipantId,
        'winner_user_id' => $winnerUserId,
        'stake_per_player' => $stakeAmount,
        'total_pot' => $totalPot,
        'opponent_stake' => $opponentStake,
        'settlement_id' => $settlementId,
        'payout_score_id' => $payoutScoreId,
        'payout_adjustment_id' => $payoutAdjustmentId
    ];
}

/**
 * Cancel one waiting PVP challenge and refund every active PVP stake.
 */
function mousefight_economy_pvp_cancel(
    SQLite3 $db,
    array $data
): array {
    $fightId = mousefight_economy_required_string($data, 'fight_id', 100);
    $actorUserId = mousefight_economy_required_string($data, 'actor_user_id', 64);
    $actorIsModerator = mousefight_economy_boolean(
        $data['actor_is_moderator'] ?? false
    );

    $fight = mousefight_economy_get_pvp_fight($db, $fightId);

    if ((string)$fight['status'] !== MOUSEFIGHT_WAITING_STATUS) {
        throw new RuntimeException('This MouseFight PVP challenge cannot be cancelled anymore.');
    }

    $isCreator = (string)$fight['created_by_user_id'] === $actorUserId;
    $isChallengedPlayer =
        (string)($fight['challenged_user_id'] ?? '') === $actorUserId;

    if (!$actorIsModerator && !$isCreator && !$isChallengedPlayer) {
        throw new RuntimeException('You are not authorized to cancel this MouseFight PVP challenge.');
    }

    $stakeRows = mousefight_economy_fetch_all(
        $db,
        "
        SELECT stake_id, user_id, username, participant_role, amount
        FROM tbl_mousefight_dspoinc_stakes
        WHERE fight_id = ?
          AND status = 'staked'
        ORDER BY stake_id ASC
        ",
        [$fightId]
    );

    $refunds = [];

    foreach ($stakeRows as $stakeRow) {
        $refundUserId = (string)$stakeRow['user_id'];
        $refundAmount = (int)$stakeRow['amount'];
        $participantRole = (string)$stakeRow['participant_role'];
        $refundSource =
            MOUSEFIGHT_PVP_REFUND_SOURCE_PREFIX .
            ':' .
            $participantRole .
            ':' .
            $fightId;
        $refundReason =
            MOUSEFIGHT_PVP_REFUND_REASON_PREFIX .
            ' ' .
            $fightId .
            ' (' .
            $participantRole .
            ')';

        $refundScoreId = mousefight_economy_write(
            $db,
            "INSERT INTO tbl_user_scores (user_id, game, score, timestamp, source) VALUES (?, ?, ?, datetime('now'), ?)",
            [
                $refundUserId,
                MOUSEFIGHT_ECONOMY_GAME,
                $refundAmount,
                $refundSource
            ]
        );

        $refundAdjustmentId = mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_score_adjustments (
                user_id, admin_id, amount, action, reason, timestamp
            ) VALUES (?, ?, ?, 'add', ?, datetime('now'))
            ",
            [
                $refundUserId,
                $actorUserId,
                $refundAmount,
                $refundReason
            ]
        );

        mousefight_economy_write(
            $db,
            "
            UPDATE tbl_mousefight_dspoinc_stakes
            SET
                status = 'refunded',
                refunded_at = datetime('now'),
                refund_score_id = ?,
                refund_adjustment_id = ?,
                updated_at = datetime('now')
            WHERE stake_id = ?
              AND status = 'staked'
            ",
            [
                $refundScoreId,
                $refundAdjustmentId,
                (int)$stakeRow['stake_id']
            ]
        );

        $refunds[] = [
            'stake_id' => (int)$stakeRow['stake_id'],
            'user_id' => $refundUserId,
            'participant_role' => $participantRole,
            'amount' => $refundAmount,
            'refund_score_id' => $refundScoreId,
            'refund_adjustment_id' => $refundAdjustmentId
        ];
    }

    mousefight_economy_write(
        $db,
        "
        UPDATE tbl_mousefights
        SET status = ?, ended_at = datetime('now')
        WHERE fight_id = ?
          AND status = ?
        ",
        [
            MOUSEFIGHT_CANCELLED_STATUS,
            $fightId,
            MOUSEFIGHT_WAITING_STATUS
        ]
    );

    return [
        'fight_id' => $fightId,
        'status' => MOUSEFIGHT_CANCELLED_STATUS,
        'refund_count' => count($refunds),
        'refund_total' => array_sum(array_column($refunds, 'amount')),
        'refunds' => $refunds
    ];
}

$authToken = mousefight_economy_get_auth_token();

$validTokens = array_values(
    array_filter([
        $_ENV['DISCORD_BOT_SECRET']
            ?? getenv('DISCORD_BOT_SECRET'),
        $_ENV['DISCORD_SECRET']
            ?? getenv('DISCORD_SECRET')
    ])
);

if (
    $authToken === '' ||
    !in_array($authToken, $validTokens, true)
) {
    mousefight_economy_output(
        [
            'success' => false,
            'error' => 'Unauthorized'
        ],
        401
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mousefight_economy_output(
        [
            'success' => false,
            'error' => 'Method not allowed'
        ],
        405
    );
}

$rawInput = file_get_contents('php://input');

if (!$rawInput) {
    mousefight_economy_output(
        [
            'success' => false,
            'error' => 'No input received'
        ],
        400
    );
}

$data = json_decode($rawInput, true);

if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($data)
) {
    mousefight_economy_output(
        [
            'success' => false,
            'error' =>
                'Invalid JSON input: ' .
                json_last_error_msg()
        ],
        400
    );
}

$action = trim((string)($data['action'] ?? ''));

if (
    !in_array(
        $action,
        [
            'event_join',
            'event_cancel',
            'pvp_create',
            'pvp_accept_settle',
            'pvp_cancel'
        ],
        true
    )
) {
    mousefight_economy_output(
        [
            'success' => false,
            'error' => 'Unsupported MouseFight economy action'
        ],
        400
    );
}

$dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

if (!file_exists($dbPath)) {
    mousefight_economy_output(
        [
            'success' => false,
            'error' => 'MouseFight database not found'
        ],
        500
    );
}

$db = null;
$transactionStarted = false;

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    $db->busyTimeout(60000);

    $db->exec('PRAGMA foreign_keys = ON');

    if (!$db->exec('BEGIN IMMEDIATE')) {
        throw new RuntimeException(
            'Could not start MouseFight economy transaction.'
        );
    }

    $transactionStarted = true;

    switch ($action) {
        case 'event_join':
            $result = mousefight_economy_event_join($db, $data);
            break;

        case 'event_cancel':
            $result = mousefight_economy_event_cancel($db, $data);
            break;

        case 'pvp_create':
            $result = mousefight_economy_pvp_create($db, $data);
            break;

        case 'pvp_accept_settle':
            $result = mousefight_economy_pvp_accept_settle($db, $data);
            break;

        case 'pvp_cancel':
            $result = mousefight_economy_pvp_cancel($db, $data);
            break;

        default:
            throw new RuntimeException('Unsupported MouseFight economy action.');
    }

    if (!$db->exec('COMMIT')) {
        throw new RuntimeException(
            'Could not commit MouseFight economy transaction.'
        );
    }

    $transactionStarted = false;

    mousefight_economy_output([
        'success' => true,
        'action' => $action,
        'data' => $result
    ]);
} catch (Throwable $error) {
    if (
        $db instanceof SQLite3 &&
        $transactionStarted
    ) {
        try {
            $db->exec('ROLLBACK');
        } catch (Throwable $rollbackError) {
            error_log(
                '[MOUSEFIGHT ECONOMY] Rollback failed: ' .
                $rollbackError->getMessage()
            );
        }
    }

    error_log(
        '[MOUSEFIGHT ECONOMY] ' .
        $action .
        ' failed: ' .
        $error->getMessage()
    );

    mousefight_economy_output(
        [
            'success' => false,
            'action' => $action,
            'error' => $error->getMessage()
        ],
        400
    );
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}