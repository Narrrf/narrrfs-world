<?php
/**
 * MouseFight DSPOINC Economy API.
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint performs controlled MouseFight economy operations inside one
 * SQLite transaction. It does not accept arbitrary SQL.
 *
 * Current supported actions:
 * - mouse_availability (authenticated read-only availability snapshot)
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

/**
 * Maximum moderator-configured recovery duration for bracket events.
 *
 * Plain language for DEVS FOR DECADES:
 * PVP challenges remain restricted separately to 15, 30, or 60 minutes.
 * Moderator events may configure zero through one year. Zero disables recovery.
 */
const MOUSEFIGHT_MAX_EVENT_RECOVERY_MINUTES = 525600;

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
 * NARRRFS_MOUSEFIGHT_BATTLE_ELIXIR_ATOMIC_SETTLEMENT_V1
 *
 * Battle Elixir Alpha server contract.
 *
 * Plain language for DEVS FOR DECADES:
 * Discord may present and temporarily calculate Battle Elixir power, but the
 * authoritative MouseFight economy transaction independently verifies the
 * submitted Elixir metadata and real inventory before one item is consumed.
 *
 * Battle Elixirs affect temporary Fight Power only.
 * They never change League Power, League tier, permanent Genesis progression,
 * Lab progression, Genetic Items, Fight Recovery duration, DSPOINC, or SPOINC.
 */
const MOUSEFIGHT_BATTLE_ELIXIR_VERSION =
    'mousefight_battle_elixir_alpha_v1';

const MOUSEFIGHT_BATTLE_ELIXIR_USAGE_REASON_PREFIX =
    'mousefight_battle_elixir';

const MOUSEFIGHT_BATTLE_ELIXIR_APPROVED_BY =
    'mousefight_system';

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
 * Return the configured default MouseFight recovery duration.
 *
 * Plain language for DEVS FOR DECADES:
 * The duration is stored in tbl_mousefight_settings so administrators can
 * change it without editing PHP or Discord bot source code.
 *
 * New fights snapshot this value into tbl_mousefights.recovery_minutes.
 * Existing open fights can therefore keep the duration they were created with.
 */
function mousefight_economy_get_default_recovery_minutes(
    SQLite3 $db
): int {
    $setting = mousefight_economy_fetch_one(
        $db,
        "
        SELECT setting_value
        FROM tbl_mousefight_settings
        WHERE setting_key = 'default_recovery_minutes'
        LIMIT 1
        "
    );

    return mousefight_economy_integer(
        $setting['setting_value'] ?? 30,
        0,
        1440
    );
}

/**
 * Return the recovery duration configured for one persisted fight.
 *
 * Plain language for DEVS FOR DECADES:
 * A non-null per-fight value is authoritative. NULL means the fight was
 * created before recovery configuration existed, so the current database
 * default is used as a safe compatibility fallback.
 */
function mousefight_economy_resolve_fight_recovery_minutes(
    SQLite3 $db,
    array $fight
): int {
    if (
        array_key_exists('recovery_minutes', $fight) &&
        $fight['recovery_minutes'] !== null &&
        $fight['recovery_minutes'] !== ''
    ) {
        return mousefight_economy_integer(
            $fight['recovery_minutes'],
            0,
            MOUSEFIGHT_MAX_EVENT_RECOVERY_MINUTES
        );
    }

    return mousefight_economy_get_default_recovery_minutes($db);
}

/**
 * Return an active recovery row for one Genesis mouse.
 *
 * Expired rows remain preserved as audit history but do not block the mouse.
 */
function mousefight_economy_get_active_mouse_recovery(
    SQLite3 $db,
    string $tokenId,
    string $collection
): ?array {
    return mousefight_economy_fetch_one(
        $db,
        "
        SELECT
            cooldown_id,
            token_id,
            collection,
            user_id,
            fight_id,
            cooldown_minutes,
            cooldown_reason,
            started_at,
            expires_at,
            status
        FROM tbl_mousefight_mouse_cooldowns
        WHERE token_id = ?
          AND collection = ?
          AND status = 'active'
          AND datetime(expires_at) > datetime('now')
        ORDER BY datetime(expires_at) DESC, cooldown_id DESC
        LIMIT 1
        ",
        [
            $tokenId,
            $collection
        ]
    );
}

/**
 * Return a waiting or active fight that already reserves one Genesis mouse.
 *
 * Plain language for DEVS FOR DECADES:
 * Reserved and recovering are separate states. A mouse is reserved when its
 * participant row belongs to another waiting or active fight.
 *
 * The optional excluded Fight ID allows a mouse already registered in the
 * current fight to pass checks performed during that same fight transaction.
 */
function mousefight_economy_get_mouse_reservation(
    SQLite3 $db,
    string $tokenId,
    string $collection,
    string $excludedFightId = ''
): ?array {
    $sql = "
        SELECT
            p.fight_id,
            p.user_id,
            p.token_id,
            p.collection,
            f.status,
            f.mode,
            f.title
        FROM tbl_mousefight_participants p
        INNER JOIN tbl_mousefights f
            ON f.fight_id = p.fight_id
        WHERE p.token_id = ?
          AND p.collection = ?
          AND f.status IN ('waiting', 'active')
    ";

    $values = [
        $tokenId,
        $collection
    ];

    if ($excludedFightId !== '') {
        $sql .= "
          AND p.fight_id <> ?
        ";

        $values[] = $excludedFightId;
    }

    $sql .= "
        ORDER BY p.joined_at DESC
        LIMIT 1
    ";

    return mousefight_economy_fetch_one(
        $db,
        $sql,
        $values
    );
}

