<?php
declare(strict_types=1);

// 🔐 Narrrfs World fail-closed owner authorization foundation
// DEVS FOR DECADES:
// This file grants nothing by itself. Every caller must name an exact route key,
// and that key must have a valid enabled policy. Wave A ships with no policies.
// Never derive owner authority from request data, display names, ordinary cookies,
// browser storage, Host/localhost, IP addresses, or legacy admin session flags.

require_once __DIR__ . '/admin-session.php';

const NARRRFS_ADMIN_OWNER_ENV = 'NARRRFS_ADMIN_DISCORD_IDS';

/**
 * Parse the server-owned owner allowlist.
 *
 * Plain language: an absent, empty, or partly malformed list grants nobody.
 * Values are never logged or returned in an error response.
 *
 * @return list<string>
 */
function narrrfs_parse_admin_owner_ids(?string $rawOwnerIds): array
{
    if ($rawOwnerIds === null || trim($rawOwnerIds) === '') {
        return [];
    }

    $ownerIds = [];
    foreach (explode(',', $rawOwnerIds) as $candidate) {
        $candidate = trim($candidate);
        if (!preg_match('/^\d{17,20}$/', $candidate)) {
            return [];
        }
        $ownerIds[$candidate] = true;
    }

    return array_keys($ownerIds);
}

/**
 * Compare the authenticated subject with the server-side owner allowlist.
 */
function narrrfs_admin_subject_is_owner(string $subject, array $ownerIds): bool
{
    foreach ($ownerIds as $ownerId) {
        if (is_string($ownerId) && hash_equals($ownerId, $subject)) {
            return true;
        }
    }

    return false;
}

/**
 * Reduce a URL to its exact scheme/host/port origin.
 */
function narrrfs_admin_normalize_origin(?string $url): ?string
{
    if ($url === null || trim($url) === '') {
        return null;
    }

    $parts = parse_url(trim($url));
    if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
        return null;
    }

    $scheme = strtolower((string)$parts['scheme']);
    if ($scheme !== 'http' && $scheme !== 'https') {
        return null;
    }

    if (isset($parts['user']) || isset($parts['pass'])) {
        return null;
    }

    $host = strtolower(rtrim((string)$parts['host'], '.'));
    if ($host === '') {
        return null;
    }

    $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
    return $scheme . '://' . $host . $port;
}

/**
 * Validate and normalize one route policy.
 *
 * @return array<string, mixed>|null
 */
function narrrfs_validate_admin_route_policy(mixed $policy): ?array
{
    if (!is_array($policy) || ($policy['enabled'] ?? false) !== true) {
        return null;
    }

    if (($policy['ownerOnly'] ?? false) !== true || !isset($policy['mutating'])) {
        return null;
    }

    $methods = $policy['methods'] ?? null;
    if (!is_array($methods) || $methods === []) {
        return null;
    }

    $normalizedMethods = [];
    foreach ($methods as $method) {
        if (!is_string($method) || !preg_match('/^[A-Z]+$/', $method)) {
            return null;
        }
        $normalizedMethods[$method] = true;
    }
    $normalizedMethods = array_keys($normalizedMethods);

    $mutating = $policy['mutating'] === true;
    $csrfRequired = ($policy['csrf'] ?? false) === true;
    $contentTypes = $policy['contentTypes'] ?? [];
    $origins = $policy['origins'] ?? [];
    $allowRefererFallback = ($policy['allowRefererFallback'] ?? false) === true;

    if ($mutating) {
        foreach ($normalizedMethods as $method) {
            if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return null;
            }
        }
        if (!$csrfRequired || !is_array($contentTypes) || $contentTypes === []) {
            return null;
        }
        if (!is_array($origins) || $origins === []) {
            return null;
        }
    } else {
        foreach ($normalizedMethods as $method) {
            if (!in_array($method, ['GET', 'HEAD'], true)) {
                return null;
            }
        }
        if ($csrfRequired) {
            return null;
        }
    }

    $normalizedContentTypes = [];
    foreach ($contentTypes as $contentType) {
        if (!is_string($contentType) || trim($contentType) === '') {
            return null;
        }
        $normalizedContentTypes[] = strtolower(trim($contentType));
    }

    $normalizedOrigins = [];
    foreach ($origins as $origin) {
        if (!is_string($origin)) {
            return null;
        }
        $normalizedOrigin = narrrfs_admin_normalize_origin($origin);
        if ($normalizedOrigin === null || $normalizedOrigin !== rtrim(strtolower($origin), '/')) {
            return null;
        }
        $normalizedOrigins[$normalizedOrigin] = true;
    }

    return [
        'enabled' => true,
        'ownerOnly' => true,
        'mutating' => $mutating,
        'methods' => $normalizedMethods,
        'csrf' => $csrfRequired,
        'contentTypes' => array_values(array_unique($normalizedContentTypes)),
        'origins' => array_keys($normalizedOrigins),
        'allowRefererFallback' => $allowRefererFallback,
    ];
}

