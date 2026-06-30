<?php
/**
 * Narrrfs World — SPOINC Bridge Admin Read API
 *
 * Plain language for DEVS FOR DECADES:
 * This endpoint gives admins a safe read-only overview of SPOINC bridge activity.
 * It must never call Gensuki, confirm transactions, settle intents, credit DSPOINC,
 * debit DSPOINC, or mutate bridge rows. All bridge execution stays in Swap Lab APIs.
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const SPOINC_BRIDGE_ADMIN_DEFAULT_LIMIT = 25;
const SPOINC_BRIDGE_ADMIN_MAX_LIMIT = 100;

/**
 * Send a JSON response and stop execution.
 */
function spoinc_bridge_admin_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Keep the API read-only and protected by the existing admin session where possible.
 *
 * TODO: If the live admin auth.php exposes one official helper/guard later,
 * replace this compatibility check with that shared admin guard.
 */
function spoinc_bridge_admin_is_allowed(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $remoteAddress = (string)($_SERVER['REMOTE_ADDR'] ?? '');

    $isLocal =
        str_contains($host, 'localhost') ||
        str_contains($host, '127.0.0.1') ||
        $remoteAddress === '127.0.0.1' ||
        $remoteAddress === '::1';

    if ($isLocal) {
        return true;
    }

    $possibleSessionFlags = [
        $_SESSION['admin_logged_in'] ?? null,
        $_SESSION['admin_authenticated'] ?? null,
        $_SESSION['is_admin'] ?? null,
        $_SESSION['admin'] ?? null,
    ];

    foreach ($possibleSessionFlags as $flag) {
        if ($flag === true || $flag === 1 || $flag === '1' || $flag === 'true') {
            return true;
        }
    }

    $possibleSessionUser = $_SESSION['admin_user'] ?? $_SESSION['user'] ?? null;
    if (is_array($possibleSessionUser)) {
        $role = strtolower((string)($possibleSessionUser['role'] ?? ''));
        if (in_array($role, ['admin', 'owner', 'super_admin', 'moderator'], true)) {
            return true;
        }
    }

    return false;
}

/**
 * Resolve the project SQLite database path.
 */
function spoinc_bridge_admin_get_db_path(): string
{
    $candidates = [
        __DIR__ . '/../../db/narrrf_world.sqlite',
        '/var/www/html/db/narrrf_world.sqlite',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return $candidates[0];
}

/**
 * Open SQLite connection in exception mode.
 */
function spoinc_bridge_admin_open_db(): PDO
{
    $dbPath = spoinc_bridge_admin_get_db_path();

    if (!is_file($dbPath)) {
        spoinc_bridge_admin_json([
            'success' => false,
            'error' => 'Database file not found.',
            'db_path' => $dbPath,
        ], 500);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

/**
 * Read a string query parameter safely.
 */
function spoinc_bridge_admin_string_param(string $key, string $fallback = ''): string
{
    return trim((string)($_GET[$key] ?? $fallback));
}

/**
 * Read a bounded integer query parameter safely.
 */
function spoinc_bridge_admin_limit_param(string $key = 'limit'): int
{
    $limit = (int)($_GET[$key] ?? SPOINC_BRIDGE_ADMIN_DEFAULT_LIMIT);

    if ($limit <= 0) {
        return SPOINC_BRIDGE_ADMIN_DEFAULT_LIMIT;
    }

    return min($limit, SPOINC_BRIDGE_ADMIN_MAX_LIMIT);
}

/**
 * Return all rows from a prepared statement.
 */
function spoinc_bridge_admin_fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

/**
 * Return one row from a prepared statement.
 */
function spoinc_bridge_admin_fetch_one(PDO $pdo, string $sql, array $params = []): ?array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch();

    return $row === false ? null : $row;
}

/**
 * Build the safe WHERE clause for recent bridge filters.
 */
function spoinc_bridge_admin_recent_filter(string $type): string
{
    return match ($type) {
        'buys' => "i.route_key = 'SOL_TO_SPOINC'",
        'claims' => "i.route_key = 'SPOINC_TO_DSPOINC'",
        'pending' => "(
            i.status IN ('buy_payload_ready', 'claim_payload_ready')
            OR i.narrrfs_status LIKE 'awaiting%'
            OR i.gensuki_status LIKE '%payload_ready%'
        )",
        'failed' => "(
            i.status LIKE '%failed%'
            OR i.gensuki_status LIKE '%failed%'
            OR i.narrrfs_status LIKE '%failed%'
            OR COALESCE(i.error_message, '') <> ''
        )",
        'settled' => "(
            i.status IN ('confirmed', 'settled')
            OR i.narrrfs_status IN ('settled', 'buy_confirmed_no_ledger_movement')
        )",
        default => '1 = 1',
    };
}

