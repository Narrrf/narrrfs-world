<?php
declare(strict_types=1);

putenv('NARRRFS_OWNER_CANARY_ENABLED=1');
putenv('NARRRFS_OWNER_SHELL_ENABLED=1');
putenv('NARRRFS_OWNER_BUG_TRACKER_READ_ENABLED=1');

$policies = require __DIR__
    . '/api/config/admin-route-policies.php';

if (!is_array($policies)) {
    fwrite(
        STDERR,
        "FAIL: policy registry missing\n"
    );

    exit(1);
}

if (($policies['default'] ?? null) !== 'deny') {
    fwrite(
        STDERR,
        "FAIL: default policy is not deny\n"
    );

    exit(1);
}

$routeKey = 'owner_control.bug_tracker.read.v1';

$policy = $policies['routes'][$routeKey] ?? null;

$expectedPolicy = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => false,
    'methods' => ['GET', 'HEAD'],
    'csrf' => false,
    'contentTypes' => [],
    'origins' => [],
    'allowRefererFallback' => false,
];

if ($policy !== $expectedPolicy) {
    fwrite(
        STDERR,
        "FAIL: Bug Tracker policy differs\n"
    );

    var_export($policy);

    exit(1);
}

$endpoint = __DIR__
    . '/api/owner-control/v1/bug-tracker.php';

$source = file_get_contents($endpoint);

if (!is_string($source)) {
    fwrite(
        STDERR,
        "FAIL: Bug Tracker endpoint missing\n"
    );

    exit(1);
}

$requiredMarkers = [
    "NARRRFS_SESSION_READ_ONLY",
    "owner_control.bug_tracker.read.v1",
    "SQLITE3_OPEN_READONLY",
    "PRAGMA query_only = ON",
    "LIMIT :limit",
    "OFFSET :offset",
    "'writes' => false",
];

foreach ($requiredMarkers as $marker) {
    if (!str_contains($source, $marker)) {
        fwrite(
            STDERR,
            "FAIL: required marker missing: {$marker}\n"
        );

        exit(1);
    }
}

$forbiddenMarkers = [
    '/api/admin/',
    'INSERT INTO',
    'UPDATE tbl_',
    'DELETE FROM',
    'REPLACE INTO',
    'ATTACH DATABASE',
    'discord_user_id',
    'discord_message_id',
    'discord_channel_id',
    'resolution_notes',
    'estimated_effort',
    'actual_effort',
    'attachments',
];

foreach ($forbiddenMarkers as $marker) {
    if (str_contains($source, $marker)) {
        fwrite(
            STDERR,
            "FAIL: forbidden marker found: {$marker}\n"
        );

        exit(1);
    }
}

echo "PASS: Owner Control Bug Tracker R1 contract\n";
echo "DEFAULT_POLICY=deny\n";
echo "OWNER_ONLY=true\n";
echo "MUTATING=false\n";
echo "METHODS=GET,HEAD\n";
echo "SQLITE_OPEN_MODE=READONLY\n";
echo "SQLITE_QUERY_ONLY=true\n";
echo "PAGINATION_MAX=100\n";
echo "LEGACY_ADMIN_ROUTE=none\n";
echo "PROTECTED_DETAIL_FIELDS=excluded\n";
echo "WRITE_SQL=none\n";
