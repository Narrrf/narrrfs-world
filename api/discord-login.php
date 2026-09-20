<?php
require_once __DIR__ . '/config/oauth.php';

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

if ($oauth['is_local']) {
    require_once __DIR__ . '/config/session.php';
    try {
        $state = bin2hex(random_bytes(32));
    } catch (Throwable $error) {
        http_response_code(500);
        exit('Unable to initialize local OAuth session.');
    }

    $_SESSION['narrrfs_local_oauth_state'] = $state;
} else {
    // Preserve the established production session and callback behavior.
    session_start();
    $state = null;
}

$_SESSION['oauth_final_redirect'] = $redirect;

$authorizationParameters = [
    'client_id' => $oauth['client_id'],
    'response_type' => 'code',
    'redirect_uri' => $oauth['redirect_uri'],
    'scope' => 'guilds+identify+guilds.members.read'
];
if ($state !== null) {
    $authorizationParameters['state'] = $state;
}

header('Location: https://discord.com/oauth2/authorize?' . http_build_query($authorizationParameters, '', '&', PHP_QUERY_RFC3986));
exit;
