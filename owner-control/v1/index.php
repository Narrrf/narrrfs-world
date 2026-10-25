<?php
declare(strict_types=1);

// 🔐 Narrrfs World Owner Control V2 shell.
//
// DEVS FOR DECADES:
// This page is presentation only. It grants no administrative capability by
// itself. Access must pass the same trusted Discord OAuth session,
// server-side owner allowlist, authority lifetime and deny-by-default route
// policy used by Owner Control APIs.
//
// The legacy Admin Interface and legacy Admin API namespace remain separate
// contained systems and are never an authorization dependency of this shell.

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../api/config/admin-authorization.php';

narrrfs_require_admin_request('owner_control.shell.v1');

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

$requestHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));

$isLocal = $requestHost === 'localhost'
    || str_starts_with($requestHost, 'localhost:');

$environmentLabel = $isLocal
    ? 'LOCAL XAMPP'
    : 'OWNER CONTROL';

$dashboardAvailable =
    getenv('NARRRFS_OWNER_DASHBOARD_UI_ENABLED') === '1'
    && getenv('NARRRFS_OWNER_DASHBOARD_SUMMARY_ENABLED') === '1';

$userManagementAvailable =
    getenv('NARRRFS_OWNER_USERS_UI_ENABLED') === '1'
    && getenv('NARRRFS_OWNER_USER_SEARCH_ENABLED') === '1';

$playerProfilesAvailable =
    getenv('NARRRFS_OWNER_PLAYER_PROFILES_UI_ENABLED') === '1'
    && getenv('NARRRFS_OWNER_PLAYER_PROFILE_READ_ENABLED') === '1';

$capabilities = [
    [
        'name' => 'Dashboard',
        'status' => $dashboardAvailable ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Six read-only operational metrics from the guarded Dashboard Summary.',
        'href' => $dashboardAvailable
            ? '/owner-control/v1/dashboard.php'
            : null,
    ],
    [
        'name' => 'User Management',
        'status' => $userManagementAvailable ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Owner-only, read-only search by username or Discord ID.',
        'href' => $userManagementAvailable
            ? '/owner-control/v1/users.php'
            : null,
    ],
    [
        'name' => 'Missions Status',
        'status' => 'REVIEW',
        'description' => 'Mission visibility is a future read-only candidate.',
    ],
    [
        'name' => 'Point Management',
        'status' => 'PROTECTED',
        'description' => 'DSPOINC economy mutation. Legacy backend is not reusable as-is.',
    ],
    [
        'name' => 'Store Management',
        'status' => 'PROTECTED',
        'description' => 'Catalog and inventory mutations require dedicated review.',
    ],
    [
        'name' => 'Lab Store',
        'status' => 'PROTECTED',
        'description' => 'LP, DSPOINC and Genetic Item boundaries are protected.',
    ],
    [
        'name' => 'Quest System',
        'status' => 'REVIEW',
        'description' => 'Read visibility may return before any quest mutation.',
    ],
    [
        'name' => 'Game Management',
        'status' => 'REVIEW',
        'description' => 'Game status requires capability-by-capability verification.',
    ],
    [
        'name' => 'Boss Management',
        'status' => 'PROTECTED',
        'description' => 'Boss reward and economy behavior is protected.',
    ],
    [
        'name' => 'Boss Notifications',
        'status' => 'PROTECTED',
        'description' => 'Legacy implementation is explicitly not approved for reuse.',
    ],
    [
        'name' => 'Discord Config',
        'status' => 'PROTECTED',
        'description' => 'Discord mutations require a dedicated integration review.',
    ],
    [
        'name' => 'Holder Verification',
        'status' => 'PROTECTED',
        'description' => 'Ownership and role state require protected-domain review.',
    ],
    [
        'name' => 'Player Profiles',
        'status' => $playerProfilesAvailable ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Owner-only, read-only account, game, inventory, role and Genetic Item slot visibility.',
        'href' => $playerProfilesAvailable ? '/owner-control/v1/player-profiles.php' : null,
    ],
    [
        'name' => 'Lab / Trait System',
        'status' => 'PROTECTED',
        'description' => 'Permanent Traits, Abilities and Lab progression are protected.',
    ],
    [
        'name' => 'Cheese Guide',
        'status' => 'REVIEW',
        'description' => 'Informational capability awaiting source mapping.',
    ],
    [
        'name' => 'Community Funds',
        'status' => 'PROTECTED',
        'description' => 'Financial data and mutations require economy review.',
    ],
    [
        'name' => 'Bug Tracker',
        'status' => 'AVAILABLE',
        'description' => 'Read-only bug dashboard and paginated report list.',
        'href' => '/owner-control/v1/bug-tracker.php',
    ],
    [
        'name' => 'Twitter Missions',
        'status' => 'REVIEW',
        'description' => 'Read visibility must be verified before migration.',
    ],
    [
        'name' => 'Database Overview',
        'status' => 'PROTECTED',
        'description' => 'Database structure and utilities require infrastructure review.',
    ],
    [
        'name' => 'Security',
        'status' => 'PROTECTED',
        'description' => 'Security tooling remains narrowly scoped and fail closed.',
    ],
    [
        'name' => 'Partners',
        'status' => 'REVIEW',
        'description' => 'Partner information requires source verification.',
    ],
    [
        'name' => 'Reward Chamber',
        'status' => 'PROTECTED',
        'description' => 'Reward and inventory operations are protected.',
    ],
    [
        'name' => 'SPOINC Bridge',
        'status' => 'PROTECTED',
        'description' => 'SPOINC / DSPOINC settlement is protected.',
    ],
];


