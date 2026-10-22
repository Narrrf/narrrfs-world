<?php
declare(strict_types=1);

// 🔐 Read trusted owner identity without refreshing or rewriting session state.
define('NARRRFS_SESSION_READ_ONLY', true);
require_once __DIR__ . '/../../config/admin-authorization.php';

narrrfs_require_admin_request('owner_control.canary.v1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'HEAD') {
    exit;
}

echo json_encode([
    'success' => true,
    'capability' => 'owner_control_canary',
    'version' => 1,
    'mode' => 'read_only',
    'writes' => false,
], JSON_UNESCAPED_SLASHES);
