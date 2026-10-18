<?php
declare(strict_types=1);

// 🔐 Narrrfs World trusted Discord OAuth and owner-session foundation.
// DEVS FOR DECADES:
// This file establishes provenance and validates session age. It grants no
// owner authority by itself; the fail-closed environment allowlist remains a
// separate mandatory check in admin-authorization.php.

require_once __DIR__ . '/session.php';

const NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY = 'narrrfs_oauth_pending_states_v1';
const NARRRFS_OAUTH_STATE_TTL_SECONDS = 600;
const NARRRFS_OAUTH_STATE_MAX_RECORDS = 5;
const NARRRFS_ADMIN_AUTH_PROVENANCE = 'discord_oauth_v1';
const NARRRFS_ADMIN_PROVENANCE_SESSION_KEY = 'auth_provenance';
const NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY = 'narrrfs_admin_auth_issued_at';
const NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY = 'narrrfs_admin_auth_last_seen_at';
const NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY = 'narrrfs_admin_auth_expires_at';
const NARRRFS_ADMIN_AUTH_IDLE_SECONDS = 1800;
const NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS = 43200;
const NARRRFS_ADMIN_AUTH_FUTURE_SKEW_SECONDS = 60;
const NARRRFS_ADMIN_CSRF_SESSION_KEY = 'narrrfs_admin_csrf_token';
const NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY = 'narrrfs_admin_csrf_issued_at';
const NARRRFS_ADMIN_CSRF_TTL_SECONDS = 300;

/** Remove owner-only provenance and CSRF state without destroying normal-user identity. */
function narrrfs_clear_admin_authority_state(array &$session): void
{
    unset(
        $session[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY],
        $session[NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY],
        $session[NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY],
        $session[NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY],
        $session[NARRRFS_ADMIN_CSRF_SESSION_KEY],
        $session[NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY]
    );
}

/** Keep only well-formed, unexpired OAuth state records and cap parallel logins. */
function narrrfs_prune_oauth_pending_states(array &$session, int $now): void
{
    $records = $session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY] ?? [];
    if (!is_array($records)) {
        unset($session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY]);
        return;
    }

    $validRecords = [];
    foreach ($records as $digest => $record) {
        if (
            !is_string($digest) || !preg_match('/^[a-f0-9]{64}$/', $digest) ||
            !is_array($record) || !is_int($record['issued_at'] ?? null) ||
            !is_string($record['final_redirect'] ?? null) || $record['final_redirect'] === ''
        ) {
            continue;
        }

        $issuedAt = $record['issued_at'];
        if ($issuedAt > $now + NARRRFS_ADMIN_AUTH_FUTURE_SKEW_SECONDS) {
            continue;
        }
        if ($now - $issuedAt >= NARRRFS_OAUTH_STATE_TTL_SECONDS) {
            continue;
        }

        $validRecords[$digest] = [
            'issued_at' => $issuedAt,
            'final_redirect' => $record['final_redirect'],
        ];
    }

    uasort($validRecords, static function (array $left, array $right): int {
        return $left['issued_at'] <=> $right['issued_at'];
    });
    while (count($validRecords) > NARRRFS_OAUTH_STATE_MAX_RECORDS) {
        array_shift($validRecords);
    }

    if ($validRecords === []) {
        unset($session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY]);
        return;
    }
    $session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY] = $validRecords;
}

/** Issue one opaque OAuth state while storing only its digest server-side. */
function narrrfs_issue_oauth_state(
    array &$session,
    string $finalRedirect,
    ?int $now = null,
    ?callable $randomBytes = null
): string {
    $now = $now ?? time();
    $rawBytes = $randomBytes === null ? random_bytes(32) : $randomBytes(32);
    if (!is_string($rawBytes) || strlen($rawBytes) !== 32 || $finalRedirect === '') {
        throw new RuntimeException('Unable to initialize OAuth state.');
    }

    narrrfs_prune_oauth_pending_states($session, $now);
    $state = bin2hex($rawBytes);
    $digest = hash('sha256', $state);
    $records = $session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY] ?? [];
    $records[$digest] = [
        'issued_at' => $now,
        'final_redirect' => $finalRedirect,
    ];
    $session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY] = $records;
    narrrfs_prune_oauth_pending_states($session, $now);
    return $state;
}

/**
 * Consume one OAuth state before token exchange.
 *
 * @return array{status: 'missing'|'invalid'|'valid', final_redirect?: string}
 */
function narrrfs_consume_oauth_state(array &$session, ?string $receivedState, ?int $now = null): array
{
    $now = $now ?? time();
    if ($receivedState === null || $receivedState === '') {
        return ['status' => 'missing'];
    }
    if (!preg_match('/^[a-f0-9]{64}$/', $receivedState)) {
        return ['status' => 'invalid'];
    }

    narrrfs_prune_oauth_pending_states($session, $now);
    $records = $session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY] ?? [];
    $digest = hash('sha256', $receivedState);
    $record = is_array($records) ? ($records[$digest] ?? null) : null;
    if (!is_array($record)) {
        return ['status' => 'invalid'];
    }

    unset($records[$digest]);
    if ($records === []) {
        unset($session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY]);
    } else {
        $session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY] = $records;
    }

    return [
        'status' => 'valid',
        'final_redirect' => $record['final_redirect'],
    ];
}