/**
 * Shared SELECT for intent rows with linked transaction and audit data.
 */
function spoinc_bridge_admin_intent_select_sql(string $whereSql): string
{
    return "
        SELECT
            i.intent_id,
            i.idempotency_id,
            i.discord_id,
            i.route_key,
            i.direction,
            i.wallet,
            i.input_token,
            i.output_token,
            i.input_amount,
            i.expected_output_amount,
            i.dspoinc_amount,
            i.spoinc_amount,
            i.status,
            i.gensuki_status,
            i.narrrfs_status,
            i.error_message,
            i.created_at,
            i.submitted_at,
            i.confirmed_at,
            i.settled_at,
            i.failed_at,

            t.transaction_id,
            t.transaction_hash,
            t.signature,
            t.gensuki_status AS transaction_gensuki_status,
            t.narrrfs_status AS transaction_narrrfs_status,
            t.confirmed_at AS transaction_confirmed_at,

            a.audit_id,
            a.ledger_action,
            a.dspoinc_delta,
            a.spoinc_amount AS audit_spoinc_amount,
            a.status AS audit_status,
            a.tbl_user_scores_id,
            a.tbl_score_adjustment_id,
            a.processed_at
        FROM tbl_spoinc_bridge_intents i
        LEFT JOIN tbl_spoinc_bridge_transactions t
            ON t.intent_id = i.intent_id
        LEFT JOIN tbl_spoinc_bridge_ledger_audit a
            ON a.intent_id = i.intent_id
        WHERE {$whereSql}
        ORDER BY i.intent_id DESC
    ";
}

/**
 * Build overview metrics for the admin dashboard cards.
 */
