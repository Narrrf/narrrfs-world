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
const NARRRFS_LOGOUT_CSRF_SESSION_KEY = 'narrrfs_logout_csrf_token';
const NARRRFS_LOGOUT_CSRF_ISSUED_AT_SESSION_KEY = 'narrrfs_logout_csrf_issued_at';
const NARRRFS_LOGOUT_CSRF_TTL_SECONDS = 300;

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
        $session['admin_roles'],
        $session[NARRRFS_LOGOUT_CSRF_SESSION_KEY],
        $session[NARRRFS_LOGOUT_CSRF_ISSUED_AT_SESSION_KEY]
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

/**
 * Confirm that a normal player identity came from the canonical server-side
 * Discord OAuth callback. This is intentionally not owner authorization: it
 * grants no owner capability and does not apply owner-session idle limits.
 */
function narrrfs_session_has_trusted_discord_oauth_provenance(array $session): bool
{
    $subject = $session['discord_id'] ?? null;
    $provenance = $session[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY] ?? null;

    return is_string($subject)
        && preg_match('/^\d{17,20}$/', $subject) === 1
        && is_string($provenance)
        && hash_equals(NARRRFS_ADMIN_AUTH_PROVENANCE, $provenance);
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

/**
 * Issue one one-time token for an Owner Control mutation.
 *
 * Plain language:
 * This creates only CSRF state inside an already-current trusted owner session.
 * It does not establish Discord identity, grant owner authority, extend the
 * absolute lifetime, or change the configured owner-session timeout rules.
 */
function narrrfs_issue_admin_csrf_token(
    array &$session,
    ?int $now = null,
    ?callable $randomBytes = null
): string {
    $now = $now ?? time();

    if (!narrrfs_admin_session_is_current($session, $now)) {
        throw new RuntimeException(
            'Unable to initialize admin mutation protection.'
        );
    }

    $rawBytes = $randomBytes === null
        ? random_bytes(32)
        : $randomBytes(32);

    if (!is_string($rawBytes) || strlen($rawBytes) !== 32) {
        throw new RuntimeException(
            'Unable to initialize admin mutation protection.'
        );
    }

    $token = bin2hex($rawBytes);

    $session[NARRRFS_ADMIN_CSRF_SESSION_KEY] = $token;
    $session[NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY] = $now;

    return $token;
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

/** Confirm the session contains an ordinary server-side Discord identity. */
function narrrfs_session_has_discord_identity(array $session): bool
{
    $subject = $session['discord_id'] ?? null;
    return is_string($subject) && preg_match('/^\d{17,20}$/', $subject) === 1;
}

/** Map only canonical application hosts to their fixed same-origin logout URL. */
function narrrfs_logout_allowed_origin_for_host(string $host): ?string
{
    $normalizedHost = strtolower(trim(explode(':', $host, 2)[0]));
    if ($normalizedHost === 'localhost') {
        return 'http://localhost';
    }
    if ($normalizedHost === 'narrrfs.world') {
        return 'https://narrrfs.world';
    }
    return null;
}

/** Normalize a configured/request origin without accepting paths or credentials. */
function narrrfs_normalize_origin(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $parts = parse_url($value);
    if (
        !is_array($parts) || !isset($parts['scheme'], $parts['host']) ||
        isset($parts['user']) || isset($parts['pass']) || isset($parts['path']) ||
        isset($parts['query']) || isset($parts['fragment'])
    ) {
        return null;
    }
    $scheme = strtolower((string)$parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }
    $origin = $scheme . '://' . strtolower((string)$parts['host']);
    if (isset($parts['port'])) {
        $origin .= ':' . (int)$parts['port'];
    }
    return $origin;
}

/** Extract only the origin from a same-origin Referer, where a path is expected. */
function narrrfs_extract_referer_origin(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $parts = parse_url($value);
    if (
        !is_array($parts) || !isset($parts['scheme'], $parts['host']) ||
        isset($parts['user']) || isset($parts['pass'])
    ) {
        return null;
    }
    $scheme = strtolower((string)$parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }
    $origin = $scheme . '://' . strtolower((string)$parts['host']);
    if (isset($parts['port'])) {
        $origin .= ':' . (int)$parts['port'];
    }
    return $origin;
}

/** Prove a logout-token request came from the configured same-origin site. */
function narrrfs_logout_csrf_issue_decision(array $session, array $request, string $allowedOrigin): array
{
    if (strtoupper((string)($request['method'] ?? '')) !== 'GET') {
        return ['allowed' => false, 'status' => 405, 'code' => 'method_not_allowed', 'allow' => ['GET']];
    }
    if (!narrrfs_session_has_discord_identity($session)) {
        return ['allowed' => false, 'status' => 401, 'code' => 'authentication_required', 'allow' => ['GET']];
    }

    $expectedOrigin = narrrfs_normalize_origin($allowedOrigin);
    $rawOrigin = trim((string)($request['origin'] ?? ''));
    $rawReferer = trim((string)($request['referer'] ?? ''));
    $requestOrigin = narrrfs_normalize_origin($rawOrigin);
    $refererOrigin = narrrfs_extract_referer_origin($rawReferer);
    $fetchSite = strtolower(trim((string)($request['fetchSite'] ?? '')));
    if (($rawOrigin !== '' && $requestOrigin === null) || ($rawReferer !== '' && $refererOrigin === null)) {
        return ['allowed' => false, 'status' => 403, 'code' => 'origin_rejected', 'allow' => ['GET']];
    }
    $sameOrigin = $expectedOrigin !== null && (
        ($requestOrigin !== null && hash_equals($expectedOrigin, $requestOrigin)) ||
        ($requestOrigin === null && $refererOrigin !== null && hash_equals($expectedOrigin, $refererOrigin)) ||
        ($requestOrigin === null && $refererOrigin === null && $fetchSite === 'same-origin')
    );
    if (!$sameOrigin) {
        return ['allowed' => false, 'status' => 403, 'code' => 'origin_rejected', 'allow' => ['GET']];
    }
    return ['allowed' => true, 'status' => 200, 'code' => 'authorized', 'allow' => ['GET']];
}

/** Issue a one-time logout token without granting owner or admin authority. */
function narrrfs_issue_logout_csrf_token(
    array &$session,
    ?int $now = null,
    ?callable $randomBytes = null
): string {
    $now = $now ?? time();
    $rawBytes = $randomBytes === null ? random_bytes(32) : $randomBytes(32);
    if (!is_string($rawBytes) || strlen($rawBytes) !== 32) {
        throw new RuntimeException('Unable to initialize logout protection.');
    }
    $token = bin2hex($rawBytes);
    $session[NARRRFS_LOGOUT_CSRF_SESSION_KEY] = $token;
    $session[NARRRFS_LOGOUT_CSRF_ISSUED_AT_SESSION_KEY] = $now;
    return $token;
}

/** Validate a logout-only one-time token and its five-minute session lifetime. */
function narrrfs_logout_csrf_is_current(array $session, mixed $requestToken, ?int $now = null): bool
{
    $now = $now ?? time();
    $sessionToken = $session[NARRRFS_LOGOUT_CSRF_SESSION_KEY] ?? null;
    $issuedAt = $session[NARRRFS_LOGOUT_CSRF_ISSUED_AT_SESSION_KEY] ?? null;
    if (
        !is_string($sessionToken) || !preg_match('/^[a-f0-9]{64}$/', $sessionToken) ||
        !is_string($requestToken) || !preg_match('/^[a-f0-9]{64}$/', $requestToken) ||
        !is_int($issuedAt) || $issuedAt > $now + NARRRFS_ADMIN_AUTH_FUTURE_SKEW_SECONDS ||
        $now - $issuedAt >= NARRRFS_LOGOUT_CSRF_TTL_SECONDS
    ) {
        return false;
    }
    return hash_equals($sessionToken, $requestToken);
}

/** Consume only logout CSRF state; future protected-route CSRF remains separate. */
function narrrfs_consume_logout_csrf_session(array &$session): void
{
    unset($session[NARRRFS_LOGOUT_CSRF_SESSION_KEY], $session[NARRRFS_LOGOUT_CSRF_ISSUED_AT_SESSION_KEY]);
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
    if (!narrrfs_session_has_discord_identity($session)) {
        return ['allowed' => false, 'status' => 401, 'code' => 'authentication_required', 'allow' => []];
    }
    $origin = narrrfs_normalize_origin((string)($request['origin'] ?? ''));
    $expectedOrigin = narrrfs_normalize_origin($allowedOrigin);
    if ($origin === null || $expectedOrigin === null || !hash_equals($expectedOrigin, $origin)) {
        return ['allowed' => false, 'status' => 403, 'code' => 'origin_rejected', 'allow' => []];
    }
    if (!narrrfs_logout_csrf_is_current($session, $request['csrfToken'] ?? null, $now)) {
        return ['allowed' => false, 'status' => 403, 'code' => 'csrf_rejected', 'allow' => []];
    }
    return ['allowed' => true, 'status' => 200, 'code' => 'authorized', 'allow' => ['POST']];
}
