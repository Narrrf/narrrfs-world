<?php
declare(strict_types=1);

putenv(
    'NARRRFS_OWNER_BUG_TRACKER_UPDATE_ENABLED=1'
);

require_once __DIR__
    . '/api/lib/owner-bug-tracker-update-v1.php';


function owner_bug_update_assert(
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


$registry = require __DIR__
    . '/api/config/admin-route-policies.php';

$policy = $registry['routes'][
    'owner_control.bug_tracker.update.v1'
] ?? null;

$expectedPolicy = [
    'enabled' => true,
    'ownerOnly' => true,
    'mutating' => true,
    'methods' => ['POST'],
    'csrf' => true,
    'contentTypes' => ['application/json'],
    'origins' => [
        'http://localhost',
        'https://narrrfs.world',
    ],
    'allowRefererFallback' => false,
];

owner_bug_update_assert(
    $policy === $expectedPolicy,
    'exact mutation policy matches contract'
);


$endpointSource = file_get_contents(
    __DIR__
    . '/api/owner-control/v1/'
    . 'bug-tracker-update.php'
);

owner_bug_update_assert(
    is_string($endpointSource),
    'update endpoint exists'
);

foreach (
    [
        "owner_control.bug_tracker.update.v1",
        'narrrfs_require_admin_request',
        'SQLITE3_OPEN_READWRITE',
        'owner-bug-tracker-update-v1.php',
        'existing_bot_observer',
    ]
    as $marker
) {
    owner_bug_update_assert(
        str_contains(
            $endpointSource,
            $marker
        ),
        "required endpoint marker exists: {$marker}"
    );
}

foreach (
    [
        '/api/admin/',
        'DISCORD_BOT_SECRET',
        'DISCORD_SECRET',
        'discord.com/api',
        'admin_id',
        'admin_username',
    ]
    as $marker
) {
    owner_bug_update_assert(
        stripos(
            $endpointSource,
            $marker
        ) === false,
        "forbidden endpoint marker absent: {$marker}"
    );
}


$tempPath = tempnam(
    sys_get_temp_dir(),
    'narrrfs-bug-r2-'
);

owner_bug_update_assert(
    is_string($tempPath),
    'temporary SQLite path created'
);

$db = new SQLite3($tempPath);

try {
    owner_bug_update_assert(
        $db->exec('PRAGMA foreign_keys = ON'),
        'temporary DB foreign keys enabled'
    );

    owner_bug_update_assert(
        $db->exec(
            '
            CREATE TABLE tbl_bug_categories (
                category_id INTEGER PRIMARY KEY,
                category_name TEXT NOT NULL
            );

            CREATE TABLE tbl_bug_priorities (
                priority_id INTEGER PRIMARY KEY,
                priority_name TEXT NOT NULL
            );

            CREATE TABLE tbl_bug_statuses (
                status_id INTEGER PRIMARY KEY,
                status_name TEXT NOT NULL,
                is_active INTEGER NOT NULL,
                is_resolved INTEGER NOT NULL
            );

            CREATE TABLE tbl_bug_reports (
                bug_id INTEGER PRIMARY KEY,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                category_id INTEGER NOT NULL,
                priority_id INTEGER NOT NULL,
                status_id INTEGER NOT NULL,
                assigned_to TEXT,
                resolution_notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                resolved_at DATETIME,
                FOREIGN KEY(category_id)
                    REFERENCES tbl_bug_categories(category_id),
                FOREIGN KEY(priority_id)
                    REFERENCES tbl_bug_priorities(priority_id),
                FOREIGN KEY(status_id)
                    REFERENCES tbl_bug_statuses(status_id)
            );

            CREATE TABLE tbl_bug_status_history (
                history_id INTEGER PRIMARY KEY,
                bug_id INTEGER NOT NULL,
                old_status_id INTEGER,
                new_status_id INTEGER NOT NULL,
                changed_by TEXT NOT NULL,
                changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                change_reason TEXT,
                FOREIGN KEY(bug_id)
                    REFERENCES tbl_bug_reports(bug_id),
                FOREIGN KEY(old_status_id)
                    REFERENCES tbl_bug_statuses(status_id),
                FOREIGN KEY(new_status_id)
                    REFERENCES tbl_bug_statuses(status_id)
            );

            CREATE TABLE tbl_bug_assignments (
                assignment_id INTEGER PRIMARY KEY,
                bug_id INTEGER NOT NULL,
                assigned_to TEXT NOT NULL,
                assigned_by TEXT NOT NULL,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                unassigned_at DATETIME,
                assignment_notes TEXT,
                FOREIGN KEY(bug_id)
                    REFERENCES tbl_bug_reports(bug_id)
            );
            '
        ),
        'temporary Bug Tracker schema created'
    );

    owner_bug_update_assert(
        $db->exec(
            "
            INSERT INTO tbl_bug_categories
                (category_id, category_name)
            VALUES
                (1, 'Gameplay');

            INSERT INTO tbl_bug_priorities
                (priority_id, priority_name)
            VALUES
                (1, 'High');

            INSERT INTO tbl_bug_statuses
                (
                    status_id,
                    status_name,
                    is_active,
                    is_resolved
                )
            VALUES
                (1, 'Reported', 1, 0),
                (5, 'Resolved', 1, 1);

            INSERT INTO tbl_bug_reports
                (
                    bug_id,
                    title,
                    description,
                    category_id,
                    priority_id,
                    status_id
                )
            VALUES
                (
                    9001,
                    'Original title',
                    'Original description',
                    1,
                    1,
                    1
                );
            "
        ),
        'temporary fixture inserted'
    );


    $ownerSubject =
        '111111111111111111';

    $resolved = narrrfs_owner_bug_tracker_update(
        $db,
        9001,
        [
            'title' => 'Updated title',
            'description' =>
                'Updated description',
            'category_id' => 1,
            'priority_id' => 1,
            'status_id' => 5,
            'assigned_to' => 'DEECZO',
            'resolution_notes' =>
                'Verified in isolated test.',
            'change_reason' =>
                'R2 isolated status test',
        ],
        $ownerSubject
    );


    owner_bug_update_assert(
        (int)$resolved['bug']['status_id'] === 5,
        'status changed to resolved'
    );

    owner_bug_update_assert(
        $resolved['bug']['resolved_at'] !== null,
        'resolved status sets resolved_at'
    );

    owner_bug_update_assert(
        $resolved['status_history_written'] === true,
        'status history written'
    );

    owner_bug_update_assert(
        $resolved['assignment_changed'] === true,
        'assignment change audited'
    );


    $history = $db->querySingle(
        '
        SELECT COUNT(*)
        FROM tbl_bug_status_history
        WHERE bug_id = 9001
          AND old_status_id = 1
          AND new_status_id = 5
          AND changed_by =
              \'111111111111111111\'
        ',
        false
    );

    owner_bug_update_assert(
        (int)$history === 1,
        'status audit has trusted owner subject'
    );


    $assignment = $db->querySingle(
        '
        SELECT assigned_to
        FROM tbl_bug_assignments
        WHERE bug_id = 9001
        ORDER BY assignment_id DESC
        LIMIT 1
        ',
        true
    );

    owner_bug_update_assert(
        is_array($assignment)
        && $assignment['assigned_to']
            === 'DEECZO',
        'assignment audit created'
    );


    $reopened = narrrfs_owner_bug_tracker_update(
        $db,
        9001,
        [
            'title' => 'Updated title',
            'description' =>
                'Updated description',
            'category_id' => 1,
            'priority_id' => 1,
            'status_id' => 1,
            'assigned_to' => null,
            'resolution_notes' => null,
            'change_reason' =>
                'R2 isolated reopen test',
        ],
        $ownerSubject
    );

    owner_bug_update_assert(
        (int)$reopened['bug']['status_id'] === 1,
        'resolved bug can be reopened'
    );

    owner_bug_update_assert(
        $reopened['bug']['resolved_at'] === null,
        'reopening clears resolved_at'
    );


    $closedAssignment = $db->querySingle(
        '
        SELECT COUNT(*)
        FROM tbl_bug_assignments
        WHERE bug_id = 9001
          AND assigned_to = \'DEECZO\'
          AND unassigned_at IS NOT NULL
        ',
        false
    );

    owner_bug_update_assert(
        (int)$closedAssignment === 1,
        'unassignment closes prior assignment'
    );


    $beforeInvalid = $db->querySingle(
        '
        SELECT title
        FROM tbl_bug_reports
        WHERE bug_id = 9001
        ',
        false
    );

    $invalidRejected = false;

    try {
        narrrfs_owner_bug_tracker_update(
            $db,
            9001,
            [
                'title' =>
                    'Must not commit',
                'description' =>
                    'Must not commit',
                'category_id' => 999,
                'priority_id' => 1,
                'status_id' => 1,
                'assigned_to' => null,
                'resolution_notes' => null,
            ],
            $ownerSubject
        );
    } catch (
        NarrrfsOwnerBugTrackerUpdateException $error
    ) {
        $invalidRejected =
            $error->publicCode()
            === 'invalid_category';
    }

    owner_bug_update_assert(
        $invalidRejected,
        'invalid category rejected'
    );

    $afterInvalid = $db->querySingle(
        '
        SELECT title
        FROM tbl_bug_reports
        WHERE bug_id = 9001
        ',
        false
    );

    owner_bug_update_assert(
        $beforeInvalid === $afterInvalid,
        'failed mutation rolls back completely'
    );


    echo "PASS: Owner Control Bug Tracker Update V1 isolated contract\n";
    echo "OWNER_ONLY=true\n";
    echo "METHOD=POST\n";
    echo "CONTENT_TYPE=application/json\n";
    echo "CSRF=required_one_time\n";
    echo "ORIGIN=exact_allowlist\n";
    echo "TRANSACTION=BEGIN_IMMEDIATE\n";
    echo "STATUS_AUDIT=atomic\n";
    echo "ASSIGNMENT_AUDIT=atomic\n";
    echo "RESOLVED_STATE=tbl_bug_statuses.is_resolved\n";
    echo "REOPEN_CLEARS_RESOLVED_AT=true\n";
    echo "INVALID_LOOKUP_ROLLBACK=true\n";
    echo "ACTING_OWNER=trusted_session_subject\n";
    echo "DISCORD_DIRECT_CALL=none\n";
    echo "REAL_DB_OPENED=NO\n";
    echo "PRODUCTION_WRITE=NO\n";

} finally {
    $db->close();

    if (is_file($tempPath)) {
        unlink($tempPath);
    }
}
