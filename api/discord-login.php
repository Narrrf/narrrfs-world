<?php
require_once __DIR__ . '/config/oauth.php';
require_once __DIR__ . '/config/admin-session.php';

try {
    $oauth = narrrfs_oauth_configuration();
} catch (RuntimeException $error) {
    http_response_code(403);
    exit('Local OAuth is not available.');
}

$redirect = narrrfs_oauth_redirect_target(
    $oauth,
    isset($_GET['redirect']) ? (string)$_GET['redirect'] : null
);

try {
    $state = narrrfs_issue_oauth_state($_SESSION, $redirect);
} catch (Throwable $error) {
    http_response_code(500);
    exit('Unable to initialize OAuth session.');
}

$authorizationParameters = [
    'client_id' => $oauth['client_id'],
    'response_type' => 'code',
    'redirect_uri' => $oauth['redirect_uri'],
    'scope' => 'guilds+identify+guilds.members.read',
    'state' => $state,
];

header('Location: https://discord.com/oauth2/authorize?' . http_build_query($authorizationParameters, '', '&', PHP_QUERY_RFC3986));
exit;
