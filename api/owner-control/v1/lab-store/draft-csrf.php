<?php
declare(strict_types=1);

// Owner Control M1 — Lab Store private-draft CSRF issuer.
//
// This is deliberately separate from the shared Owner Control issuer. It can
// create only a short-lived, one-time token scoped to one private-draft write
// action. Its exact-Origin POST policy is checked before token issuance;
// it opens no SQLite connection and cannot publish an offer.

require_once __DIR__ . '/../../../config/admin-authorization.php';

$routeKey = 'owner_control.lab_store_drafts.csrf.v1';
narrrfs_require_admin_request($routeKey);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

try {
    $rawBody = file_get_contents('php://input');
    $requestBody = json_decode(
        is_string($rawBody) ? $rawBody : '',
        true,
        4,
        JSON_THROW_ON_ERROR
    );
    if (
        !is_array($requestBody)
        || count($requestBody) !== 1
        || !array_key_exists('action', $requestBody)
        || !is_string($requestBody['action'])
    ) {
        throw new InvalidArgumentException('Invalid Lab Store draft CSRF action.');
    }

    // Do not accept a scope from the caller. Each accepted action maps to the
    // one immutable route scope that may consume its token.
    $scopeByAction = [
        'create' => 'owner_control.lab_store_drafts.create.v1',
        'update' => 'owner_control.lab_store_drafts.update.v1',
        'upload_media' => 'owner_control.lab_store_drafts.media_upload.v1',
    ];
    $scope = $scopeByAction[$requestBody['action']] ?? null;
    if (!is_string($scope)) {
        throw new InvalidArgumentException('Invalid Lab Store draft CSRF action.');
    }

    $csrfToken = narrrfs_issue_admin_csrf_token($_SESSION, null, null, $scope);

    // Release the session lock after persisting the one-time token. The next
    // successful draft create/update consumes it before domain code executes.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    echo json_encode(
        [
            'success' => true,
            'capability' => 'lab_store_drafts_csrf',
            'version' => 1,
            'csrf_token' => $csrfToken,
            'expires_in' => NARRRFS_ADMIN_CSRF_TTL_SECONDS,
        ],
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $error) {
    error_log('[OWNER CONTROL] Lab Store draft CSRF issuance failed: ' . $error->getMessage());

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    http_response_code($error instanceof InvalidArgumentException || $error instanceof JsonException ? 400 : 500);
    echo json_encode(['success' => false, 'error' => $error instanceof InvalidArgumentException || $error instanceof JsonException ? 'invalid_action' : 'csrf_unavailable'], JSON_UNESCAPED_SLASHES);
}