/**
 * Reject a Genesis mouse that is recovering or reserved elsewhere.
 *
 * Plain language for DEVS FOR DECADES:
 * This helper must run inside the same BEGIN IMMEDIATE transaction as any
 * participant, burn, or escrow write. That prevents fast duplicate actions
 * from bypassing recovery or entering one mouse into two fights.
 */
function mousefight_economy_assert_mouse_available(
    SQLite3 $db,
    array $participant,
    string $excludedFightId = ''
): void {
    $tokenId = (string)$participant['token_id'];
    $collection = (string)$participant['collection'];

    $activeRecovery =
        mousefight_economy_get_active_mouse_recovery(
            $db,
            $tokenId,
            $collection
        );

    if ($activeRecovery) {
        $expiresAt = (string)(
            $activeRecovery['expires_at'] ?? ''
        );

        throw new RuntimeException(
            'This Genesis mouse is recovering from its last MouseFight' .
            ($expiresAt !== ''
                ? " until {$expiresAt} UTC."
                : '.')
        );
    }

    $reservation =
        mousefight_economy_get_mouse_reservation(
            $db,
            $tokenId,
            $collection,
            $excludedFightId
        );

    if ($reservation) {
        throw new RuntimeException(
            'This Genesis mouse is already reserved in another waiting or active MouseFight.'
        );
    }
}

/**
 * Return one read-only availability snapshot for a Genesis mouse.
 *
 * Plain language for DEVS FOR DECADES:
 * Matchmaking needs to avoid advertising a recovering or already-reserved
 * Genesis mouse as available while a player is searching for a League fight.
 *
 * This action intentionally reuses the same recovery and reservation SELECT
 * helpers used by the authoritative MouseFight economy transaction.
 *
 * It is an early UX guard only. It does not reserve the NFT and it never
 * replaces mousefight_economy_assert_mouse_available(), which must still run
 * inside the real PVP/event BEGIN IMMEDIATE transaction before any participant,
 * burn, escrow, settlement, or Recovery write.
 *
 * This function performs SELECTs only. It never:
 * - inserts or updates a participant;
 * - creates or clears Fight Recovery;
 * - moves DSPOINC / SPOINC;
 * - creates/refunds stakes;
 * - settles a fight;
 * - changes Genesis/Lab/ownership/Genetic Item state.
 */
function mousefight_economy_mouse_availability(
    SQLite3 $db,
    array $data
): array {
    $tokenId = trim(
        (string)($data['token_id'] ?? '')
    );

    $collection = strtolower(
        trim(
            (string)($data['collection'] ?? 'genesis')
        )
    );

    if ($tokenId === '') {
        throw new RuntimeException(
            'token_id is required for MouseFight availability.'
        );
    }

    if ($collection === '') {
        $collection = 'genesis';
    }

    if ($collection !== 'genesis') {
        throw new RuntimeException(
            'MouseFight availability currently supports Genesis collection only.'
        );
    }

    $activeRecovery =
        mousefight_economy_get_active_mouse_recovery(
            $db,
            $tokenId,
            $collection
        );

    $reservation =
        mousefight_economy_get_mouse_reservation(
            $db,
            $tokenId,
            $collection
        );

    $blockedReasons = [];

    if ($activeRecovery) {
        $blockedReasons[] = 'recovery';
    }

    if ($reservation) {
        $blockedReasons[] = 'reservation';
    }

    $recoveryData = null;

    if ($activeRecovery) {
        $recoveryData = [
            'fight_id' =>
                (string)($activeRecovery['fight_id'] ?? ''),
            'cooldown_minutes' =>
                (int)($activeRecovery['cooldown_minutes'] ?? 0),
            'cooldown_reason' =>
                (string)($activeRecovery['cooldown_reason'] ?? ''),
            'started_at' =>
                (string)($activeRecovery['started_at'] ?? ''),
            'expires_at' =>
                (string)($activeRecovery['expires_at'] ?? ''),
            'status' =>
                (string)($activeRecovery['status'] ?? '')
        ];
    }

    $reservationData = null;

    if ($reservation) {
        $reservationData = [
            'fight_id' =>
                (string)($reservation['fight_id'] ?? ''),
            'status' =>
                (string)($reservation['status'] ?? ''),
            'mode' =>
                (string)($reservation['mode'] ?? ''),
            'title' =>
                (string)($reservation['title'] ?? '')
        ];
    }

    return [
        'token_id' => $tokenId,
        'collection' => $collection,
        'available' => count($blockedReasons) === 0,
        'blocked_reason' =>
            $blockedReasons[0] ?? null,
        'blocked_reasons' =>
            $blockedReasons,
        'active_recovery' =>
            $recoveryData,
        'reservation' =>
            $reservationData,
        'authority' =>
            'read_only_ux_snapshot',
        'transaction_recheck_required' =>
            true
    ];
}


