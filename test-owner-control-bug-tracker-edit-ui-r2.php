<?php
declare(strict_types=1);

$pagePath = __DIR__
    . '/owner-control/v1/bug-tracker.php';

$page = file_get_contents($pagePath);

function owner_bug_edit_ui_assert(
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


owner_bug_edit_ui_assert(
    is_string($page),
    'Bug Tracker UI exists'
);


$requiredMarkers = [
    'View / Edit',
    'Save Changes',
    'bugDialog',
    'bugEditForm',
    'bugStatusHistory',
    'bugAssignmentHistory',
    'bugComments',
    'bugDiscordLink',
    "const bugTrackerMutationUiEnabled = true",
    '/api/owner-control/v1/bug-tracker-details.php?id=',
    '/api/owner-control/v1/csrf.php',
    '/api/owner-control/v1/bug-tracker-update.php',
    "'X-CSRF-Token': csrfToken",
    "'Content-Type': 'application/json'",
    "credentials: 'same-origin'",
    "cache: 'no-store'",
    'https://discord.com/channels/',
    "const discordGuildId = '1332015322546311218'",
    'isDiscordSnowflake',
    'textContent',
    'replaceChildren',
    'showModal',
];

foreach ($requiredMarkers as $marker) {
    owner_bug_edit_ui_assert(
        str_contains($page, $marker),
        "required marker exists: {$marker}"
    );
}


$forbiddenMarkers = [
    '/api/admin/',
    'innerHTML',
    'insertAdjacentHTML',
    'document.write',
    'document.cookie',
    'localStorage',
    'sessionStorage',
    'new SQLite3',
    'new PDO',
    'DISCORD_BOT_SECRET',
    'DISCORD_SECRET',
    'discord.com/api',
];

foreach ($forbiddenMarkers as $marker) {
    owner_bug_edit_ui_assert(
        stripos($page, $marker) === false,
        "forbidden marker absent: {$marker}"
    );
}


owner_bug_edit_ui_assert(
    str_contains(
        $page,
        'id="saveBugButton"'
    ),
    'save button exists'
);

owner_bug_edit_ui_assert(
    preg_match(
        '/id="saveBugButton"\s+disabled/',
        $page
    ) === 1,
    'save button is fail-closed in HTML'
);

owner_bug_edit_ui_assert(
    str_contains(
        $page,
        'bugTrackerMutationUiEnabled !== true'
    ),
    'save handler refuses disabled mutation gate'
);

owner_bug_edit_ui_assert(
    str_contains(
        $page,
        'saveBugButton.disabled ='
    ),
    'save state is managed centrally'
);

owner_bug_edit_ui_assert(
    str_contains(
        $page,
        'cell.colSpan = 8'
    ),
    'empty/error row spans new action column'
);

owner_bug_edit_ui_assert(
    str_contains(
        $page,
        '<th>Actions</th>'
    ),
    'Actions table column exists'
);


echo "PASS: Owner Control Bug Tracker Edit UI R2 contract\n";
echo "SERVER_PAGE_POLICY=read_only\n";
echo "DETAILS_ENDPOINT=wired\n";
echo "CSRF_ENDPOINT=wired\n";
echo "UPDATE_ENDPOINT=wired\n";
echo "SAVE_UI_ENABLED=YES\n";
echo "FAIL_CLOSED=true\n";
echo "DOM_RENDERING=textContent\n";
echo "DISCORD_LINK=validated_snowflakes_only\n";
echo "DIRECT_DISCORD_API=none\n";
echo "LEGACY_ADMIN_API=none\n";
echo "DB_ACCESS_FROM_UI=none\n";
echo "REAL_DB_WRITE=NO\n";