/**
 * Build a uniform authorization decision without starting a session or emitting output.
 *
 * Tests use this pure function so validation cannot touch runtime or protected state.
 *
 * @param array<string, mixed>|null $policy
 * @param array<string, mixed> $session
 * @param array<string, mixed> $request
 * @param list<string> $ownerIds
 * @return array{allowed: bool, status: int, code: string, allow: list<string>, consumeCsrf: bool, subject: ?string}
 */
function narrrfs_admin_authorization_decision(
    mixed $policy,
    array $session,
    array $request,
    array $ownerIds,
    ?int $now = null
): array {
    $deny = static function (int $status, string $code, array $allow = []): array {
        return [
            'allowed' => false,
            'status' => $status,
            'code' => $code,
            'allow' => $allow,
            'consumeCsrf' => false,
            'subject' => null,
        ];
    };

    $policy = narrrfs_validate_admin_route_policy($policy);
    if ($policy === null) {
        return $deny(404, 'admin_route_unavailable');
    }

    if ($ownerIds === []) {
        return $deny(503, 'admin_authority_unavailable');
    }

    $provenance = $session[NARRRFS_ADMIN_PROVENANCE_SESSION_KEY] ?? null;
    if (!is_string($provenance) || !hash_equals(NARRRFS_ADMIN_AUTH_PROVENANCE, $provenance)) {
        return $deny(401, 'authentication_required');
    }

    $subject = $session['discord_id'] ?? null;
    if (!is_string($subject) || !preg_match('/^\d{17,20}$/', $subject)) {
        return $deny(401, 'authentication_required');
    }

    if (!narrrfs_admin_session_is_current($session, $now)) {
        return $deny(401, 'authentication_required');
    }

    if (!narrrfs_admin_subject_is_owner($subject, $ownerIds)) {
        return $deny(403, 'owner_access_required');
    }

    $method = strtoupper((string)($request['method'] ?? ''));
    if (!in_array($method, $policy['methods'], true)) {
        return $deny(405, 'method_not_allowed', $policy['methods']);
    }

    if ($policy['mutating']) {
        $contentType = strtolower(trim(explode(';', (string)($request['contentType'] ?? ''), 2)[0]));
        if (!in_array($contentType, $policy['contentTypes'], true)) {
            return $deny(415, 'unsupported_media_type');
        }

        $rawOrigin = is_string($request['origin'] ?? null)
            ? trim($request['origin'])
            : '';
        $requestOrigin = $rawOrigin === ''
            ? null
            : narrrfs_admin_normalize_origin($rawOrigin);

        // A present but malformed Origin is hostile input, not an absent header.
        if ($rawOrigin !== '' && $requestOrigin === null) {
            return $deny(403, 'origin_rejected');
        }

        if ($rawOrigin === '' && $policy['allowRefererFallback']) {
            $requestOrigin = narrrfs_admin_normalize_origin(
                is_string($request['referer'] ?? null) ? $request['referer'] : null
            );
        }
        if ($requestOrigin === null || !in_array($requestOrigin, $policy['origins'], true)) {
            return $deny(403, 'origin_rejected');
        }

        $requestCsrf = $request['csrfToken'] ?? null;
        if (!narrrfs_admin_csrf_is_current($session, $requestCsrf, $now)) {
            return $deny(403, 'csrf_rejected');
        }
    }

    return [
        'allowed' => true,
        'status' => 200,
        'code' => 'authorized',
        'allow' => $policy['methods'],
        'consumeCsrf' => $policy['mutating'],
        'subject' => $subject,
    ];
}