/**
 * Create one persistent recovery row for a mouse that completed a real fight.
 *
 * Plain language for DEVS FOR DECADES:
 * Recovery belongs to token_id + collection, not permanently to one Discord
 * owner. The user_id stored here records who controlled the mouse during this
 * completed fight and remains part of the historical audit.
 *
 * The database UNIQUE constraint on fight_id + token_id + collection makes
 * this operation idempotent. Retrying the same completed fight cannot create
 * a second recovery period for the same mouse.
 *
 * A configured duration of zero disables recovery for that fight and creates
 * no cooldown row.
 */
function mousefight_economy_create_mouse_recovery(
    SQLite3 $db,
    array $fight,
    array $participant,
    string $cooldownReason = 'completed_match'
): ?array {
    $fightId = trim((string)($fight['fight_id'] ?? ''));
    $tokenId = trim((string)($participant['token_id'] ?? ''));
    $collection = trim(
        (string)($participant['collection'] ?? 'genesis')
    );
    $userId = trim((string)($participant['user_id'] ?? ''));

    if ($fightId === '') {
        throw new RuntimeException(
            'Cannot create MouseFight recovery without a Fight ID.'
        );
    }

    if ($tokenId === '' || $collection === '') {
        throw new RuntimeException(
            'Cannot create MouseFight recovery without mouse identity.'
        );
    }

    if ($userId === '') {
        throw new RuntimeException(
            'Cannot create MouseFight recovery without participant identity.'
        );
    }

    $recoveryMinutes =
        mousefight_economy_resolve_fight_recovery_minutes(
            $db,
            $fight
        );

    if ($recoveryMinutes <= 0) {
        return null;
    }

    mousefight_economy_write(
        $db,
        "
        INSERT OR IGNORE INTO tbl_mousefight_mouse_cooldowns (
            token_id,
            collection,
            user_id,
            fight_id,
            cooldown_minutes,
            cooldown_reason,
            started_at,
            expires_at,
            status,
            created_at
        ) VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            datetime('now'),
            datetime('now', '+' || ? || ' minutes'),
            'active',
            datetime('now')
        )
        ",
        [
            $tokenId,
            $collection,
            $userId,
            $fightId,
            $recoveryMinutes,
            $cooldownReason,
            $recoveryMinutes
        ]
    );

    $recovery = mousefight_economy_fetch_one(
        $db,
        "
        SELECT
            cooldown_id,
            token_id,
            collection,
            user_id,
            fight_id,
            cooldown_minutes,
            cooldown_reason,
            started_at,
            expires_at,
            status,
            created_at
        FROM tbl_mousefight_mouse_cooldowns
        WHERE fight_id = ?
          AND token_id = ?
          AND collection = ?
        LIMIT 1
        ",
        [
            $fightId,
            $tokenId,
            $collection
        ]
    );

    if (!$recovery) {
        throw new RuntimeException(
            'MouseFight recovery row could not be confirmed.'
        );
    }

    return $recovery;
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
 * Return the fixed Alpha definition for one Battle Elixir item.
 *
 * Store metadata is deliberately not used to decide combat power.
 */
function mousefight_economy_get_battle_elixir_definition(
    int $itemId
): ?array {
    switch ($itemId) {
        case 33:
            return [
                'item_id' => 33,
                'item_name' => 'Green Elixir',
                'boost_rate' => 0.05,
                'boost_percent' => 5
            ];

        case 34:
            return [
                'item_id' => 34,
                'item_name' => 'Blue Elixir',
                'boost_rate' => 0.10,
                'boost_percent' => 10
            ];

        case 35:
            return [
                'item_id' => 35,
                'item_name' => 'Red Elixir',
                'boost_rate' => 0.15,
                'boost_percent' => 15
            ];

        default:
            return null;
    }
}

/**
 * Require one integer value from the persisted Battle Elixir snapshot.
 *
 * This is intentionally stricter than mousefight_economy_integer().
 * Missing or malformed combat proof must fail instead of silently becoming 0.
 */
function mousefight_economy_battle_elixir_required_integer(
    $value,
    string $label,
    int $maximum = 100000000
): int {
    if (is_int($value)) {
        $numericValue = $value;
    } elseif (
        is_float($value) &&
        floor($value) === $value
    ) {
        $numericValue = (int)$value;
    } elseif (
        is_string($value) &&
        preg_match('/^\d+$/', $value)
    ) {
        $numericValue = (int)$value;
    } else {
        throw new RuntimeException(
            'Invalid Battle Elixir integer: ' . $label
        );
    }

    if (
        $numericValue < 0 ||
        $numericValue > $maximum
    ) {
        throw new RuntimeException(
            'Battle Elixir integer is outside the allowed range: ' .
            $label
        );
    }

    return $numericValue;
}

/**
 * Decode one participant's persisted MouseFight player snapshot.
 *
 * Missing snapshot_json is accepted for older PVP challenges created before
 * Battle Elixirs existed. A non-empty but invalid snapshot is rejected.
 */