function spoinc_bridge_admin_overview(PDO $pdo): array
{
    $config = spoinc_bridge_admin_fetch_one($pdo, "
        SELECT
            config_key,
            public_enabled,
            settlement_enabled,
            external_sell_enabled,
            status
        FROM tbl_spoinc_bridge_config
        ORDER BY config_key ASC
        LIMIT 1
    ") ?? [];

    $routes = spoinc_bridge_admin_fetch_all($pdo, "
        SELECT
            route_key,
            public_enabled,
            backend_enabled,
            status
        FROM tbl_spoinc_bridge_routes
        ORDER BY route_key ASC
    ");

    $metrics = spoinc_bridge_admin_fetch_one($pdo, "
        SELECT
            (SELECT COUNT(*)
             FROM tbl_spoinc_bridge_intents
             WHERE route_key = 'SOL_TO_SPOINC'
               AND status = 'confirmed') AS confirmed_buys,

            (SELECT COUNT(*)
             FROM tbl_spoinc_bridge_intents
             WHERE route_key = 'SPOINC_TO_DSPOINC'
               AND status = 'settled') AS settled_claims,

            (SELECT COUNT(*)
             FROM tbl_spoinc_bridge_intents
             WHERE status IN ('buy_payload_ready', 'claim_payload_ready')
                OR narrrfs_status LIKE 'awaiting%') AS pending_rows,

            (SELECT COUNT(*)
             FROM tbl_spoinc_bridge_intents
             WHERE status LIKE '%failed%'
                OR gensuki_status LIKE '%failed%'
                OR narrrfs_status LIKE '%failed%'
                OR COALESCE(error_message, '') <> '') AS failed_rows,

            (SELECT COALESCE(SUM(dspoinc_delta), 0)
             FROM tbl_spoinc_bridge_ledger_audit
             WHERE route_key = 'SPOINC_TO_DSPOINC'
               AND dspoinc_delta > 0
               AND status = 'processed') AS total_dspoinc_credited
    ") ?? [];

    $recent = spoinc_bridge_admin_fetch_all(
        $pdo,
        spoinc_bridge_admin_intent_select_sql("(
            i.status IN ('confirmed', 'settled')
            OR i.narrrfs_status IN ('settled', 'buy_confirmed_no_ledger_movement')
        )") . " LIMIT 10"
    );

    return [
        'config' => $config,
        'routes' => $routes,
        'metrics' => $metrics,
        'recent' => $recent,
    ];
}

/**
 * Check replay / duplicate bridge ledger rows by transaction hash.
 */
function spoinc_bridge_admin_duplicate_check(PDO $pdo): array
{
    return spoinc_bridge_admin_fetch_all($pdo, "
        SELECT
            transaction_hash,
            COUNT(*) AS duplicate_count,
            SUM(dspoinc_delta) AS total_dspoinc_delta
        FROM tbl_spoinc_bridge_ledger_audit
        WHERE status = 'processed'
        GROUP BY transaction_hash
        HAVING COUNT(*) > 1
        ORDER BY duplicate_count DESC
        LIMIT 25
    ");
}

if (!spoinc_bridge_admin_is_allowed()) {
    spoinc_bridge_admin_json([
        'success' => false,
        'error' => 'Admin authentication is required for SPOINC bridge admin data.',
    ], 403);
}

try {
    $pdo = spoinc_bridge_admin_open_db();
    $action = spoinc_bridge_admin_string_param('action', 'overview');

    if ($action === 'overview') {
        spoinc_bridge_admin_json([
            'success' => true,
            'action' => $action,
            'data' => spoinc_bridge_admin_overview($pdo),
        ]);
    }

    if ($action === 'recent') {
        $type = spoinc_bridge_admin_string_param('type', 'all');
        $limit = spoinc_bridge_admin_limit_param();
        $whereSql = spoinc_bridge_admin_recent_filter($type);

        spoinc_bridge_admin_json([
            'success' => true,
            'action' => $action,
            'type' => $type,
            'data' => [
                'rows' => spoinc_bridge_admin_fetch_all(
                    $pdo,
                    spoinc_bridge_admin_intent_select_sql($whereSql) . " LIMIT ?",
                    [$limit]
                ),
            ],
        ]);
    }

    if ($action === 'user') {
        $discordId = spoinc_bridge_admin_string_param('discord_id');
        $limit = spoinc_bridge_admin_limit_param();

        if ($discordId === '') {
            spoinc_bridge_admin_json(['success' => false, 'error' => 'discord_id is required.'], 400);
        }

        spoinc_bridge_admin_json([
            'success' => true,
            'action' => $action,
            'data' => [
                'rows' => spoinc_bridge_admin_fetch_all(
                    $pdo,
                    spoinc_bridge_admin_intent_select_sql("i.discord_id = ?") . " LIMIT ?",
                    [$discordId, $limit]
                ),
            ],
        ]);
    }

    if ($action === 'wallet') {
        $wallet = spoinc_bridge_admin_string_param('wallet');
        $limit = spoinc_bridge_admin_limit_param();

        if ($wallet === '') {
            spoinc_bridge_admin_json(['success' => false, 'error' => 'wallet is required.'], 400);
        }

        $usesPartialSearch = strlen($wallet) < 32;
        $whereSql = $usesPartialSearch ? 'i.wallet LIKE ?' : 'i.wallet = ?';
        $walletParam = $usesPartialSearch ? '%' . $wallet . '%' : $wallet;

        spoinc_bridge_admin_json([
            'success' => true,
            'action' => $action,
            'data' => [
                'rows' => spoinc_bridge_admin_fetch_all(
                    $pdo,
                    spoinc_bridge_admin_intent_select_sql($whereSql) . " LIMIT ?",
                    [$walletParam, $limit]
                ),
            ],
        ]);
    }

    if ($action === 'tx') {
        $signature = spoinc_bridge_admin_string_param('signature');

        if ($signature === '') {
            spoinc_bridge_admin_json(['success' => false, 'error' => 'signature is required.'], 400);
        }

        $row = spoinc_bridge_admin_fetch_one($pdo, "
            SELECT
                t.*,
                i.discord_id,
                i.wallet,
                i.input_amount,
                i.expected_output_amount,
                i.status AS intent_status,
                i.gensuki_status AS intent_gensuki_status,
                i.narrrfs_status AS intent_narrrfs_status,
                i.created_at AS intent_created_at,
                i.settled_at AS intent_settled_at,
                a.audit_id,
                a.ledger_action,
                a.dspoinc_delta,
                a.spoinc_amount,
                a.status AS audit_status,
                a.tbl_user_scores_id,
                a.tbl_score_adjustment_id,
                a.processed_at
            FROM tbl_spoinc_bridge_transactions t
            LEFT JOIN tbl_spoinc_bridge_intents i
                ON i.intent_id = t.intent_id
            LEFT JOIN tbl_spoinc_bridge_ledger_audit a
                ON a.transaction_id = t.transaction_id
            WHERE t.transaction_hash = ?
               OR t.signature = ?
            LIMIT 1
        ", [$signature, $signature]);

        spoinc_bridge_admin_json([
            'success' => true,
            'action' => $action,
            'data' => [
                'row' => $row,
            ],
        ]);
    }

    if ($action === 'intent') {
        $intentId = (int)($_GET['intent_id'] ?? 0);

        if ($intentId <= 0) {
            spoinc_bridge_admin_json(['success' => false, 'error' => 'intent_id is required.'], 400);
        }

        $rows = spoinc_bridge_admin_fetch_all(
            $pdo,
            spoinc_bridge_admin_intent_select_sql("i.intent_id = ?") . " LIMIT 1",
            [$intentId]
        );

        spoinc_bridge_admin_json([
            'success' => true,
            'action' => $action,
            'data' => [
                'row' => $rows[0] ?? null,
            ],
        ]);
    }

    if ($action === 'audit') {
        $limit = spoinc_bridge_admin_limit_param();

        spoinc_bridge_admin_json([
            'success' => true,
            'action' => $action,
            'data' => [
                'rows' => spoinc_bridge_admin_fetch_all($pdo, "
                    SELECT
                        audit_id,
                        intent_id,
                        transaction_id,
                        idempotency_id,
                        transaction_hash,
                        discord_id,
                        wallet,
                        route_key,
                        direction,
                        ledger_action,
                        dspoinc_delta,
                        spoinc_amount,
                        conversion_rate_dspoinc_per_spoinc,
                        tbl_user_scores_id,
                        tbl_score_adjustment_id,
                        status,
                        error_message,
                        created_at,
                        processed_at
                    FROM tbl_spoinc_bridge_ledger_audit
                    ORDER BY audit_id DESC
                    LIMIT ?
                ", [$limit]),
            ],
        ]);
    }

    if ($action === 'duplicates') {
        spoinc_bridge_admin_json([
            'success' => true,
            'action' => $action,
            'data' => [
                'rows' => spoinc_bridge_admin_duplicate_check($pdo),
            ],
        ]);
    }

    spoinc_bridge_admin_json([
        'success' => false,
        'error' => 'Unknown action.',
        'allowed_actions' => ['overview', 'recent', 'user', 'wallet', 'tx', 'intent', 'audit', 'duplicates'],
    ], 400);
} catch (Throwable $error) {
    spoinc_bridge_admin_json([
        'success' => false,
        'error' => 'SPOINC bridge admin API failed.',
        'details' => $error->getMessage(),
    ], 500);
}