<?php
declare(strict_types=1);

function diagnostic_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$routePath = __DIR__ . '/api/owner-control/v1/canary.php';
$routeSource = file_get_contents($routePath);

diagnostic_test_assert(
    is_string($routeSource),
    'canary source is readable'
);

diagnostic_test_assert(
    str_contains($routeSource, "define('NARRRFS_SESSION_READ_ONLY', true)"),
    'diagnostic retains read-only session mode'
);

diagnostic_test_assert(
    str_contains($routeSource, "NARRRFS_OWNER_DIAGNOSTIC_TOKEN"),
    'diagnostic requires a temporary server-side token'
);

diagnostic_test_assert(
    str_contains($routeSource, "HTTP_X_OWNER_DIAGNOSTIC_TOKEN"),
    'diagnostic requires the matching request header'
);

diagnostic_test_assert(
    str_contains($routeSource, 'hash_equals($diagnosticToken, $requestDiagnosticToken)'),
    'diagnostic token comparison is timing-safe'
);

diagnostic_test_assert(
    str_contains($routeSource, 'narrrfs_admin_session_is_current($_SESSION)'),
    'diagnostic requires a current trusted admin-authority session'
);

diagnostic_test_assert(
    str_contains($routeSource, "NARRRFS_ADMIN_PROVENANCE_SESSION_KEY"),
    'diagnostic verifies trusted OAuth provenance'
);

diagnostic_test_assert(
    str_contains($routeSource, "getenv(NARRRFS_ADMIN_OWNER_ENV, true)"),
    'diagnostic compares local-only process environment'
);

diagnostic_test_assert(
    str_contains($routeSource, "apache_getenv(NARRRFS_ADMIN_OWNER_ENV, false)"),
    'diagnostic inspects current Apache request environment'
);

diagnostic_test_assert(
    str_contains($routeSource, "apache_getenv(NARRRFS_ADMIN_OWNER_ENV, true)"),
    'diagnostic inspects top-level Apache environment'
);

diagnostic_test_assert(
    str_contains($routeSource, "narrrfs_parse_admin_owner_ids"),
    'diagnostic uses the authoritative allowlist parser'
);

diagnostic_test_assert(
    str_contains($routeSource, "narrrfs_admin_subject_is_owner"),
    'diagnostic uses the authoritative owner comparator'
);

diagnostic_test_assert(
    str_contains($routeSource, "narrrfs_require_admin_request('owner_control.canary.v1')"),
    'ordinary canary requests still use the standard owner guard'
);

diagnostic_test_assert(
    str_contains($routeSource, "'writes' => false"),
    'diagnostic explicitly reports zero writes'
);

$forbiddenPatterns =
    '/\\b(?:PDO|SQLite3|INSERT|UPDATE|DELETE|REPLACE|file_put_contents|fwrite|fopen|unlink|rename|copy|exec|shell_exec|system|passthru|proc_open|curl_)\\b|https?:\\/\\//i';

diagnostic_test_assert(
    !preg_match($forbiddenPatterns, $routeSource),
    'diagnostic adds no DB, filesystem, process, network, or mutation capability'
);

diagnostic_test_assert(
    !preg_match('/\\$_SESSION\\s*\\[.*\\]\\s*=/', $routeSource),
    'diagnostic performs no session write'
);

diagnostic_test_assert(
    !str_contains($routeSource, "NARRRFS_ADMIN_DISCORD_IDS"),
    'diagnostic does not embed an owner ID or raw owner environment name'
);

echo "PASS: temporary owner-control environment diagnostic V1\n";