function mousefight_economy_decode_participant_snapshot(
    array $participant,
    string $participantRole
): array {
    $snapshotJson =
        trim(
            (string)(
                $participant['snapshot_json'] ??
                ''
            )
        );

    if ($snapshotJson === '') {
        return [];
    }

    $snapshot =
        json_decode(
            $snapshotJson,
            true
        );

    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_array($snapshot)
    ) {
        throw new RuntimeException(
            'Invalid ' .
            $participantRole .
            ' MouseFight snapshot JSON.'
        );
    }

    return $snapshot;
}

/**
 * Resolve and verify one participant's Battle Elixir intent.
 *
 * Plain language:
 * The API does not trust Discord's +5/+10/+15 calculation by itself.
 * It recomputes the allowed bonus from the recorded pre-Elixir Fight Power and
 * Genetic Item support, then proves that the final submitted Fight Power is
 * exactly the Alpha result.
 *
 * Missing Battle Elixir metadata means "no Elixir" for backwards compatibility.
 */
function mousefight_economy_resolve_battle_elixir(
    array $participant,
    string $participantRole
): array {
    $snapshot =
        mousefight_economy_decode_participant_snapshot(
            $participant,
            $participantRole
        );

    $mouseProfile =
        $snapshot['mouseProfile'] ??
        null;

    if (!is_array($mouseProfile)) {
        return [
            'selected' => false,
            'role' => $participantRole
        ];
    }

    if (
        !array_key_exists(
            'battleElixirSelection',
            $mouseProfile
        )
    ) {
        return [
            'selected' => false,
            'role' => $participantRole
        ];
    }

    $selection =
        $mouseProfile['battleElixirSelection'];

    if (!is_array($selection)) {
        throw new RuntimeException(
            'Invalid ' .
            $participantRole .
            ' Battle Elixir selection.'
        );
    }

    $version =
        trim(
            (string)(
                $selection['version'] ??
                ''
            )
        );

    if (
        $version !==
        MOUSEFIGHT_BATTLE_ELIXIR_VERSION
    ) {
        throw new RuntimeException(
            'Unsupported ' .
            $participantRole .
            ' Battle Elixir version.'
        );
    }

    if (
        !mousefight_economy_boolean(
            $selection['resolved'] ??
            false
        )
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir selection is not resolved.'
        );
    }

    $selected =
        mousefight_economy_boolean(
            $selection['selected'] ??
            false
        );

    $pendingConsumption =
        mousefight_economy_boolean(
            $selection['pendingConsumption'] ??
            false
        );

    if (!$selected) {
        if ($pendingConsumption) {
            throw new RuntimeException(
                'The ' .
                $participantRole .
                ' no-Elixir selection has invalid pending consumption.'
            );
        }

        if (
            array_key_exists(
                'battleElixir',
                $mouseProfile
            ) &&
            $mouseProfile['battleElixir'] !== null
        ) {
            throw new RuntimeException(
                'The ' .
                $participantRole .
                ' no-Elixir selection contains unexpected boost metadata.'
            );
        }

        return [
            'selected' => false,
            'role' => $participantRole,
            'version' => $version
        ];
    }

    if (!$pendingConsumption) {
        throw new RuntimeException(
            'The selected ' .
            $participantRole .
            ' Battle Elixir is not marked for consumption.'
        );
    }

    $itemId =
        mousefight_economy_battle_elixir_required_integer(
            $selection['itemId'] ??
            null,
            $participantRole . '.battleElixirSelection.itemId',
            100000
        );

    $definition =
        mousefight_economy_get_battle_elixir_definition(
            $itemId
        );

    if (!$definition) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' selected an unsupported Battle Elixir.'
        );
    }

    $battleElixir =
        $mouseProfile['battleElixir'] ??
        null;

    if (!is_array($battleElixir)) {
        throw new RuntimeException(
            'The selected ' .
            $participantRole .
            ' Battle Elixir calculation is missing.'
        );
    }

    if (
        trim(
            (string)(
                $battleElixir['version'] ??
                ''
            )
        ) !==
        MOUSEFIGHT_BATTLE_ELIXIR_VERSION
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir calculation version is invalid.'
        );
    }

    $battleItemId =
        mousefight_economy_battle_elixir_required_integer(
            $battleElixir['itemId'] ??
            null,
            $participantRole . '.battleElixir.itemId',
            100000
        );

    if ($battleItemId !== $itemId) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir item does not match the selection.'
        );
    }

    if (
        !mousefight_economy_boolean(
            $battleElixir['pendingConsumption'] ??
            false
        )
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir calculation is not pending consumption.'
        );
    }

    if (
        mousefight_economy_boolean(
            $battleElixir['consumed'] ??
            false
        )
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir snapshot is already marked consumed.'
        );
    }

    $profilePower =
        $mouseProfile['power'] ??
        null;

    if (!is_array($profilePower)) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir power snapshot is missing.'
        );
    }

    $participantFinalPower =
        mousefight_economy_battle_elixir_required_integer(
            $participant['mouse_warrior_power'] ??
            null,
            $participantRole . '.mouse_warrior_power'
        );

    $participantGeneticPower =
        mousefight_economy_battle_elixir_required_integer(
            $participant['genetic_support_power'] ??
            null,
            $participantRole . '.genetic_support_power'
        );

    $snapshotFinalPower =
        mousefight_economy_battle_elixir_required_integer(
            $profilePower['mouseWarriorPower'] ??
            null,
            $participantRole . '.mouseProfile.power.mouseWarriorPower'
        );

    $snapshotGeneticPower =
        mousefight_economy_battle_elixir_required_integer(
            $profilePower['geneticSupportPower'] ??
            null,
            $participantRole . '.mouseProfile.power.geneticSupportPower'
        );

    if (
        $snapshotFinalPower !==
        $participantFinalPower
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir Fight Power does not match the participant payload.'
        );
    }

    if (
        $snapshotGeneticPower !==
        $participantGeneticPower
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir Genetic Item support does not match the participant payload.'
        );
    }

    $originalFightPower =
        mousefight_economy_battle_elixir_required_integer(
            $battleElixir['originalFightPower'] ??
            null,
            $participantRole . '.battleElixir.originalFightPower'
        );

    $recordedGeneticPower =
        mousefight_economy_battle_elixir_required_integer(
            $battleElixir['geneticSupportPower'] ??
            null,
            $participantRole . '.battleElixir.geneticSupportPower'
        );

    $recordedBaseGenesisPower =
        mousefight_economy_battle_elixir_required_integer(
            $battleElixir['baseGenesisPower'] ??
            null,
            $participantRole . '.battleElixir.baseGenesisPower'
        );

    $recordedBonusPower =
        mousefight_economy_battle_elixir_required_integer(
            $battleElixir['bonusPower'] ??
            null,
            $participantRole . '.battleElixir.bonusPower'
        );

    $recordedFinalPower =
        mousefight_economy_battle_elixir_required_integer(
            $battleElixir['finalFightPower'] ??
            null,
            $participantRole . '.battleElixir.finalFightPower'
        );

    $recordedBoostPercent =
        mousefight_economy_battle_elixir_required_integer(
            $battleElixir['boostPercent'] ??
            null,
            $participantRole . '.battleElixir.boostPercent',
            100
        );

    $recordedBoostRate =
        $battleElixir['boostRate'] ??
        null;

    if (!is_numeric($recordedBoostRate)) {
        throw new RuntimeException(
            'Invalid ' .
            $participantRole .
            ' Battle Elixir boost rate.'
        );
    }

    if (
        abs(
            (float)$recordedBoostRate -
            (float)$definition['boost_rate']
        ) >
        0.000000001
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir boost rate is not approved.'
        );
    }

    if (
        $recordedBoostPercent !==
        (int)$definition['boost_percent']
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir boost percentage is not approved.'
        );
    }

    if (
        $recordedGeneticPower !==
        $participantGeneticPower
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir recorded Genetic Item support is inconsistent.'
        );
    }

    if (
        $originalFightPower <
        $participantGeneticPower
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' original Fight Power is below its Genetic Item support.'
        );
    }

    $expectedBaseGenesisPower =
        $originalFightPower -
        $participantGeneticPower;

    $expectedBonusPower =
        (int)round(
            $expectedBaseGenesisPower *
            (float)$definition['boost_rate'],
            0,
            PHP_ROUND_HALF_UP
        );

    $expectedFinalPower =
        $originalFightPower +
        $expectedBonusPower;

    if (
        $recordedBaseGenesisPower !==
        $expectedBaseGenesisPower
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir Genesis Base Power is invalid.'
        );
    }

    if (
        $recordedBonusPower !==
        $expectedBonusPower
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir bonus is invalid.'
        );
    }

    if (
        $recordedFinalPower !==
        $expectedFinalPower ||
        $participantFinalPower !==
        $expectedFinalPower
    ) {
        throw new RuntimeException(
            'The ' .
            $participantRole .
            ' Battle Elixir final Fight Power is invalid.'
        );
    }

    return [
        'selected' => true,
        'role' => $participantRole,
        'version' => $version,
        'user_id' => (string)(
            $participant['user_id'] ??
            ''
        ),
        'item_id' => $itemId,
        'item_name' => $definition['item_name'],
        'boost_rate' => $definition['boost_rate'],
        'boost_percent' => $definition['boost_percent'],
        'original_fight_power' => $originalFightPower,
        'base_genesis_power' => $expectedBaseGenesisPower,
        'genetic_support_power' => $participantGeneticPower,
        'bonus_power' => $expectedBonusPower,
        'final_fight_power' => $expectedFinalPower
    ];
}

