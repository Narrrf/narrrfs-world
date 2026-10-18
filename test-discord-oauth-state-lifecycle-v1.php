<?php
declare(strict_types=1);

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/api/config/admin-session.php';

function oauth_state_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$session = [];
$sequence = 0;
$generator = static function (int $length) use (&$sequence): string {
    $sequence++;
    return str_repeat(chr($sequence), $length);
};

$states = [];
for ($index = 0; $index < 6; $index++) {
    $states[] = narrrfs_issue_oauth_state(
        $session,
        '/public/profile.html',
        1000 + $index,
        $generator
    );
}

oauth_state_test_assert(count($session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY]) === 5, 'pending collection is capped at five');
oauth_state_test_assert(!isset($session[NARRRFS_OAUTH_PENDING_STATES_SESSION_KEY][hash('sha256', $states[0])]), 'oldest pending state is pruned');
oauth_state_test_assert(!str_contains(serialize($session), $states[1]), 'raw OAuth state is never stored server-side');

$valid = narrrfs_consume_oauth_state($session, $states[5], 1010);
oauth_state_test_assert($valid === ['status' => 'valid', 'final_redirect' => '/public/profile.html'], 'valid state returns its bound redirect');
oauth_state_test_assert(narrrfs_consume_oauth_state($session, $states[5], 1010)['status'] === 'invalid', 'consumed state cannot replay');
oauth_state_test_assert(narrrfs_consume_oauth_state($session, null, 1010)['status'] === 'missing', 'missing state is distinguished for canonical restart');
oauth_state_test_assert(narrrfs_consume_oauth_state($session, 'not-hex', 1010)['status'] === 'invalid', 'malformed state is denied');

$expiredSession = [];
$expiredState = narrrfs_issue_oauth_state($expiredSession, '/public/profile.html', 2000, static fn(int $length): string => str_repeat("x", $length));
oauth_state_test_assert(narrrfs_consume_oauth_state($expiredSession, $expiredState, 2600)['status'] === 'invalid', 'state expires at 600 seconds');

$loginSource = file_get_contents(__DIR__ . '/api/discord-login.php');
$callbackSource = file_get_contents(__DIR__ . '/api/auth/callback.php');
oauth_state_test_assert(is_string($loginSource) && is_string($callbackSource), 'OAuth sources are readable');
oauth_state_test_assert(str_contains($loginSource, 'narrrfs_issue_oauth_state'), 'canonical login always issues server-side state');
oauth_state_test_assert(!str_contains($loginSource, 'narrrfs_local_oauth_state'), 'legacy local-only state is removed');
oauth_state_test_assert(str_contains($callbackSource, 'narrrfs_consume_oauth_state'), 'callback consumes state before exchange');
oauth_state_test_assert(str_contains($callbackSource, "redirect=%2Fpublic%2Fprofile.html', true, 303"), 'missing state uses fixed 303 canonical restart');
oauth_state_test_assert(strpos($callbackSource, 'narrrfs_consume_oauth_state') < strpos($callbackSource, 'curl_init()'), 'state validation precedes token network setup');

echo "PASS: Discord OAuth state lifecycle B1\n";