/** Establish canonical OAuth provenance only after validated server identity and rotation. */
function narrrfs_establish_discord_oauth_session(
    array &$session,
    array $serverIdentity,
    callable $regenerateSessionId,
    ?int $now = null
): bool {
    $subject = $serverIdentity['id'] ?? null;
    if (!is_string($subject) || !preg_match('/^\d{17,20}$/', $subject)) {
        return false;
    }
    unset(
        $session['discord_id'],
        $session['user'],
        $session['access_token'],
        $session['token_expires_at'],
        $session['refresh_token'],
        $session['guilds'],
        $session['admin_authenticated'],
        $session['admin_logged_in'],
        $session['admin_role'],
        $session['admin_roles']
    );
    narrrfs_clear_admin_authority_state($session);
    if (!$regenerateSessionId()) {
        return false;
    }

    $now = $now ?? time();
    $session['discord_id'] = $subject;
    $session[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY] = NARRRFS_ADMIN_AUTH_PROVENANCE;
    $session[NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY] = $now;
    $session[NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY] = $now;
    $session[NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY] = $now + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS;
    return true;
}

/** Validate provenance and owner-authority age without consulting the owner allowlist. */
function narrrfs_admin_session_is_current(array $session, ?int $now = null): bool
{
    $now = $now ?? time();
    $subject = $session['discord_id'] ?? null;
    $provenance = $session[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY] ?? null;
    $issuedAt = $session[NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY] ?? null;
    $lastSeenAt = $session[NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY] ?? null;
    $expiresAt = $session[NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY] ?? null;

    if (
        !is_string($subject) || !preg_match('/^\d{17,20}$/', $subject) ||
        !is_string($provenance) || !hash_equals(NARRRFS_ADMIN_AUTH_PROVENANCE, $provenance) ||
        !is_int($issuedAt) || !is_int($lastSeenAt) || !is_int($expiresAt)
    ) {
        return false;
    }
    if ($issuedAt > $now + NARRRFS_ADMIN_AUTH_FUTURE_SKEW_SECONDS || $lastSeenAt > $now + NARRRFS_ADMIN_AUTH_FUTURE_SKEW_SECONDS) {
        return false;
    }
    if ($lastSeenAt < $issuedAt || $expiresAt !== $issuedAt + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS) {
        return false;
    }
    if ($now - $lastSeenAt >= NARRRFS_ADMIN_AUTH_IDLE_SECONDS || $now >= $expiresAt) {
        return false;
    }
    return true;
}

/** Advance owner last-seen only after the complete authorization decision succeeds. */
function narrrfs_touch_admin_authority(array &$session, ?int $now = null): void
{
    if (narrrfs_admin_session_is_current($session, $now)) {
        $session[NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY] = $now ?? time();
    }
}

/** Validate an exact one-time CSRF token and its five-minute server-session lifetime. */
function narrrfs_admin_csrf_is_current(array $session, mixed $requestToken, ?int $now = null): bool
{
    $now = $now ?? time();
    $sessionToken = $session[NARRRFS_ADMIN_CSRF_SESSION_KEY] ?? null;
    $issuedAt = $session[NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY] ?? null;
    if (
        !is_string($sessionToken) || !preg_match('/^[a-f0-9]{64}$/', $sessionToken) ||
        !is_string($requestToken) || !preg_match('/^[a-f0-9]{64}$/', $requestToken) ||
        !is_int($issuedAt) || $issuedAt > $now + NARRRFS_ADMIN_AUTH_FUTURE_SKEW_SECONDS ||
        $now - $issuedAt >= NARRRFS_ADMIN_CSRF_TTL_SECONDS
    ) {
        return false;
    }
    return hash_equals($sessionToken, $requestToken);
}

/** Consume a CSRF token before protected route logic so it cannot be replayed. */
function narrrfs_consume_admin_csrf_session(array &$session): void
{
    unset($session[NARRRFS_ADMIN_CSRF_SESSION_KEY], $session[NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY]);
}

/** Build a pure logout decision; runtime destruction happens only after allowed=true. */
function narrrfs_logout_decision(
    array $session,
    array $request,
    string $allowedOrigin,
    ?int $now = null
): array {
    $method = strtoupper((string)($request['method'] ?? ''));
    if ($method !== 'POST') {
        return ['allowed' => false, 'status' => 405, 'code' => 'method_not_allowed', 'allow' => ['POST']];
    }
    $contentType = strtolower(trim(explode(';', (string)($request['contentType'] ?? ''), 2)[0]));
    if ($contentType !== 'application/json') {
        return ['allowed' => false, 'status' => 415, 'code' => 'unsupported_media_type', 'allow' => []];
    }
    $origin = strtolower(rtrim(trim((string)($request['origin'] ?? '')), '/'));
    if ($origin === '' || !hash_equals(strtolower(rtrim($allowedOrigin, '/')), $origin)) {
        return ['allowed' => false, 'status' => 403, 'code' => 'origin_rejected', 'allow' => []];
    }
    if (!narrrfs_admin_csrf_is_current($session, $request['csrfToken'] ?? null, $now)) {
        return ['allowed' => false, 'status' => 403, 'code' => 'csrf_rejected', 'allow' => []];
    }
    return ['allowed' => true, 'status' => 200, 'code' => 'authorized', 'allow' => ['POST']];
}
