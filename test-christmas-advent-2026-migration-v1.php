<?php
/**
 * Disposable-only regression suite for the Christmas Advent 2026 marker migration.
 *
 * This test creates fresh temporary SQLite files and never opens the local
 * application database. It verifies the migration contract consumed by the
 * read-only V2.2 status helper.
 */
declare(strict_types=1);

require_once __DIR__ . '/api/lib/christmas-advent-2026.php';

function christmas26_migration_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

function christmas26_migration_database_path(): string
{
    $path = tempnam(sys_get_temp_dir(), 'christmas26-migration-');
    if ($path === false) {
        throw new RuntimeException('Unable to allocate disposable SQLite path');
    }
    return $path;
}

function christmas26_migration_open(string $path): SQLite3
{
    $database = new SQLite3($path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $database->enableExceptions(true);
    $database->exec('PRAGMA foreign_keys = ON');
    christmas26_migration_assert(
        (int)$database->querySingle('PRAGMA foreign_keys') === 1,
        'foreign-key enforcement is enabled before disposable transactions'
    );

    $database->exec('CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY, username TEXT NOT NULL)');
    $database->exec("CREATE TABLE tbl_user_scores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT,
        game TEXT,
        score INTEGER,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        source TEXT DEFAULT 'legacy',
        game_type TEXT,
        season TEXT DEFAULT 'season_2',
        created_at DATETIME
    )");
    $database->exec("CREATE TABLE tbl_score_adjustments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT NOT NULL,
        admin_id TEXT NOT NULL,
        amount INTEGER NOT NULL,
        action TEXT NOT NULL CHECK(action IN ('add', 'remove', 'set')),
        reason TEXT NOT NULL DEFAULT 'admin_adjustment',
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        quest_id INTEGER,
        FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id),
        FOREIGN KEY (admin_id) REFERENCES tbl_users(discord_id)
    )");
    return $database;
}

function christmas26_migration_expect_rejection(SQLite3 $database, string $sql, string $label): void
{
    $rejected = false;
    try {
        $database->exec($sql);
    } catch (Throwable) {
        $rejected = true;
    }
    christmas26_migration_assert($rejected, $label);
}

function christmas26_migration_insert_valid_references(SQLite3 $database, string $userId, int $scoreId, int $adjustmentId): void
{
    $database->exec("INSERT INTO tbl_users (discord_id, username) VALUES ('$userId', 'Disposable user')");
    $database->exec("INSERT INTO tbl_user_scores (id, user_id, game, score, source) VALUES ($scoreId, '$userId', 'christmas_advent_2026', 5000, 'christmas_advent_2026')");
    $database->exec("INSERT INTO tbl_score_adjustments (id, user_id, admin_id, amount, action, reason) VALUES ($adjustmentId, '$userId', '$userId', 5000, 'add', 'christmas_advent_2026')");
}

$migrationPath = __DIR__ . '/db/migrations/create_christmas_advent_2026_claims_v1.sql';
$migrationSql = file_get_contents($migrationPath);
if (!is_string($migrationSql) || $migrationSql === '') {
    throw new RuntimeException('Migration artifact is unavailable');
}

