<?php
declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-session.php';
require_once __DIR__ . '/api/config/admin-authorization.php';

function logout_compat_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$now = 2000000000;
$normalSession = ['discord_id' => '222222222222222222', 'normal_user_preference' => 'preserved'];
$legacySession = ['discord_id' => '333333333333333333', 'username' => 'legacy-user'];
$sameOriginRequest = [
    'method' => 'GET',
    'referer' => 'https://narrrfs.world/public/profile.html',
    'fetchSite' => 'same-origin',
];

logout_compat_test_assert(narrrfs_logout_allowed_origin_for_host('localhost') === 'http://localhost', 'localhost maps to fixed local origin');
logout_compat_test_assert(narrrfs_logout_allowed_origin_for_host('narrrfs.world') === 'https://narrrfs.world', 'production host maps to fixed production origin');
logout_compat_test_assert(narrrfs_logout_allowed_origin_for_host('example.invalid') === null, 'unknown host fails closed');

logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, $sameOriginRequest, 'https://narrrfs.world')['allowed'],
    'normal Discord user may request a logout-only CSRF token'
);
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($legacySession, $sameOriginRequest, 'https://narrrfs.world')['allowed'],
    'legacy normal session remains able to enter secure logout flow'
);
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision([], $sameOriginRequest, 'https://narrrfs.world')['status'] === 401,
    'unauthenticated token request is denied'
);
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, ['method' => 'POST'], 'https://narrrfs.world')['status'] === 405,
    'logout token issuer rejects unsupported methods'
);
$missingOriginRequest = ['method' => 'GET'];
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, $missingOriginRequest, 'https://narrrfs.world')['status'] === 403,
    'logout token issuer rejects requests without same-origin evidence'
);
$crossOriginRequest = $sameOriginRequest;
$crossOriginRequest['origin'] = 'https://example.invalid';
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, $crossOriginRequest, 'https://narrrfs.world')['status'] === 403,
    'cross-origin token request is denied'
);
$malformedOriginRequest = $sameOriginRequest;
$malformedOriginRequest['origin'] = 'null';
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, $malformedOriginRequest, 'https://narrrfs.world')['status'] === 403,
    'present malformed Origin cannot fall back to Referer or fetch metadata'
);
$credentialOriginRequest = $sameOriginRequest;
$credentialOriginRequest['origin'] = 'https://attacker@narrrfs.world';
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, $credentialOriginRequest, 'https://narrrfs.world')['status'] === 403,
    'credential-bearing Origin is rejected'
);
$pathOriginRequest = $sameOriginRequest;
$pathOriginRequest['origin'] = 'https://narrrfs.world/public/profile.html';
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, $pathOriginRequest, 'https://narrrfs.world')['status'] === 403,
    'path-bearing Origin is rejected rather than normalized'
);
$malformedRefererRequest = ['method' => 'GET', 'referer' => 'not-a-url', 'fetchSite' => 'same-origin'];
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, $malformedRefererRequest, 'https://narrrfs.world')['status'] === 403,
    'present malformed Referer cannot fall back to fetch metadata'
);
logout_compat_test_assert(
    narrrfs_logout_csrf_issue_decision($normalSession, ['method' => 'GET', 'fetchSite' => 'same-origin'], 'https://narrrfs.world')['allowed'],
    'modern same-origin fetch metadata is accepted when GET omits Origin and Referer'
);

$logoutToken = narrrfs_issue_logout_csrf_token(
    $normalSession,
    $now,
    static fn(int $length): string => str_repeat("\x0b", $length)
);
$replacementToken = narrrfs_issue_logout_csrf_token(
    $normalSession,
    $now + 1,
    static fn(int $length): string => str_repeat("\x0c", $length)
);
logout_compat_test_assert(!narrrfs_logout_csrf_is_current($normalSession, $logoutToken, $now + 1), 'reissuing invalidates the prior logout token');
$logoutToken = $replacementToken;
$validLogout = [
    'method' => 'POST',
    'contentType' => 'application/json',
    'origin' => 'https://narrrfs.world',
    'csrfToken' => $logoutToken,
];
logout_compat_test_assert(
    narrrfs_logout_decision($normalSession, $validLogout, 'https://narrrfs.world', $now + 1)['allowed'],
    'valid ordinary-user POST logout is accepted'
);
$getAttemptSession = $normalSession;
logout_compat_test_assert(
    narrrfs_logout_decision($getAttemptSession, ['method' => 'GET'], 'https://narrrfs.world', $now)['status'] === 405,
    'legacy GET logout is denied'
);
logout_compat_test_assert($getAttemptSession === $normalSession, 'GET decision does not mutate the session');

$missingCsrf = $validLogout;
unset($missingCsrf['csrfToken']);
logout_compat_test_assert(narrrfs_logout_decision($normalSession, $missingCsrf, 'https://narrrfs.world', $now)['status'] === 403, 'missing CSRF is denied');
$invalidCsrf = $validLogout;
$invalidCsrf['csrfToken'] = str_repeat('c', 64);
logout_compat_test_assert(narrrfs_logout_decision($normalSession, $invalidCsrf, 'https://narrrfs.world', $now)['status'] === 403, 'invalid CSRF is denied');
$wrongOrigin = $validLogout;
$wrongOrigin['origin'] = 'https://example.invalid';
logout_compat_test_assert(narrrfs_logout_decision($normalSession, $wrongOrigin, 'https://narrrfs.world', $now + 1)['status'] === 403, 'wrong logout origin is denied');
$pathLogoutOrigin = $validLogout;
$pathLogoutOrigin['origin'] = 'https://narrrfs.world/public/profile.html';
logout_compat_test_assert(narrrfs_logout_decision($normalSession, $pathLogoutOrigin, 'https://narrrfs.world', $now + 1)['status'] === 403, 'path-bearing logout Origin is denied');
$wrongMediaType = $validLogout;
$wrongMediaType['contentType'] = 'text/plain';
logout_compat_test_assert(narrrfs_logout_decision($normalSession, $wrongMediaType, 'https://narrrfs.world', $now + 1)['status'] === 415, 'non-JSON logout is denied');
logout_compat_test_assert(narrrfs_logout_csrf_is_current($normalSession, $logoutToken, $now + 1), 'failed validation does not consume logout token');
logout_compat_test_assert(!narrrfs_logout_csrf_is_current($normalSession, $logoutToken, $now + 301), 'logout token expires at five minutes');
narrrfs_consume_logout_csrf_session($normalSession);
logout_compat_test_assert(narrrfs_logout_decision($normalSession, $validLogout, 'https://narrrfs.world', $now)['status'] === 403, 'replayed CSRF is denied');
logout_compat_test_assert($normalSession['normal_user_preference'] === 'preserved', 'failed/replayed logout leaves ordinary session data intact');

$ownerDecision = narrrfs_admin_authorization_decision(
    [
        'enabled' => true,
        'ownerOnly' => true,
        'mutating' => false,
        'methods' => ['GET'],
        'csrf' => false,
        'contentTypes' => [],
        'origins' => [],
        'allowRefererFallback' => false,
    ],
    $legacySession,
    ['method' => 'GET'],
    [],
    $now
);
logout_compat_test_assert(!$ownerDecision['allowed'], 'ordinary or legacy session gains no owner authority without an allowlist');

echo "PASS: B1 secure logout compatibility\n";
