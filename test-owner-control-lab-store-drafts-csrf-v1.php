<?php
declare(strict_types=1);

require_once __DIR__ . '/api/config/admin-authorization.php';

function draft_csrf_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

$gate = 'NARRRFS_OWNER_LAB_STORE_DRAFTS_WRITE_ENABLED';
$priorGate = getenv($gate);
$mediaGate = 'NARRRFS_OWNER_LAB_STORE_DRAFTS_MEDIA_UPLOAD_ENABLED';
$priorMediaGate = getenv($mediaGate);
putenv($gate . '=1');
putenv($mediaGate . '=1');

try {
    $policies = require __DIR__ . '/api/config/admin-route-policies.php';
    $issuerPolicy = $policies['routes']['owner_control.lab_store_drafts.csrf.v1'];
    $createPolicy = $policies['routes']['owner_control.lab_store_drafts.create.v1'];
    $updatePolicy = $policies['routes']['owner_control.lab_store_drafts.update.v1'];
    $mediaPolicy = $policies['routes']['owner_control.lab_store_drafts.media_upload.v1'];
    $owner = '12345678901234567';
    $now = 2000000000;
    $makeSession = static function () use ($owner, $now): array {
        return [
            'discord_id' => $owner,
            NARRRFS_ADMIN_PROVENANCE_SESSION_KEY => NARRRFS_ADMIN_AUTH_PROVENANCE,
            NARRRFS_ADMIN_AUTH_ISSUED_AT_SESSION_KEY => $now,
            NARRRFS_ADMIN_AUTH_LAST_SEEN_AT_SESSION_KEY => $now,
            NARRRFS_ADMIN_AUTH_EXPIRES_AT_SESSION_KEY => $now + NARRRFS_ADMIN_AUTH_ABSOLUTE_SECONDS,
        ];
    };
    $session = $makeSession();

    $issuerRequest = [
        'method' => 'POST',
        'contentType' => 'application/json',
        'origin' => 'https://narrrfs.world',
    ];
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($issuerPolicy, $session, $issuerRequest, [$owner], $now)['allowed'] === true,
        'dedicated issuer accepts only the trusted owner with exact Origin'
    );
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($issuerPolicy, [], $issuerRequest, [$owner], $now)['status'] === 401,
        'dedicated issuer rejects anonymous callers before token issuance'
    );
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($issuerPolicy, $session, ['method' => 'GET'], [$owner], $now)['status'] === 405,
        'dedicated issuer rejects non-POST before token issuance'
    );
    draft_csrf_assert(
        narrrfs_admin_authorization_decision(
            $issuerPolicy,
            $session,
            ['method' => 'POST', 'contentType' => 'application/json', 'origin' => 'https://attacker.invalid'],
            [$owner],
            $now
        )['status'] === 403,
        'dedicated issuer rejects a non-allowlisted Origin before token issuance'
    );
    draft_csrf_assert(
        !array_key_exists('csrfScope', $issuerPolicy)
        && $createPolicy['csrfScope'] === 'owner_control.lab_store_drafts.create.v1'
        && $updatePolicy['csrfScope'] === 'owner_control.lab_store_drafts.update.v1'
        && $mediaPolicy['csrfScope'] === 'owner_control.lab_store_drafts.media_upload.v1',
        'issuer has no generic scope and each M1 action has an immutable distinct scope'
    );

    $createToken = narrrfs_issue_admin_csrf_token(
        $session,
        $now,
        static fn (int $bytes): string => str_repeat('a', $bytes),
        'owner_control.lab_store_drafts.create.v1'
    );
    $createRequest = [
        'method' => 'POST',
        'contentType' => 'application/json',
        'origin' => 'https://narrrfs.world',
        'csrfToken' => $createToken,
    ];
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($createPolicy, $session, $createRequest, [$owner], $now)['allowed'] === true,
        'create token authorizes only the create action'
    );
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($updatePolicy, $session, $createRequest, [$owner], $now)['status'] === 403,
        'create token is denied by the update action'
    );
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($createPolicy, $makeSession(), $createRequest, [$owner], $now)['status'] === 403,
        'create token is denied in another owner session'
    );

    $genericPolicy = $createPolicy;
    unset($genericPolicy['csrfScope']);
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($genericPolicy, $session, $createRequest, [$owner], $now)['status'] === 403,
        'action-scoped token cannot authorize an unscoped mutation'
    );
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($createPolicy, $session, $createRequest, [$owner], $now + NARRRFS_ADMIN_CSRF_TTL_SECONDS)['status'] === 403,
        'expired create token fails closed'
    );
    narrrfs_consume_admin_csrf_session($session);
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($createPolicy, $session, $createRequest, [$owner], $now)['status'] === 403,
        'consumed create token cannot replay'
    );

    $updateSession = $makeSession();
    $updateToken = narrrfs_issue_admin_csrf_token(
        $updateSession,
        $now,
        static fn (int $bytes): string => str_repeat('b', $bytes),
        'owner_control.lab_store_drafts.update.v1'
    );
    $updateRequest = [
        'method' => 'POST',
        'contentType' => 'application/json',
        'origin' => 'https://narrrfs.world',
        'csrfToken' => $updateToken,
    ];
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($updatePolicy, $updateSession, $updateRequest, [$owner], $now)['allowed'] === true,
        'update token authorizes only the update action'
    );
    draft_csrf_assert(
        narrrfs_admin_authorization_decision($createPolicy, $updateSession, $updateRequest, [$owner], $now)['status'] === 403,
        'update token is denied by the create action'
    );

    $mediaSession = $makeSession();
    $mediaToken = narrrfs_issue_admin_csrf_token($mediaSession, $now, static fn (int $bytes): string => str_repeat('c', $bytes), 'owner_control.lab_store_drafts.media_upload.v1');
    $mediaRequest = ['method' => 'POST', 'contentType' => 'multipart/form-data; boundary=test', 'origin' => 'https://narrrfs.world', 'csrfToken' => $mediaToken];
    draft_csrf_assert(narrrfs_admin_authorization_decision($mediaPolicy, $mediaSession, $mediaRequest, [$owner], $now)['allowed'] === true, 'upload token authorizes only multipart media upload');
    $wrongActionRequest = $mediaRequest;
    $wrongActionRequest['contentType'] = 'application/json';
    draft_csrf_assert(narrrfs_admin_authorization_decision($createPolicy, $mediaSession, $wrongActionRequest, [$owner], $now)['status'] === 403, 'upload token is denied by draft create');
    narrrfs_consume_admin_csrf_session($mediaSession);
    draft_csrf_assert(narrrfs_admin_authorization_decision($mediaPolicy, $mediaSession, $mediaRequest, [$owner], $now)['status'] === 403, 'consumed upload token cannot replay');

    $endpoint = file_get_contents(__DIR__ . '/api/owner-control/v1/lab-store/draft-csrf.php');
    draft_csrf_assert(
        is_string($endpoint)
        && str_contains($endpoint, "\$routeKey = 'owner_control.lab_store_drafts.csrf.v1'")
        && str_contains($endpoint, 'narrrfs_require_admin_request($routeKey)')
        && str_contains($endpoint, "'create' => 'owner_control.lab_store_drafts.create.v1'")
        && str_contains($endpoint, "'update' => 'owner_control.lab_store_drafts.update.v1'")
        && str_contains($endpoint, "'upload_media' => 'owner_control.lab_store_drafts.media_upload.v1'")
        && str_contains($endpoint, 'count($requestBody) !== 1')
        && !str_contains($endpoint, 'SQLite3') && !str_contains($endpoint, 'openDatabase'),
        'issuer allowlists action-to-scope mapping and has no database access'
    );
} finally {
    $priorGate === false ? putenv($gate) : putenv($gate . '=' . $priorGate);
    $priorMediaGate === false ? putenv($mediaGate) : putenv($mediaGate . '=' . $priorMediaGate);
}

echo "PASS: Lab Store M1 dedicated CSRF suite\n";
