<?php
declare(strict_types=1);

putenv(
    'NARRRFS_OWNER_BUG_TRACKER_DETAILS_ENABLED=1'
);

function owner_bug_details_assert(
    bool $condition,
    string $message
): void {
    if (!$condition) {
        fwrite(
            STDERR,
            "FAIL: {$message}\n"
        );

        exit(1);
    }
}


$policyPath = __DIR__
    . '/api/config/admin-route-policies.php';

$endpointPath = __DIR__
    . '/api/owner-control/v1/'
    . 'bug-tracker-details.php';


$registry = require $policyPath;

$policy = $registry['routes'][
    'owner_control.bug_tracker.details.v1'
] ?? null;


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


owner_bug_details_assert(
    $policy === $expectedPolicy,
    'exact details policy matches contract'
);


$source = file_get_contents(
    $endpointPath
);

owner_bug_details_assert(
    is_string($source),
    'details endpoint exists'
);


$required = [
    "define('NARRRFS_SESSION_READ_ONLY', true)",
    "owner_control.bug_tracker.details.v1",
    'SQLITE3_OPEN_READONLY',
    'PRAGMA query_only = ON',
    'tbl_bug_reports',
    'tbl_bug_comments',
    'tbl_bug_status_history',
    'tbl_bug_assignments',
    'discord_message_id',
    'discord_channel_id',
    'description',
    'resolution_notes',
    "'writes' => false",
];

foreach ($required as $marker) {
    owner_bug_details_assert(
        str_contains($source, $marker),
        "required marker exists: {$marker}"
    );
}


$forbidden = [
    'INSERT INTO',
    'UPDATE tbl_',
    'DELETE FROM',
    'REPLACE INTO',
    '/api/admin/',
    'DISCORD_BOT_SECRET',
    'DISCORD_SECRET',
    'attachments',
    'estimated_effort',
    'actual_effort',
    'tags',
];

foreach ($forbidden as $marker) {
    owner_bug_details_assert(
        stripos($source, $marker) === false,
        "forbidden marker absent: {$marker}"
    );
}


owner_bug_details_assert(
    preg_match(
        '/\$_GET\s*\[\s*[\'"]id[\'"]\s*\]/',
        $source
    ) === 1,
    'bug ID is explicitly read'
);

owner_bug_details_assert(
    str_contains(
        $source,
        "preg_match('/^[1-9]\\d*$/', \$rawBugId)"
    ),
    'bug ID is strictly numeric'
);


echo "PASS: Owner Control Bug Tracker Details V1 contract\n";
echo "OWNER_ONLY=true\n";
echo "MUTATING=false\n";
echo "METHODS=GET,HEAD\n";
echo "SQLITE_OPEN_MODE=READONLY\n";
echo "SQLITE_QUERY_ONLY=true\n";
echo "COMMENTS=read_only\n";
echo "STATUS_HISTORY=read_only\n";
echo "ASSIGNMENT_HISTORY=read_only\n";
echo "DISCORD_LINKAGE=read_only\n";
echo "WRITE_SQL=none\n";
echo "LEGACY_ADMIN_API=none\n";
