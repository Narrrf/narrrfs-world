<?php
declare(strict_types=1);

// 🔐 Narrrfs World owner-control route policy registry
// DEVS FOR DECADES:
// Every route remains unreachable until it has an exact, owner-approved policy
// and a matching deployment-layer exception. Missing or disabled policy is deny.

return [
    'version' => 1,
    'default' => 'deny',
    'routes' => [
        'owner_control.canary.v1' => [
            'enabled' => getenv('NARRRFS_OWNER_CANARY_ENABLED') === '1',
            'ownerOnly' => true,
            'mutating' => false,
            'methods' => ['GET', 'HEAD'],
            'csrf' => false,
            'contentTypes' => [],
            'origins' => [],
            'allowRefererFallback' => false,
        ],
        'owner_control.shell.v1' => [
            'enabled' => getenv('NARRRFS_OWNER_SHELL_ENABLED') === '1',
            'ownerOnly' => true,
            'mutating' => false,
            'methods' => ['GET', 'HEAD'],
            'csrf' => false,
            'contentTypes' => [],
            'origins' => [],
            'allowRefererFallback' => false,
        ],
        'owner_control.dashboard.summary.v1' => [
            'enabled' => getenv('NARRRFS_OWNER_DASHBOARD_SUMMARY_ENABLED') === '1',
            'ownerOnly' => true,
            'mutating' => false,
            'methods' => ['GET', 'HEAD'],
            'csrf' => false,
            'contentTypes' => [],
            'origins' => [],
            'allowRefererFallback' => false,
        ],
        'owner_control.dashboard.ui.v1' => [
            'enabled' => getenv('NARRRFS_OWNER_DASHBOARD_UI_ENABLED') === '1',
            'ownerOnly' => true,
            'mutating' => false,
            'methods' => ['GET', 'HEAD'],
            'csrf' => false,
            'contentTypes' => [],
            'origins' => [],
            'allowRefererFallback' => false,
        ],
        'owner_control.csrf.v1' => [
            'enabled' => getenv('NARRRFS_OWNER_CSRF_ENABLED') === '1',
            'ownerOnly' => true,
            'mutating' => false,
            'methods' => ['GET', 'HEAD'],
            'csrf' => false,
            'contentTypes' => [],
            'origins' => [],
            'allowRefererFallback' => false,
        ],
        'owner_control.bug_tracker.update.v1' => [
            'enabled' => getenv(
                'NARRRFS_OWNER_BUG_TRACKER_UPDATE_ENABLED'
            ) === '1',
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
        ],
        'owner_control.bug_tracker.details.v1' => [
            'enabled' => getenv(
                'NARRRFS_OWNER_BUG_TRACKER_DETAILS_ENABLED'
            ) === '1',
            'ownerOnly' => true,
            'mutating' => false,
            'methods' => ['GET', 'HEAD'],
            'csrf' => false,
            'contentTypes' => [],
            'origins' => [],
            'allowRefererFallback' => false,
        ],
        'owner_control.bug_tracker.read.v1' => [
            'enabled' => getenv('NARRRFS_OWNER_BUG_TRACKER_READ_ENABLED') === '1',
            'ownerOnly' => true,
            'mutating' => false,
            'methods' => ['GET', 'HEAD'],
            'csrf' => false,
            'contentTypes' => [],
            'origins' => [],
            'allowRefererFallback' => false,
        ],
        'owner_control.bug_tracker.ui.v1' => [
            'enabled' => getenv('NARRRFS_OWNER_BUG_TRACKER_UI_ENABLED') === '1',
            'ownerOnly' => true,
            'mutating' => false,
            'methods' => ['GET', 'HEAD'],
            'csrf' => false,
            'contentTypes' => [],
            'origins' => [],
            'allowRefererFallback' => false,
        ],
    ],
];