/**
 * Revalidate one selected Battle Elixir against authoritative inventory.
 *
 * This function is read-only. Both fighters are prepared before either item is
 * consumed, so an unavailable second Elixir fails before the first burn.
 */
function mousefight_economy_prepare_battle_elixir_consumption(
    SQLite3 $db,
    string $fightId,
    array $participant,
    string $participantRole
): array {
    $selection =
        mousefight_economy_resolve_battle_elixir(
            $participant,
            $participantRole
        );

    if (!$selection['selected']) {
        return $selection;
    }

    $userId =
        trim(
            (string)(
                $selection['user_id'] ??
                ''
            )
        );

    if ($userId === '') {
        throw new RuntimeException(
            'Missing ' .
            $participantRole .
            ' user for Battle Elixir consumption.'
        );
    }

    $inventoryRow =
        mousefight_economy_fetch_one(
            $db,
            "
            SELECT
                inventory_id,
                user_id,
                item_id,
                quantity,
                last_used_at
            FROM tbl_user_inventory
            WHERE user_id = ?
              AND item_id = ?
              AND COALESCE(quantity, 0) > 0
            LIMIT 1
            ",
            [
                $userId,
                $selection['item_id']
            ]
        );

    if (!$inventoryRow) {
        throw new RuntimeException(
            $selection['item_name'] .
            ' is no longer available in the ' .
            $participantRole .
            ' inventory.'
        );
    }

    $quantityBefore =
        mousefight_economy_battle_elixir_required_integer(
            $inventoryRow['quantity'] ??
            null,
            $participantRole . '.inventory.quantity',
            100000000
        );

    if ($quantityBefore < 1) {
        throw new RuntimeException(
            $selection['item_name'] .
            ' is no longer available in the ' .
            $participantRole .
            ' inventory.'
        );
    }

    return array_merge(
        $selection,
        [
            'fight_id' => $fightId,
            'inventory_id' => (int)$inventoryRow['inventory_id'],
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore - 1
        ]
    );
}

