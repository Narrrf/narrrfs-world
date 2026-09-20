<?php
// 🔐 Narrrfs World OAuth Route Configuration
// DEVS FOR DECADES:
// Production OAuth remains pinned to narrrfs.world. Local OAuth is disabled
// unless it is explicitly enabled on the exact localhost host, so browser
// storage can never become an alternative identity authority.

function narrrfs_oauth_request_host(): string
{
    $hostHeader = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
    if ($hostHeader === '') {
        return '';
    }

    return explode(':', $hostHeader, 2)[0];
}

function narrrfs_oauth_is_exact_localhost(): bool
{
    return narrrfs_oauth_request_host() === 'localhost';
}

function narrrfs_oauth_local_mode_enabled(): bool
{
    return narrrfs_oauth_is_exact_localhost()
        && getenv('NARRRFS_LOCAL_OAUTH_ENABLED') === '1';
}

/**
 * Return the only OAuth callback configuration allowed for this request.
 * Local development has a fixed callback URI and requires development-only
 * credentials. It never derives a callback URI from the Host header.
 */
function narrrfs_oauth_configuration(): array
{
    if (narrrfs_oauth_is_exact_localhost()) {
        if (!narrrfs_oauth_local_mode_enabled()) {
            throw new RuntimeException('Local OAuth is disabled.');
        }

        $clientId = trim((string)getenv('NARRRFS_LOCAL_DISCORD_CLIENT_ID'));
        $clientSecret = (string)getenv('NARRRFS_LOCAL_DISCORD_SECRET');
        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Local Discord OAuth credentials are not configured.');
        }

        return [
            'is_local' => true,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => 'http://localhost/api/auth/callback.php',
            'base_url' => 'http://localhost',
            'default_redirect' => '/public/profile.html',
            'allowed_redirects' => [
                '/public/profile.html',
                '/public/three.js/3d-riddle-game.html'
            ]
        ];
    }

    return [
        'is_local' => false,
        'client_id' => '1357927342265204858',
        'client_secret' => (string)getenv('DISCORD_SECRET'),
        'redirect_uri' => 'https://narrrfs.world/api/auth/callback.php',
        'base_url' => 'https://narrrfs.world',
        'default_redirect' => '/profile.html',
        'allowed_redirects' => []
    ];
}

/**
 * Local OAuth accepts only known relative return paths. Production preserves
 * its existing relative redirect behavior and its fixed production base URL.
 */
function narrrfs_oauth_redirect_target(array $configuration, ?string $requestedRedirect): string
{
    if (!$configuration['is_local']) {
        return $requestedRedirect ?: $configuration['default_redirect'];
    }

    return in_array($requestedRedirect, $configuration['allowed_redirects'], true)
        ? $requestedRedirect
        : $configuration['default_redirect'];
}
