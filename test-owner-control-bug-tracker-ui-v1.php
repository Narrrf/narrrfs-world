<?php
declare(strict_types=1);

putenv('NARRRFS_OWNER_CANARY_ENABLED=1');
putenv('NARRRFS_OWNER_SHELL_ENABLED=1');
putenv('NARRRFS_OWNER_BUG_TRACKER_READ_ENABLED=1');
putenv('NARRRFS_OWNER_BUG_TRACKER_UI_ENABLED=1');

$policies = require __DIR__
    . '/api/config/admin-route-policies.php';

$routeKey = 'owner_control.bug_tracker.ui.v1';

$policy = $policies['routes'][$routeKey] ?? null;

$expected = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => false,
    'methods' => ['GET', 'HEAD'],
    'csrf' => false,
    'contentTypes' => [],
    'origins' => [],
    'allowRefererFallback' => false,
];

if ($policy !== $expected) {
    fwrite(
        STDERR,
        "FAIL: Bug Tracker UI policy differs\n"
    );

    exit(1);
}

$page = file_get_contents(
    __DIR__ . '/owner-control/v1/bug-tracker.php'
);

if (!is_string($page)) {
    fwrite(
        STDERR,
        "FAIL: Bug Tracker UI missing\n"
    );

    exit(1);
}

$required = [
    "owner_control.bug_tracker.ui.v1",
    "/api/owner-control/v1/bug-tracker.php",
    "credentials: 'same-origin'",
    "cache: 'no-store'",
    "const pageSize = 50",
    "textContent",
    "replaceChildren",
];

foreach ($required as $marker) {
    if (!str_contains($page, $marker)) {
        fwrite(
            STDERR,
            "FAIL: required marker missing: {$marker}\n"
        );

        exit(1);
    }
}

$forbidden = [
    '/api/admin/',
    'innerHTML',
    'insertAdjacentHTML',
    'document.write',
    'document.cookie',
    'localStorage',
    'sessionStorage',
    'new SQLite3',
    'new PDO',
];

foreach ($forbidden as $marker) {
    if (str_contains($page, $marker)) {
        fwrite(
            STDERR,
            "FAIL: forbidden marker found: {$marker}\n"
        );

        exit(1);
    }
}

$home = file_get_contents(
    __DIR__ . '/owner-control/v1/index.php'
);

if (!is_string($home)) {
    fwrite(
        STDERR,
        "FAIL: Owner Control home missing\n"
    );

    exit(1);
}

if (
    !str_contains(
        $home,
        "'status' => 'AVAILABLE'"
    )
    || !str_contains(
        $home,
        "'href' => '/owner-control/v1/bug-tracker.php'"
    )
) {
    fwrite(
        STDERR,
        "FAIL: Bug Tracker home capability not available\n"
    );

    exit(1);
}

echo "PASS: Owner Control Bug Tracker UI V1 contract\n";
echo "OWNER_ONLY=true\n";
echo "MUTATING=false\n";
echo "DB_ACCESS=none\n";
echo "LEGACY_ADMIN_API=none\n";
echo "DOM_RENDERING=textContent\n";
echo "PAGE_SIZE=50\n";
echo "HOME_STATUS=AVAILABLE\n";