/**
 * Require the immediately previous SQLite write to affect exactly one row.
 *
 * User inventory is protected state. SELECT changes() makes a missing or broad
 * write fail the entire MouseFight transaction instead of being accepted
 * silently.
 */
function mousefight_economy_require_single_change(
    SQLite3 $db,
    string $label
): void {
    $changeRow =
        mousefight_economy_fetch_one(
            $db,
            "SELECT changes() AS changed"
        );

    if (
        (int)($changeRow['changed'] ?? 0) !==
        1
    ) {
        throw new RuntimeException(
            $label .
            ' did not change exactly one row.'
        );
    }
}

/**
 * Consume one already-prepared Battle Elixir and record usage history.
 *
 * This must only run inside the existing BEGIN IMMEDIATE PVP settlement
 * transaction. Any later exception causes the shared request handler to
 * ROLLBACK this inventory write together with the complete fight settlement.
 */
function mousefight_economy_consume_prepared_battle_elixir(
    SQLite3 $db,
    array $prepared
): ?array {
    if (
        !mousefight_economy_boolean(
            $prepared['selected'] ??
            false
        )
    ) {
        return null;
    }

    $inventoryId =
        (int)(
            $prepared['inventory_id'] ??
            0
        );

    $userId =
        trim(
            (string)(
                $prepared['user_id'] ??
                ''
            )
        );

    $itemId =
        (int)(
            $prepared['item_id'] ??
            0
        );

    $quantityBefore =
        (int)(
            $prepared['quantity_before'] ??
            0
        );

    $quantityAfter =
        (int)(
            $prepared['quantity_after'] ??
            -1
        );

    if (
        $inventoryId < 1 ||
        $userId === '' ||
        $itemId < 1 ||
        $quantityBefore < 1 ||
        $quantityAfter !==
            ($quantityBefore - 1)
    ) {
        throw new RuntimeException(
            'Invalid prepared Battle Elixir inventory state.'
        );
    }

    if ($quantityAfter > 0) {
        mousefight_economy_write(
            $db,
            "
            UPDATE tbl_user_inventory
            SET
                quantity = ?,
                last_used_at = datetime('now')
            WHERE inventory_id = ?
              AND user_id = ?
              AND item_id = ?
              AND quantity = ?
            ",
            [
                $quantityAfter,
                $inventoryId,
                $userId,
                $itemId,
                $quantityBefore
            ]
        );

        mousefight_economy_require_single_change(
            $db,
            'Battle Elixir inventory decrement'
        );
    } else {
        mousefight_economy_write(
            $db,
            "
            DELETE FROM tbl_user_inventory
            WHERE inventory_id = ?
              AND user_id = ?
              AND item_id = ?
              AND quantity = 1
            ",
            [
                $inventoryId,
                $userId,
                $itemId
            ]
        );

        mousefight_economy_require_single_change(
            $db,
            'Battle Elixir final inventory removal'
        );
    }

    $usageReason =
        MOUSEFIGHT_BATTLE_ELIXIR_USAGE_REASON_PREFIX .
        ':' .
        (string)$prepared['fight_id'] .
        ':' .
        (string)$prepared['role'];

    $usageId =
        mousefight_economy_write(
            $db,
            "
            INSERT INTO tbl_item_usage_history (
                user_id,
                item_id,
                item_name,
                quantity,
                reason,
                status,
                approved_by,
                used_at,
                approved_at
            ) VALUES (
                ?,
                ?,
                ?,
                1,
                ?,
                'approved',
                ?,
                datetime('now'),
                datetime('now')
            )
            ",
            [
                $userId,
                $itemId,
                (string)$prepared['item_name'],
                $usageReason,
                MOUSEFIGHT_BATTLE_ELIXIR_APPROVED_BY
            ]
        );

    mousefight_economy_require_single_change(
        $db,
        'Battle Elixir usage-history insert'
    );

    return [
        'selected' => true,
        'role' => (string)$prepared['role'],
        'user_id' => $userId,
        'item_id' => $itemId,
        'item_name' => (string)$prepared['item_name'],
        'boost_percent' => (int)$prepared['boost_percent'],
        'bonus_power' => (int)$prepared['bonus_power'],
        'final_fight_power' => (int)$prepared['final_fight_power'],
        'quantity_before' => $quantityBefore,
        'remaining_inventory_quantity' => $quantityAfter,
        'usage_id' => $usageId
    ];
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

        /**
     * Authoritatively verify that this Genesis mouse is available.
     *
     * Plain language for DEVS FOR DECADES:
     * The same-event duplicate checks above provide the clearest message for
     * users already registered here. This shared check then blocks recovery
     * and reservations in every other waiting or active MouseFight.
     *
     * It runs before capacity, balance, burn, and participant writes so a
     * rejected mouse can never lose DSPOINC.
     */
    mousefight_economy_assert_mouse_available(
        $db,
        $participant,
        $fightId
    );

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
            wager_dspoinc,
            recovery_minutes
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

    /**
     * Validate and snapshot the challenger-selected PVP recovery duration.
     *
     * Plain language for DEVS FOR DECADES:
     * Discord allows only the published 15, 30, or 60 minute PVP choices.
     * This backend validates the value again because browser, bot, or network
     * input must never be trusted as authoritative without server validation.
     *
     * The accepted value is copied into tbl_mousefights so later configuration
     * changes cannot alter the recovery terms of an already-open challenge.
     */
    $recoveryMinutes = mousefight_economy_integer(
        $fightInput['recovery_minutes'] ?? 0,
        0,
        60
    );

    if (!in_array($recoveryMinutes, [15, 30, 60], true)) {
        throw new RuntimeException(
            'Invalid MouseFight PVP recovery duration. Choose 15, 30, or 60 minutes.'
        );
    }

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

    /**
     * Authoritatively verify that the challenger mouse can enter a new PVP.
     *
     * Plain language for DEVS FOR DECADES:
     * No fight row exists yet for this new challenge, so there is no Fight ID
     * to exclude. This blocks both active recovery and any reservation in an
     * existing waiting or active MouseFight before escrow can be created.
     */
    mousefight_economy_assert_mouse_available(
        $db,
        $challenger
    );

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
            buy_in_dspoinc,
            recovery_minutes
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, NULL, NULL, ?,
            ?, 0, NULL, 0,
            ?, ?, ?, ?, ?,
            2, ?, NULL, ?,
            datetime('now'), ?, 0, ?
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
            $metadataJson !== '' ? $metadataJson : null,
            $recoveryMinutes
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
        "
        SELECT
            id,
            user_id,
            token_id,
            collection,
            mouse_warrior_power,
            genetic_support_power,
            snapshot_json
        FROM tbl_mousefight_participants
        WHERE fight_id = ?
          AND user_id = ?
        LIMIT 1
        ",
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

        /**
     * Authoritatively verify that the opponent mouse can enter this PVP.
     *
     * Plain language for DEVS FOR DECADES:
     * Same-fight duplicate checks above keep their specific player messages.
     * This shared check then blocks active recovery and reservations in every
     * other waiting or active MouseFight.
     *
     * The current Fight ID is excluded because the challenger participant
     * legitimately reserves a different mouse inside this same PVP challenge.
     *
     * This check runs before the opponent stake, participant insertion,
     * settlement, payout, or final fight-state write.
     */
    mousefight_economy_assert_mouse_available(
        $db,
        $opponent,
        $fightId
    );

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

    /**
     * Battle Elixir atomic settlement.
     *
     * Both selections and both inventory rows are validated before either item
     * is consumed. Consumption then remains inside the same BEGIN IMMEDIATE
     * transaction as opponent stake, participant persistence, payout, finished
     * fight state, and completed-PVP Fight Recovery.
     *
     * Any exception after this point rolls the complete transaction back.
     */
    $challengerBattleElixirPrepared =
        mousefight_economy_prepare_battle_elixir_consumption(
            $db,
            $fightId,
            $challengerParticipant,
            'challenger'
        );

    $opponentBattleElixirPrepared =
        mousefight_economy_prepare_battle_elixir_consumption(
            $db,
            $fightId,
            $opponent,
            'opponent'
        );

    $challengerBattleElixirConsumption =
        mousefight_economy_consume_prepared_battle_elixir(
            $db,
            $challengerBattleElixirPrepared
        );

    $opponentBattleElixirConsumption =
        mousefight_economy_consume_prepared_battle_elixir(
            $db,
            $opponentBattleElixirPrepared
        );

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

        /**
     * Start recovery for both mice after the real PVP fight is finalized.
     *
     * Plain language for DEVS FOR DECADES:
     * Both the winner and loser completed a real MouseFight, so both mice enter
     * recovery. The cooldown belongs to token_id + collection and uses the
     * duration snapshotted when this PVP challenge was created.
     *
     * These writes remain inside the same SQLite transaction as the opponent
     * stake, payout, settlement, and finished fight state. Any failure rolls
     * back the complete acceptance instead of leaving partial economy results.
     */
    $challengerRecovery =
        mousefight_economy_create_mouse_recovery(
            $db,
            $fight,
            $challengerParticipant,
            'completed_pvp'
        );

    $opponentRecovery =
        mousefight_economy_create_mouse_recovery(
            $db,
            $fight,
            $opponent,
            'completed_pvp'
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
        'payout_adjustment_id' => $payoutAdjustmentId,
        'battle_elixirs' => [
            'challenger' =>
                $challengerBattleElixirConsumption,
            'opponent' =>
                $opponentBattleElixirConsumption
        ],
        'challenger_recovery' => $challengerRecovery,
        'opponent_recovery' => $opponentRecovery
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

/**
 * Start recovery for every Genesis mouse that completed a real event match.
 *
 * Plain language for DEVS FOR DECADES:
 * The authoritative source is tbl_mousefight_rounds, not a Discord-supplied
 * fighter list. Real matches persist attacker and defender identities.
 * Pure bracket byes persist no combat rounds and therefore receive no recovery.
 *
 * The recovery helper is idempotent through the unique
 * fight_id + token_id + collection identity. Retrying this action cannot create
 * duplicate recovery rows for the same completed event and Genesis mouse.
 */
function mousefight_economy_event_complete_recovery(
    SQLite3 $db,
    array $data
): array {
    $fightId = mousefight_economy_required_string(
        $data,
        'fight_id',
        100
    );

    $fight = mousefight_economy_fetch_one(
        $db,
        "
        SELECT
            fight_id,
            status,
            mode,
            recovery_minutes
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
        MOUSEFIGHT_FINISHED_STATUS
    ) {
        throw new RuntimeException(
            'MouseFight event recovery requires a finished event.'
        );
    }

    $recoveryMinutes =
        mousefight_economy_resolve_fight_recovery_minutes(
            $db,
            $fight
        );

    if ($recoveryMinutes <= 0) {
        return [
            'fight_id' => $fightId,
            'status' => MOUSEFIGHT_FINISHED_STATUS,
            'recovery_minutes' => 0,
            'fighter_count' => 0,
            'recovery_count' => 0,
            'recoveries' => []
        ];
    }

    /**
     * Load unique participants that appear as either attacker or defender in
     * at least one persisted combat round.
     *
     * Plain language:
     * A bye has no attacker/defender round row, so a mouse that received only
     * a bye is naturally excluded without adding special bracket assumptions.
     */
    $fighters = mousefight_economy_fetch_all(
        $db,
        "
        SELECT DISTINCT
            participant.user_id,
            participant.token_id,
            participant.collection
        FROM tbl_mousefight_participants participant
        INNER JOIN (
            SELECT
                attacker_user_id AS user_id,
                attacker_token_id AS token_id
            FROM tbl_mousefight_rounds
            WHERE fight_id = ?

            UNION

            SELECT
                defender_user_id AS user_id,
                defender_token_id AS token_id
            FROM tbl_mousefight_rounds
            WHERE fight_id = ?
        ) completed_fighter
            ON completed_fighter.user_id = participant.user_id
           AND completed_fighter.token_id = participant.token_id
        WHERE participant.fight_id = ?
          AND participant.token_id IS NOT NULL
          AND TRIM(participant.token_id) <> ''
        ORDER BY participant.id ASC
        ",
        [
            $fightId,
            $fightId,
            $fightId
        ]
    );

    if (count($fighters) === 0) {
        throw new RuntimeException(
            'No persisted real MouseFight event rounds were found for recovery.'
        );
    }

    $recoveries = [];

    foreach ($fighters as $fighter) {
        $recovery =
            mousefight_economy_create_mouse_recovery(
                $db,
                $fight,
                $fighter,
                'completed_event_match'
            );

        if ($recovery !== null) {
            $recoveries[] = $recovery;
        }
    }

    return [
        'fight_id' => $fightId,
        'status' => MOUSEFIGHT_FINISHED_STATUS,
        'recovery_minutes' => $recoveryMinutes,
        'fighter_count' => count($fighters),
        'recovery_count' => count($recoveries),
        'recoveries' => $recoveries
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
            'mouse_availability',
            'event_join',
            'event_cancel',
            'event_complete_recovery',
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

    /**
     * Matchmaking availability is authenticated but strictly read-only.
     *
     * DEVS FOR DECADES:
     * Handle this action before BEGIN IMMEDIATE so routine League searches do
     * not acquire the SQLite write lock used by protected economy operations.
     *
     * query_only is defense-in-depth: SQLite will reject an accidental write
     * if this read-only branch is changed incorrectly in the future.
     */
    if ($action === 'mouse_availability') {
        if (!$db->exec('PRAGMA query_only = ON')) {
            throw new RuntimeException(
                'Could not enable read-only MouseFight availability mode.'
            );
        }

        $result =
            mousefight_economy_mouse_availability(
                $db,
                $data
            );

        $db->close();
        $db = null;

        mousefight_economy_output([
            'success' => true,
            'action' => $action,
            'data' => $result
        ]);
    }

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

        case 'event_complete_recovery':
            $result = mousefight_economy_event_complete_recovery(
                $db,
                $data
            );
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