function owner_control_escape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Narrrfs World Owner Control</title>

    <style>
        :root {
            color-scheme: dark;
            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background: #0d0f12;
            color: #f5f7fa;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;

            background:
                radial-gradient(
                    circle at top,
                    #292318 0,
                    #12151a 38rem,
                    #0d0f12 70rem
                );
        }

        main {
            width: min(1180px, calc(100% - 2rem));
            margin: 0 auto;
            padding: 3rem 0 5rem;
        }

        .eyebrow {
            margin: 0 0 .5rem;
            color: #e2b85b;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: clamp(2rem, 6vw, 4rem);
            line-height: 1;
        }

        .lede {
            max-width: 760px;
            margin: 1rem 0 0;
            color: #aeb6c2;
            line-height: 1.6;
        }

        .security-panel {
            margin-top: 2rem;
            padding: 1.25rem;

            border: 1px solid #2d333d;
            border-radius: 18px;

            background: rgba(18, 21, 26, .9);
        }

        .security-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(190px, 1fr));

            gap: .75rem;
            margin-top: 1rem;
        }

        .security-item {
            padding: .9rem 1rem;
            border-radius: 12px;
            background: #171b21;
        }

        .security-item strong {
            display: block;
            margin-bottom: .25rem;
        }

        .pass {
            color: #8ee6a5;
        }

        .cards {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(250px, 1fr));

            gap: 1rem;
            margin-top: 2rem;
        }

        .card {
            padding: 1.1rem;

            border: 1px solid #292f38;
            border-radius: 16px;

            background: rgba(19, 22, 27, .94);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .card h2 {
            margin: 0;
            font-size: 1rem;
        }

        .card p {
            margin: .75rem 0 0;
            color: #aeb6c2;
            font-size: .9rem;
            line-height: 1.5;
        }

        .badge {
            padding: .3rem .6rem;

            border-radius: 999px;

            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
        }

        .badge-review {
            background: #27394a;
            color: #9cd2ff;
        }

        .badge-protected {
            background: #472827;
            color: #ffaca5;
        }

        .badge-available {
            background: #1d3b2a;
            color: #8ee6a5;
        }

        .capability-link {
            display: inline-flex;
            margin-top: 1rem;
            padding: .55rem .8rem;
            border-radius: 10px;
            background: #252c35;
            color: #f5f7fa;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
        }

        .capability-link:hover {
            background: #303844;
        }

        footer {
            margin-top: 3rem;
            padding-top: 1.5rem;

            border-top: 1px solid #292f38;

            color: #7f8996;
            font-size: .8rem;
        }
    </style>
</head>

<body>
<main>
    <p class="eyebrow">
        <?= owner_control_escape($environmentLabel) ?>
        · Owner Control V2
    </p>

    <h1>Narrrfs World Owner Control</h1>

    <p class="lede">
        Secure administration is being restored one capability at a time.
        Legacy Admin Interface APIs remain isolated. Protected capabilities
        stay unavailable until their own migration review is complete.
    </p>

    <section
        class="security-panel"
        aria-labelledby="security-title"
    >
        <h2 id="security-title">Security boundary</h2>

        <div class="security-grid">
            <div class="security-item">
                <strong class="pass">✓ Authenticated</strong>
                Trusted Discord OAuth server session
            </div>

            <div class="security-item">
                <strong class="pass">✓ Owner verified</strong>
                Server-side owner allowlist
            </div>

            <div class="security-item">
                <strong class="pass">✓ Fail closed</strong>
                Exact Owner Control route policy
            </div>

            <div class="security-item">
                <strong class="pass">✓ Legacy isolated</strong>
                No legacy Admin API dependency
            </div>
        </div>
    </section>

    <section
        class="cards"
        aria-label="Owner Control capabilities"
    >
        <?php foreach ($capabilities as $capability): ?>
            <?php
            $status = (string)$capability['status'];

            $badgeClass = match ($status) {
                'PROTECTED' => 'badge-protected',
                'AVAILABLE' => 'badge-available',
                default => 'badge-review',
            };
            ?>

            <article class="card">
                <div class="card-header">
                    <h2>
                        <?= owner_control_escape(
                            (string)$capability['name']
                        ) ?>
                    </h2>

                    <span
                        class="badge <?= owner_control_escape(
                            $badgeClass
                        ) ?>"
                    >
                        <?= owner_control_escape($status) ?>
                    </span>
                </div>

                <p>
                    <?= owner_control_escape(
                        (string)$capability['description']
                    ) ?>
                </p>

                <?php if (
                    isset($capability['href'])
                    && is_string($capability['href'])
                ): ?>
                    <a
                        class="capability-link"
                        href="<?= owner_control_escape(
                            $capability['href']
                        ) ?>"
                    >
                        Open capability →
                    </a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <footer>
        Owner Control V2 shell · no database access ·
        no legacy Admin API calls · no economy mutation
    </footer>
</main>
</body>
</html>
