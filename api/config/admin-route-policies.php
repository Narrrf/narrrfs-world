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
    ],
];
