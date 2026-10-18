<?php
declare(strict_types=1);

function oauth_callback_redirect_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$callbackSource = file_get_contents(__DIR__ . '/api/auth/callback.php');
oauth_callback_redirect_test_assert(is_string($callbackSource), 'OAuth callback source is readable');

oauth_callback_redirect_test_assert(
    str_contains($callbackSource, 'narrrfs_consume_oauth_state($_SESSION, $receivedState)'),
    'callback still consumes server-side OAuth state'
);
oauth_callback_redirect_test_assert(
    str_contains($callbackSource, 'narrrfs_establish_discord_oauth_session('),
    'callback still establishes and rotates the trusted Discord session'
);
oauth_callback_redirect_test_assert(
    str_contains($callbackSource, 'narrrfs_oauth_redirect_target($oauth, $finalRedirect)'),
    'callback destination still comes from the state-bound OAuth target'
);
oauth_callback_redirect_test_assert(
    str_contains($callbackSource, '$productionRedirectAllowlist = ['),
    'production callback has an exact server-side redirect allowlist'
);
oauth_callback_redirect_test_assert(
    str_contains($callbackSource, "'/profile.html'") &&
        str_contains($callbackSource, "'/public/profile.html'"),
    'production callback preserves both evidenced profile destinations'
);
oauth_callback_redirect_test_assert(
    str_contains($callbackSource, "!in_array(\$finalRedirect, \$productionRedirectAllowlist, true)"),
    'production callback rejects non-allowlisted state-bound destinations'
);
oauth_callback_redirect_test_assert(
    str_contains($callbackSource, "\$finalRedirect = \$oauth['default_redirect']"),
    'rejected production destinations fall back to the configured profile'
);
oauth_callback_redirect_test_assert(
    str_contains($callbackSource, 'window.location.replace(" . $targetForJavaScript . ")'),
    'successful production callback follows only the encoded validated target'
);
oauth_callback_redirect_test_assert(
    substr_count($callbackSource, 'window.location.') === 1,
    'callback contains exactly one browser navigation operation'
);

foreach (['adminRedirect', 'adminAuthenticated', 'adminAuthTime'] as $obsoleteBrowserFlag) {
    oauth_callback_redirect_test_assert(
        !str_contains($callbackSource, $obsoleteBrowserFlag),
        'callback does not read or write obsolete browser flag ' . $obsoleteBrowserFlag
    );
}

oauth_callback_redirect_test_assert(
    !preg_match('~/(?:api/)?admin(?:-interface\\.html|/|$)~i', $callbackSource),
    'callback contains no admin destination'
);
oauth_callback_redirect_test_assert(
    !str_contains($callbackSource, 'localStorage.getItem('),
    'stale browser state cannot select the callback destination'
);
oauth_callback_redirect_test_assert(
    str_contains($callbackSource, 'JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT'),
    'validated target is encoded for its JavaScript context'
);

echo "PASS: Discord OAuth callback uses only the state-bound redirect target\n";