$databasePath = christmas26_migration_database_path();
$database = null;
try {
    $database = christmas26_migration_open($databasePath);
    $parentsBefore = [];
    foreach (['tbl_users', 'tbl_user_scores', 'tbl_score_adjustments'] as $table) {
        $parentsBefore[$table] = (string)$database->querySingle(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '$table'"
        );
    }

    $database->exec($migrationSql);
    christmas26_migration_assert(
        (int)$database->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'tbl_christmas_advent_2026_claims'") === 1,
        'fresh migration creates only the Christmas claims table'
    );
    christmas26_migration_assert(
        (int)$database->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type = 'index' AND name = 'idx_christmas_advent_2026_claims_event_date'") === 1,
        'migration creates the event/date reconciliation index'
    );
    foreach ($parentsBefore as $table => $sql) {
        christmas26_migration_assert(
            (string)$database->querySingle("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '$table'") === $sql,
            'migration leaves existing parent table unchanged: ' . $table
        );
    }

    christmas26_migration_assert(christmas26_claims_schema_error($database) === null, 'migration satisfies the V2.2 GET schema validator');

    $foreignKeys = [];
    $foreignKeyResult = $database->query('PRAGMA foreign_key_list(tbl_christmas_advent_2026_claims)');
    while (($row = $foreignKeyResult->fetchArray(SQLITE3_ASSOC)) !== false) {
        $foreignKeys[] = [$row['table'], $row['from'], $row['to']];
    }
    sort($foreignKeys);
    christmas26_migration_assert($foreignKeys === [
        ['tbl_score_adjustments', 'adjustment_id', 'id'],
        ['tbl_user_scores', 'score_id', 'id'],
        ['tbl_users', 'actor_user_id', 'discord_id'],
        ['tbl_users', 'user_id', 'discord_id'],
    ], 'claims table has the four required parent foreign keys');

    christmas26_migration_insert_valid_references($database, '111111111111111111', 101, 201);
    $database->exec("INSERT INTO tbl_christmas_advent_2026_claims
        (event_key, user_id, calendar_date, day_number, reward_dspoinc, actor_user_id, score_id, adjustment_id)
        VALUES ('christmas_advent_2026', '111111111111111111', '2026-12-01', 1, 5000, '111111111111111111', 101, 201)");
    christmas26_migration_assert(
        (int)$database->querySingle('SELECT claim_id FROM tbl_christmas_advent_2026_claims WHERE score_id = 101') > 0,
        'valid claim receives a durable positive claim receipt'
    );
    christmas26_migration_assert(
        trim((string)$database->querySingle('SELECT claimed_at FROM tbl_christmas_advent_2026_claims WHERE score_id = 101')) !== '',
        'claimed_at is generated by SQLite'
    );

    christmas26_migration_insert_valid_references($database, '222222222222222222', 102, 202);
    $database->exec("INSERT INTO tbl_christmas_advent_2026_claims
        (event_key, user_id, calendar_date, day_number, reward_dspoinc, actor_user_id, score_id, adjustment_id)
        VALUES ('christmas_advent_2026', '222222222222222222', '2026-12-01', 1, 5000, '222222222222222222', 102, 202)");
    christmas26_migration_assert(
        (int)$database->querySingle("SELECT COUNT(*) FROM tbl_christmas_advent_2026_claims WHERE calendar_date = '2026-12-01'") === 2,
        'different accounts can hold separate claims for the same calendar day'
    );
    christmas26_migration_expect_rejection($database,
        "INSERT INTO tbl_christmas_advent_2026_claims (event_key,user_id,calendar_date,day_number,reward_dspoinc,actor_user_id,score_id,adjustment_id) VALUES ('christmas_advent_2026','111111111111111111','2026-12-01',1,5000,'111111111111111111',102,202)",
        'same account cannot duplicate a daily claim'
    );

    for ($day = 2; $day <= 24; $day++) {
        $userId = sprintf('333333333333333%03d', $day);
        $scoreId = 1000 + $day;
        $adjustmentId = 2000 + $day;
        christmas26_migration_insert_valid_references($database, $userId, $scoreId, $adjustmentId);
        $date = sprintf('2026-12-%02d', $day);
        $database->exec("INSERT INTO tbl_christmas_advent_2026_claims
            (event_key, user_id, calendar_date, day_number, reward_dspoinc, actor_user_id, score_id, adjustment_id)
            VALUES ('christmas_advent_2026', '$userId', '$date', $day, 5000, '$userId', $scoreId, $adjustmentId)");
    }
    christmas26_migration_assert(
        (int)$database->querySingle('SELECT COUNT(DISTINCT calendar_date) FROM tbl_christmas_advent_2026_claims') === 24,
        'all 24 canonical December calendar dates are accepted'
    );

    foreach ([[150, 250], [151, 251], [152, 252]] as [$scoreId, $adjustmentId]) {
        $database->exec("INSERT INTO tbl_user_scores (id, user_id, game, score, source) VALUES ($scoreId, '111111111111111111', 'christmas_advent_2026', 5000, 'christmas_advent_2026')");
        $database->exec("INSERT INTO tbl_score_adjustments (id, user_id, admin_id, amount, action, reason) VALUES ($adjustmentId, '111111111111111111', '111111111111111111', 5000, 'add', 'christmas_advent_2026')");
    }

    christmas26_migration_expect_rejection($database,
        "INSERT INTO tbl_christmas_advent_2026_claims (event_key,user_id,calendar_date,day_number,reward_dspoinc,actor_user_id,score_id,adjustment_id) VALUES ('christmas_advent_2026','111111111111111111','2026-11-30',1,5000,'111111111111111111',150,250)",
        'invalid calendar date is rejected'
    );
    christmas26_migration_expect_rejection($database,
        "INSERT INTO tbl_christmas_advent_2026_claims (event_key,user_id,calendar_date,day_number,reward_dspoinc,actor_user_id,score_id,adjustment_id) VALUES ('christmas_advent_2026','111111111111111111','2026-12-02',1,5000,'111111111111111111',151,251)",
        'mismatched calendar date and day number are rejected'
    );
    christmas26_migration_expect_rejection($database,
        "INSERT INTO tbl_christmas_advent_2026_claims (event_key,user_id,calendar_date,day_number,reward_dspoinc,actor_user_id,score_id,adjustment_id) VALUES ('christmas_advent_2026','111111111111111111','2026-12-02',2,5000,'222222222222222222',152,252)",
        'actor mismatch is rejected'
    );
    christmas26_migration_expect_rejection($database,
        "INSERT INTO tbl_christmas_advent_2026_claims (event_key,user_id,calendar_date,day_number,reward_dspoinc,actor_user_id,score_id,adjustment_id) VALUES ('christmas_advent_2026','111111111111111111','2026-12-02',2,5000,'111111111111111111',101,202)",
        'duplicate ledger reference is rejected'
    );
    christmas26_migration_expect_rejection($database,
        "INSERT INTO tbl_christmas_advent_2026_claims (event_key,user_id,calendar_date,day_number,reward_dspoinc,actor_user_id,score_id,adjustment_id) VALUES ('christmas_advent_2026','111111111111111111','2026-12-02',2,5000,'111111111111111111',102,201)",
        'duplicate audit reference is rejected'
    );
    christmas26_migration_expect_rejection($database,
        "INSERT INTO tbl_christmas_advent_2026_claims (event_key,user_id,calendar_date,day_number,reward_dspoinc,actor_user_id,score_id,adjustment_id) VALUES ('christmas_advent_2026','111111111111111111','2026-12-02',2,5000,'111111111111111111',102,202)",
        'missing ledger and audit parents are rejected'
    );

    $beforeRepeat = (string)$database->querySingle("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'tbl_christmas_advent_2026_claims'");
    christmas26_migration_expect_rejection($database, $migrationSql, 'repeat migration fails rather than accepting an existing schema');
    christmas26_migration_assert(
        (string)$database->querySingle("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'tbl_christmas_advent_2026_claims'") === $beforeRepeat,
        'failed repeat migration leaves the existing schema unchanged'
    );

    $database->exec('PRAGMA foreign_keys = OFF');
    $database->exec("INSERT INTO tbl_christmas_advent_2026_claims
        (event_key, user_id, calendar_date, day_number, reward_dspoinc, actor_user_id, score_id, adjustment_id)
        VALUES ('christmas_advent_2026', 'orphan', '2026-12-02', 2, 5000, 'orphan', 303, 403)");
    $database->exec('PRAGMA foreign_keys = ON');
    christmas26_migration_assert(
        (int)$database->querySingle("SELECT COUNT(*) FROM pragma_foreign_key_check('tbl_christmas_advent_2026_claims')") === 4,
        'table-scoped foreign-key check identifies a disposable malformed marker'
    );

    $database->exec("INSERT INTO tbl_user_scores (id, user_id, game, score, source) VALUES (303, '111111111111111111', 'christmas_advent_2026', 5000, 'not_christmas')");
    $database->exec("INSERT INTO tbl_score_adjustments (id, user_id, admin_id, amount, action, reason) VALUES (403, '111111111111111111', '111111111111111111', 5000, 'add', 'christmas_advent_2026')");
    $database->exec("UPDATE tbl_christmas_advent_2026_claims SET user_id = '222222222222222222', actor_user_id = '222222222222222222' WHERE score_id = 303");
    christmas26_migration_assert(
        (int)$database->querySingle("SELECT COUNT(*) FROM pragma_foreign_key_check('tbl_christmas_advent_2026_claims')") === 0,
        'table-scoped foreign-key check clears after disposable references are repaired'
    );
    $malformed = christmas26_status($database, '222222222222222222', new DateTimeImmutable('2026-12-02 12:00:00', new DateTimeZone('Europe/Vienna')));
    christmas26_migration_assert(
        !$malformed['ready'] && $malformed['error'] === 'christmas26_claim_status_integrity_failed',
        'V2.2 status fails closed for a semantically malformed linked record'
    );

    $database->exec('PRAGMA foreign_keys = ON');
    christmas26_migration_assert($database->querySingle('PRAGMA integrity_check') === 'ok', 'disposable database integrity remains ok');
    echo 'PASS: Christmas Advent 2026 migration suite complete' . PHP_EOL;
} finally {
    if ($database instanceof SQLite3) {
        $database->close();
    }
    if (is_file($databasePath)) {
        unlink($databasePath);
    }
}
