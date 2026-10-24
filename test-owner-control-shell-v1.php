<?php
declare(strict_types=1);

putenv('NARRRFS_OWNER_CANARY_ENABLED=1');
putenv('NARRRFS_OWNER_SHELL_ENABLED=1');

$policies = require __DIR__ . '/api/config/admin-route-policies.php';

if (!is_array($policies)) {
    fwrite(STDERR, "FAIL: policy registry is not an array\n");
    exit(1);
}

if (($policies['default'] ?? null) !== 'deny') {
    fwrite(STDERR, "FAIL: default policy is not deny\n");
    exit(1);
}

$routes = $policies['routes'] ?? null;

if (!is_array($routes)) {
    fwrite(STDERR, "FAIL: route registry missing\n");
    exit(1);
}

$expectedRouteKeys = [
    'owner_control.canary.v1',
    'owner_control.shell.v1',
];

foreach ($expectedRouteKeys as $routeKey) {
    if (!isset($routes[$routeKey])) {
        fwrite(
            STDERR,
            "FAIL: missing route policy {$routeKey}\n"
        );
        exit(1);
    }
}

$shellPolicy = $routes['owner_control.shell.v1'];

$expectedShellPolicy = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => false,
    'methods' => ['GET', 'HEAD'],
    'csrf' => false,
    'contentTypes' => [],
    'origins' => [],
    'allowRefererFallback' => false,
];

if ($shellPolicy !== $expectedShellPolicy) {
    fwrite(
        STDERR,
        "FAIL: shell policy contract differs\n"
    );

    var_export($shellPolicy);

    exit(1);
}

$shellSource = file_get_contents(
    __DIR__ . '/owner-control/v1/index.php'
);

if (!is_string($shellSource)) {
    fwrite(STDERR, "FAIL: shell source missing\n");
    exit(1);
}

$requiredMarkers = [
    "narrrfs_require_admin_request('owner_control.shell.v1')",
    "NARRRFS_SESSION_READ_ONLY",
    "No legacy Admin API dependency",
    "no database access",
];

foreach ($requiredMarkers as $marker) {
    if (!str_contains($shellSource, $marker)) {
        fwrite(
            STDERR,
            "FAIL: shell marker missing: {$marker}\n"
        );
        exit(1);
    }
}

$forbiddenMarkers = [
    '/api/admin/',
    'tbl_',
    'new PDO',
    'new SQLite3',
    'fetch(',
];

foreach ($forbiddenMarkers as $marker) {
    if (str_contains($shellSource, $marker)) {
        fwrite(
            STDERR,
            "FAIL: forbidden shell marker found: {$marker}\n"
        );
        exit(1);
    }
}

echo "PASS: owner-control local shell V1 contract\n";
echo "DEFAULT_POLICY=deny\n";
echo "OWNER_ONLY=true\n";
echo "MUTATING=false\n";
echo "METHODS=GET,HEAD\n";
echo "DB_ACCESS=none\n";
echo "LEGACY_ADMIN_API_CALLS=none\n";