/**
 * Consume a one-time mutation CSRF token after authorization succeeds.
 *
 * Keeping this transition focused makes replay behavior directly testable.
 *
 * @param array<string, mixed> $session
 */
function narrrfs_consume_admin_csrf(array &$session, bool $consume): void
{
    if ($consume) {
        narrrfs_consume_admin_csrf_session($session);
    }
}

/**
 * Load the deny-by-default policy registry.
 *
 * @return array<string, mixed>
 */
function narrrfs_load_admin_route_policies(?string $path = null): array
{
    $path = $path ?? (__DIR__ . '/admin-route-policies.php');
    if (!is_file($path)) {
        return [];
    }

    try {
        $policies = require $path;
    } catch (Throwable) {
        return [];
    }

    if (
        !is_array($policies) ||
        ($policies['version'] ?? null) !== 1 ||
        ($policies['default'] ?? null) !== 'deny' ||
        !is_array($policies['routes'] ?? null)
    ) {
        return [];
    }

    return $policies;
}

/**
 * Send a generic no-store failure and stop before route logic.
 *
 * @return never
 */
function narrrfs_admin_fail_closed(int $status, string $code, array $allow = []): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
    if ($status === 405 && $allow !== []) {
        header('Allow: ' . implode(', ', $allow));
    }

    echo json_encode([
        'success' => false,
        'error' => $code,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Require an exact owner policy before any future route business logic.
 *
 * Plain language: Wave A has no route policy, so every call fails closed.
 * A later endpoint must call this function before loading DB or domain code.
 */
function narrrfs_require_admin_request(string $routeKey): string
{
    try {
        $policies = narrrfs_load_admin_route_policies();
        $policy = $policies['routes'][$routeKey] ?? null;

        $sessionBootstrap = __DIR__ . '/session.php';
        if (!is_file($sessionBootstrap)) {
            narrrfs_admin_fail_closed(500, 'authorization_unavailable');
        }
        require_once $sessionBootstrap;

        if (!narrrfs_admin_session_is_current($_SESSION)) {
            narrrfs_clear_admin_authority_state($_SESSION);
        }

        $rawOwnerIds = getenv(NARRRFS_ADMIN_OWNER_ENV);
        $ownerIds = narrrfs_parse_admin_owner_ids(
            is_string($rawOwnerIds) ? $rawOwnerIds : null
        );

        $decision = narrrfs_admin_authorization_decision(
            $policy,
            $_SESSION,
            [
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'contentType' => $_SERVER['CONTENT_TYPE'] ?? '',
                'origin' => $_SERVER['HTTP_ORIGIN'] ?? null,
                'referer' => $_SERVER['HTTP_REFERER'] ?? null,
                'csrfToken' => $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null,
            ],
            $ownerIds
        );

        if (!$decision['allowed']) {
            narrrfs_admin_fail_closed($decision['status'], $decision['code'], $decision['allow']);
        }

        narrrfs_touch_admin_authority($_SESSION);
        narrrfs_consume_admin_csrf($_SESSION, $decision['consumeCsrf']);
        if ($decision['consumeCsrf']) {
            session_write_close();
        }

        return (string)$decision['subject'];
    } catch (Throwable) {
        narrrfs_admin_fail_closed(500, 'authorization_unavailable');
    }
}
