<?php
declare(strict_types=1);

// 🔐 Narrrfs World owner-control route policy registry
// DEVS FOR DECADES:
// This registry is deliberately empty in Wave A. A route is unreachable through
// the shared guard until a later, separately approved change adds an exact policy
// and the deployment layer adds an exact-path exception. Missing policy is deny.

return [
    'version' => 1,
    'default' => 'deny',
    'routes' => [],
];
