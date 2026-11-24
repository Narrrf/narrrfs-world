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

$geneticCatalogAvailable =
    getenv('NARRRFS_OWNER_GENETIC_CATALOG_UI_ENABLED') === '1'
    && getenv('NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED') === '1';

$questHuntOverviewAvailable =
    getenv('NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_UI_ENABLED') === '1'
    && getenv('NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_READ_ENABLED') === '1';

$cheeseHuntAvailable =
    getenv('NARRRFS_OWNER_CHEESE_HUNT_UI_ENABLED') === '1'
    && getenv('NARRRFS_OWNER_CHEESE_HUNT_READ_ENABLED') === '1';

// The workspace shell may advertise this route once its read-only UI gate is
// enabled. The page itself grants no mutation capability: its CSRF, points,
// and inventory operations remain behind separate server-side gates.
$playerAdjustmentsAvailable =
    getenv('NARRRFS_OWNER_PLAYER_ADJUSTMENT_UI_ENABLED') === '1';

$labStoreOffersAvailable = getenv('NARRRFS_OWNER_LAB_STORE_OFFERS_UI_ENABLED') === '1' && getenv('NARRRFS_OWNER_LAB_STORE_OFFERS_READ_ENABLED') === '1';
$labStoreLiveOffersAvailable = getenv('NARRRFS_OWNER_LAB_STORE_LIVE_OFFERS_UI_ENABLED') === '1' && getenv('NARRRFS_OWNER_LAB_STORE_LIVE_OFFERS_READ_ENABLED') === '1';
$labStoreDraftsAvailable = getenv('NARRRFS_OWNER_LAB_STORE_DRAFTS_UI_ENABLED') === '1' && getenv('NARRRFS_OWNER_LAB_STORE_DRAFTS_READ_ENABLED') === '1' && getenv('NARRRFS_OWNER_LAB_STORE_DRAFTS_WRITE_ENABLED') === '1';

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
        'name' => 'Player Adjustments',
        'status' => $playerAdjustmentsAvailable ? 'AVAILABLE' : 'PROTECTED',
        'description' => 'Owner-only player adjustment workspace. DSPOINC and elixir changes remain separately CSRF- and gate-protected.',
        'href' => $playerAdjustmentsAvailable
            ? '/owner-control/v1/player-adjustments.php'
            : null,
    ],
    [
        // Christmas 2026 planning card only; never expose a link before
        // the owner-only editor route, data contract and gates are approved.
        'name' => 'Christmas Advent 2026',
        'status' => 'REVIEW',
        'description' => '24-day reward manager planned: guaranteed DSPOINC and optional Store item bonuses. No configuration, claims, grants or writes available yet.',
    ],
    [
        'name' => 'Store Management',
        'status' => 'PROTECTED',
        'description' => 'Catalog and inventory mutations require dedicated review.',
    ],
    [
        'name' => 'Lab Store',
        'status' => ($labStoreOffersAvailable || $labStoreDraftsAvailable || $labStoreLiveOffersAvailable) ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Owner-only Lab Store workspace: private drafts, live offers and gated history.',
        'href' => $labStoreDraftsAvailable ? '/owner-control/v1/lab-store-drafts.php' : ($labStoreLiveOffersAvailable ? '/owner-control/v1/lab-store-live-offers.php' : ($labStoreOffersAvailable ? '/owner-control/v1/lab-store-offers.php' : null)),
    ],
    [
        'name' => 'Lab Store Drafts',
        'status' => $labStoreDraftsAvailable ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Owner-only private drafts. Publication, prices, stock, purchases and delivery remain unavailable.',
        'href' => $labStoreDraftsAvailable ? '/owner-control/v1/lab-store-drafts.php' : null,
    ],
    [
        'name' => 'Lab Store Live Offers',
        'status' => $labStoreLiveOffersAvailable ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Owner-only, read-only current shop state. No activation, publish, edit, or purchase controls.',
        'href' => $labStoreLiveOffersAvailable ? '/owner-control/v1/lab-store-live-offers.php' : null,
    ],
    [
        'name' => 'Quest System',
        'status' => $questHuntOverviewAvailable ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Owner-only, read-only aggregate Quest and Hunt status. No player, claim, reward, or action controls.',
        'href' => $questHuntOverviewAvailable
            ? '/owner-control/v1/quest-hunt-overview.php'
            : null,
    ],
    [
        'name' => 'Cheese Hunt',
        'status' => $cheeseHuntAvailable ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Owner-only Cheese Hunt missions and pending claim reviews. Mutations remain separately CSRF- and gate-protected.',
        'href' => $cheeseHuntAvailable
            ? '/owner-control/v1/cheese-hunt.php'
            : null,
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
        'name' => 'Genetic Catalog',
        'status' => $geneticCatalogAvailable ? 'AVAILABLE' : 'REVIEW',
        'description' => 'Owner-only, read-only Genetic catalog metadata. No ownership, pricing, stock, or mutation capability.',
        'href' => $geneticCatalogAvailable
            ? '/owner-control/v1/genetic-catalog.php'
            : null,
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

        /* Christmas preparation preview is presentation-only and cannot issue rewards. */
        .christmas-preview {
            margin-top: 2rem;
            padding: 1.4rem;
            border: 1px solid #6d5832;
            border-radius: 18px;
            background: linear-gradient(145deg, #211d18, #15191c 68%);
        }

        .christmas-preview summary {
            cursor: pointer;
            color: #ffdb8b;
            font-weight: 800;
            font-size: 1.05rem;
        }

        .christmas-preview:focus-within {
            border-color: #c5a263;
        }

        .christmas-preview p {
            color: #c0c5cc;
            line-height: 1.55;
        }

        .christmas-preview-label {
            display: inline-block;
            margin-top: 1rem;
            padding: .3rem .65rem;
            border: 1px solid #695b42;
            border-radius: 999px;
            color: #ffe0a3;
            font-size: .75rem;
        }

        .christmas-day-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: .8rem;
            margin-top: 1.2rem;
        }

        .christmas-day {
            border: 1px solid #36403c;
            border-radius: 13px;
            padding: 1rem;
            background: #17201d;
        }

        .christmas-day--finale {
            border-color: #bd9c5f;
            background: #282319;
        }

        .christmas-day h3 {
            margin: 0 0 .7rem;
            font-size: 1rem;
            color: #ffe0a3;
        }

        .christmas-day p {
            margin: .35rem 0;
            font-size: .8rem;
        }

        /* Owner preview controls are browser-only; they have no persistence authority. */
        .christmas-draft-editor {
            margin: 1.2rem 0;
            padding: 1rem;
            border: 1px solid #49564b;
            border-radius: 13px;
            background: #1b2420;
        }

        .christmas-draft-editor h3 {
            margin: 0 0 .5rem;
            color: #ffe0a3;
        }

        .christmas-draft-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: .85rem;
            margin: 1rem 0;
        }

        .christmas-draft-controls label {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            font-size: .82rem;
            color: #dce4df;
        }

        .christmas-draft-controls input,
        .christmas-draft-controls select {
            width: 100%;
            box-sizing: border-box;
            min-height: 2.6rem;
            border: 1px solid #5a675e;
            border-radius: 8px;
            background: #101815;
            color: #f3f6f4;
            padding: .55rem;
            font: inherit;
        }

        .christmas-draft-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
        }

        .christmas-draft-actions button {
            padding: .7rem 1rem;
            border: 1px solid #a78c59;
            border-radius: 9px;
            background: #3a3121;
            color: #ffe4a9;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .christmas-draft-actions button:focus-visible {
            outline: 3px solid #ffce70;
            outline-offset: 3px;
        }

        .christmas-portable-draft {
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid #56634d;
            border-radius: 10px;
            background: #15211b;
        }
        .christmas-portable-draft h4 {
            margin: 0 0 .8rem;
            color: #ffe0a3;
        }
        .christmas-portable-draft label {
            display: block;
            margin: .6rem 0;
            font-size: .82rem;
            color: #dce4df;
        }
        .christmas-portable-draft input[type="file"],
        .christmas-portable-draft select {
            display: block;
            margin-top: .4rem;
            max-width: 100%;
            min-height: 2.5rem;
            padding: .4rem;
            border: 1px solid #5a675e;
            border-radius: 8px;
            color: #f3f6f4;
            background: #101815;
        }
        .christmas-portable-draft .christmas-draft-actions {
            margin: .7rem 0;
        }

        .christmas-draft-status {
            margin-top: .75rem;
            font-size: .83rem;
            color: #ffe0a3;
        }

        .christmas-preview-note {
            border-left: 3px solid #b28f56;
            padding-left: .8rem;
            font-size: .85rem;
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

    <!-- DEVS FOR DECADES: review-only layout; no endpoint, policy, or claim authority. -->
    <details
        class="christmas-preview"
        id="christmas-advent-2026-preview"
    >
        <summary>🎄 Christmas Advent 2026 · 24-day preparation preview</summary>
        <span class="christmas-preview-label">NOT CONFIGURED · NO REWARDS ENABLED</span>
        <p>
            This owner-only preview illustrates the future 24-day manager.
            Every successfully claimed day is planned to award configured
            DSPOINC plus an optional Store inventory bonus. Nothing below is
            saved, published, or connected to a reward endpoint. For planning,
            December 1–23 may propose 1,000–10,000 DSPOINC per claim; December 24
            may propose 50,000–250,000. This is not a live random-payout rule.
        </p>
        <p class="christmas-preview-note">
            Candidate bonus items are displayed from the current active
            Store catalog (read-only). Catalog visibility does not establish
            giveaway eligibility, stock reservations, event allocations or
            any first-bonus guarantee. None are approved or enabled.
            An inventory item is not a blockchain token airdrop.
        </p>
        <!-- Planning only: no form submit, storage, API, or publication action. -->
        <section
            class="christmas-draft-editor"
            id="christmas-day-draft-editor-v3"
            aria-labelledby="christmas-draft-heading"
        >
            <h3 id="christmas-draft-heading">Draft day simulator · not saved</h3>
            <p>
                Try proposed daily rewards in this browser tab. Applying a preview
                updates only the matching card below. Closing or reloading this
                page clears all previews. Nothing is saved or published.
            </p>
            <div class="christmas-draft-controls">
                <label for="christmas-draft-day">
                    December day
                    <select id="christmas-draft-day">
                        <?php foreach (range(1, 24) as $draftDay): ?>
                            <option value="<?= (int)$draftDay ?>">
                                December <?= (int)$draftDay ?><?= $draftDay === 24 ? ' · Finale' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label for="christmas-draft-dspoinc">
                    Proposed minimum DSPOINC (not saved)
                    <input id="christmas-draft-dspoinc" type="number" min="1000" max="10000" step="1" placeholder="1,000 (days 1–23)">
                </label>
                <label for="christmas-draft-dspoinc-max">
                    Proposed maximum DSPOINC (not saved)
                    <input id="christmas-draft-dspoinc-max" type="number" min="1000" max="10000" step="1" placeholder="10,000 (days 1–23)">
                </label>
                <label for="christmas-draft-claim-cap">
                    Maximum daily claims (illustrative)
                    <input id="christmas-draft-claim-cap" type="number" min="1" max="100000" step="1" placeholder="Required for preflight export">
                </label>
                <label for="christmas-draft-item">
                    Candidate Store bonus
                    <select id="christmas-draft-item">
                        <option value="none">No Store bonus</option>
                    </select>
                    <span id="christmas-draft-catalog-status" class="christmas-draft-status" role="status" aria-live="polite">
                        Loading active Store items (read-only)…
                    </span>
                </label>
                <label for="christmas-draft-chance">
                    Bonus drop chance (%)
                    <input id="christmas-draft-chance" type="number" min="0" max="100" step="0.1" value="0">
                </label>
                <label for="christmas-draft-max-winners">
                    Illustrative daily winner limit
                    <input id="christmas-draft-max-winners" type="number" min="0" max="100000" step="1" value="0">
                </label>
            </div>
            <div class="christmas-draft-actions">
                <button id="christmas-draft-preview-button" type="button">Preview selected day (not saved)</button>
                <button id="christmas-draft-clear-button" type="button">Clear selected day's preview</button>
            </div>
            <!-- Local JSON handoff only; not an authorized policy or server save. -->
            <section class="christmas-portable-draft" aria-labelledby="christmas-portable-heading">
                <h4 id="christmas-portable-heading">Portable 24-day draft · local file only</h4>
                <label for="christmas-draft-per-player-cap">
                    Proposed maximum bonus item wins per player (event-wide)
                    <select id="christmas-draft-per-player-cap">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3" selected>3</option>
                    </select>
                </label>
                <div class="christmas-draft-actions">
                    <button id="christmas-draft-export-button" type="button">Download all 24 days as unapproved JSON</button>
                    <button id="christmas-draft-load-server" type="button">Reload saved draft</button>
                    <button id="christmas-draft-save-server" type="button" disabled>Save draft for review</button>
                </div>
                <label for="christmas-draft-import-input">
                    Reload a previously exported local draft file
                    <input id="christmas-draft-import-input" type="file" accept=".json,application/json">
                </label>
                <p id="christmas-draft-file-status" class="christmas-draft-status" role="status" aria-live="polite">
                    No file loaded or exported. This is not server persistence or an approved policy.
                </p>
                <p class="christmas-preview-note">
                    Range drafts use the Stage 9 V2 offline preflight. An actual random amount
                    must later be drawn once on the server, stored with its claim receipt,
                    and accounted for at the maximum bound. No randomness or payout happens here.
                    First-win guarantee stays disabled pending eligibility and inventory proof.
                    A valid draft is never publishable.
                </p>
            </section>
            <p class="christmas-draft-status" id="christmas-draft-status" role="status" aria-live="polite">
                No preview changes applied. All real rewards remain disabled.
            </p>
            <p class="christmas-preview-note">
                Store catalog lookup is read-only. Stock reservations,
                first-win guarantees, item-grant eligibility and actual token
                delivery remain unimplemented. A preview is not authorization.
            </p>
        </section>
        <div class="christmas-day-grid" aria-label="Unconfigured Advent days">
            <?php foreach (range(1, 24) as $christmasDay): ?>
                <article
                    class="christmas-day<?= $christmasDay === 24 ? ' christmas-day--finale' : '' ?>"
                    data-christmas-day="<?= (int)$christmasDay ?>"
                >
                    <h3>
                        December <?= (int)$christmasDay ?>
                        <?= $christmasDay === 24 ? ' · Finale' : '' ?>
                    </h3>
                    <p>Guaranteed DSPOINC: <strong data-christmas-preview-field="dspoinc">Not configured</strong></p>
                    <p>Store bonus chance: <strong data-christmas-preview-field="bonus">Not configured</strong></p>
                    <p>Item allocation: <strong data-christmas-preview-field="allocation">Not configured</strong></p>
                    <p>Policy status: <strong data-christmas-preview-field="status">Not published</strong></p>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="christmas-preview-note">
            This is an informational layout only: no inputs, Save/Publish
            actions, database reads, draws, inventory changes, or payouts.
            The real owner editor requires separately reviewed routes and
            an immutable, owner-approved financial/Store reward policy.
        </p>
    </details>

    <script>
    // DEVS FOR DECADES: This is a visual-only draft simulator. Never use it as
    // reward authority; the browser contains no published policy or claim data.
    (() => {
        'use strict';
        const root = document.getElementById('christmas-day-draft-editor-v3');
        if (!root) {
            return;
        }
        const dayInput = document.getElementById('christmas-draft-day');
        const amountInput = document.getElementById('christmas-draft-dspoinc');
        const maxAmountInput = document.getElementById('christmas-draft-dspoinc-max');
        const itemInput = document.getElementById('christmas-draft-item');
        const catalogStatus = document.getElementById('christmas-draft-catalog-status');
        const chanceInput = document.getElementById('christmas-draft-chance');
        const winnersInput = document.getElementById('christmas-draft-max-winners');
        const claimCapInput = document.getElementById('christmas-draft-claim-cap');
        const perPlayerCapInput = document.getElementById('christmas-draft-per-player-cap');
        const importInput = document.getElementById('christmas-draft-import-input');
        const fileStatus = document.getElementById('christmas-draft-file-status');
        const status = document.getElementById('christmas-draft-status');
        const previews = new Map(); // In-memory only; reload discards all entries.
        const itemLabels = new Map([['none', 'No Store bonus']]);

        function makeStoreOption(value, label) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label; // Never interpolate Store data into HTML.
            return option;
        }

        // Existing public Store endpoint selects is_active=1 using SELECT only.
        // This enumeration is NOT independent giveaway eligibility or stock proof.
        async function loadActiveStoreCandidates() {
            try {
                const response = await fetch('/api/store/items.php', {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { Accept: 'application/json' }
                });
                if (!response.ok) {
                    throw new Error(`Catalog HTTP ${response.status}`);
                }
                const payload = await response.json();
                if (!payload || payload.success !== true || !Array.isArray(payload.items) ||
                    payload.items.length > 10000) {
                    throw new Error('Invalid catalog response');
                }
                const candidates = new Map();
                for (const row of payload.items) {
                    if (!row || typeof row !== 'object' || Number(row.is_active) !== 1) {
                        continue;
                    }
                    const id = Number(row.item_id);
                    if (!Number.isSafeInteger(id) || id < 1 || id > 1000000 ||
                        typeof row.item_name !== 'string' || !row.item_name.trim()) {
                        continue;
                    }
                    const key = String(id);
                    if (!candidates.has(key)) {
                        candidates.set(key, `#${key} · ${row.item_name.trim().slice(0, 200)}`);
                    }
                }

                itemLabels.clear();
                itemLabels.set('none', 'No Store bonus');
                itemInput.replaceChildren(makeStoreOption('none', 'No Store bonus'));
                for (const [key, label] of candidates) {
                    itemLabels.set(key, label);
                    itemInput.appendChild(makeStoreOption(key, label));
                }
                // Keep compatibility with previously exported #60-or-#61 drafts.
                // Never offer this legacy pair unless both are currently active.
                if (candidates.has('60') && candidates.has('61')) {
                    const pairLabel = '#60 or #61 · legacy selection (not reserved)';
                    itemLabels.set('both', pairLabel);
                    itemInput.appendChild(makeStoreOption('both', pairLabel));
                }
                catalogStatus.textContent = candidates.size
                    ? `${candidates.size} active Store item(s) loaded. Candidates only; stock not reserved.`
                    : 'No active Store items returned. Only No Store bonus is selectable.';
                showDay(selectedDay());
            } catch (error) {
                itemLabels.clear();
                itemLabels.set('none', 'No Store bonus');
                itemInput.replaceChildren(makeStoreOption('none', 'No Store bonus'));
                catalogStatus.textContent = 'Store catalog unavailable or invalid. Bonus choices disabled; no rewards affected.';
                showDay(selectedDay());
            }
        }

        function selectedDay() {
            return Number(dayInput.value);
        }

        function dayCard(day) {
            return document.querySelector(`[data-christmas-day="${day}"]`);
        }

        function setCardField(card, field, value) {
            const target = card.querySelector(`[data-christmas-preview-field="${field}"]`);
            if (target) {
                target.textContent = value;
            }
        }

        // Planning limits only. Never use browser values as payout authority.
        function rangeForDay(day) {
            return day === 24 ? { low: 50000, high: 250000 } : { low: 1000, high: 10000 };
        }

        function formatDraftRange(entry) {
            const minimum = entry.minAmount.toLocaleString('en-US');
            const maximum = entry.maxAmount.toLocaleString('en-US');
            return `${minimum}${entry.minAmount === entry.maxAmount ? '' : '–' + maximum} DSPOINC · unsaved`;
        }

        function showDay(day) {
            const bounds = rangeForDay(day);
            amountInput.min = String(bounds.low);
            amountInput.max = String(bounds.high);
            maxAmountInput.min = String(bounds.low);
            maxAmountInput.max = String(bounds.high);
            amountInput.placeholder = `Minimum ${bounds.low.toLocaleString('en-US')}`;
            maxAmountInput.placeholder = `Maximum ${bounds.high.toLocaleString('en-US')}`;
            const draft = previews.get(day);
            amountInput.value = draft ? String(draft.minAmount) : '';
            maxAmountInput.value = draft ? String(draft.maxAmount) : '';
            itemInput.value = draft && itemLabels.has(draft.item) ? draft.item : 'none';
            chanceInput.value = draft ? String(draft.chance) : '0';
            winnersInput.value = draft ? String(draft.maxWinners) : '0';
            claimCapInput.value = draft ? String(draft.maxDailyClaims) : '';
            status.textContent = draft && !itemLabels.has(draft.item)
                ? `December ${day}: previously previewed item is no longer an active catalog option; select a new item.`
                : draft
                    ? `December ${day}: unsaved preview loaded from this tab.`
                    : `December ${day}: no preview entered. No rewards configured.`;
        }

        function previewDay() {
            const day = selectedDay();
            const minAmount = Number(amountInput.value);
            const maxAmount = Number(maxAmountInput.value);
            const bounds = rangeForDay(day);
            const item = itemInput.value;
            const chance = Number(chanceInput.value);
            const maxWinners = Number(winnersInput.value);
            const maxDailyClaims = Number(claimCapInput.value);
            const card = dayCard(day);

            if (!Number.isInteger(day) || day < 1 || day > 24 || !card ||
                !amountInput.value || !maxAmountInput.value ||
                !Number.isSafeInteger(minAmount) || !Number.isSafeInteger(maxAmount) ||
                minAmount < bounds.low || maxAmount > bounds.high || minAmount > maxAmount ||
                !itemLabels.has(item) ||
                !chanceInput.value || !Number.isFinite(chance) ||
                chance < 0 || chance > 100 ||
                Math.round(chance * 10) !== chance * 10 ||
                !winnersInput.value || !Number.isSafeInteger(maxWinners) ||
                maxWinners < 0 || maxWinners > 100000 ||
                !claimCapInput.value || !Number.isSafeInteger(maxDailyClaims) ||
                maxDailyClaims < 1 || maxDailyClaims > 100000 ||
                maxWinners > maxDailyClaims ||
                (item === 'none' && (chance !== 0 || maxWinners !== 0)) ||
                (item !== 'none' && (chance <= 0 || maxWinners <= 0))) {
                status.textContent = `Preview rejected: DSPOINC must be within ${bounds.low.toLocaleString('en-US')}–${bounds.high.toLocaleString('en-US')} (minimum ≤ maximum). Check claim cap, active bonus item, chance and winner cap.`;
                return;
            }

            previews.set(day, { minAmount, maxAmount, item, chance, maxWinners, maxDailyClaims });
            setCardField(card, 'dspoinc', formatDraftRange({ minAmount, maxAmount }));
            setCardField(card, 'bonus', `${chance}% · ${itemLabels.get(item)}`);
            setCardField(card, 'allocation', `${maxWinners} candidate winner(s) / ${maxDailyClaims} claim(s) · NOT RESERVED`);
            setCardField(card, 'status', 'PREVIEW ONLY · NOT SAVED');
            status.textContent = `December ${day}: browser-only preview updated. No policy published, no grants or payouts.`;
        }

        function clearDay() {
            const day = selectedDay();
            const card = dayCard(day);
            if (!card) {
                return;
            }
            previews.delete(day);
            setCardField(card, 'dspoinc', 'Not configured');
            setCardField(card, 'bonus', 'Not configured');
            setCardField(card, 'allocation', 'Not configured');
            setCardField(card, 'status', 'Not published');
            showDay(day);
        }

        // V2 adds a range contract. The older V1 preflight handles fixed amounts
        // only and must never be treated as a validator of this new range schema.
        function buildPortableDraft() {
            const maxBonusWins = Number(perPlayerCapInput.value);
            if (!Number.isInteger(maxBonusWins) || maxBonusWins < 1 || maxBonusWins > 3) {
                throw new Error('Invalid event-wide bonus win cap.');
            }
            const days = [];
            for (let day = 1; day <= 24; day += 1) {
                const entry = previews.get(day);
                if (!entry) {
                    throw new Error(`December ${day} has no preview. Complete all 24 days.`);
                }
                if (!itemLabels.has(entry.item)) {
                    throw new Error(`December ${day}: Store item is not in the active catalog.`);
                }
                const itemIds = entry.item === 'both' ? [60, 61]
                    : entry.item === 'none' ? [] : [Number(entry.item)];
                days.push({
                    day_number: day,
                    dspoinc_min: entry.minAmount,
                    dspoinc_max: entry.maxAmount,
                    max_daily_claims: entry.maxDailyClaims,
                    bonus: {
                        enabled: itemIds.length > 0,
                        item_ids: itemIds,
                        chance_basis_points: Math.round(entry.chance * 100),
                        max_winners: entry.maxWinners,
                        quantity_per_win: 1
                    }
                });
            }
            return {
                schema_version: 'christmas_advent_2026_draft_v2',
                event_key: 'christmas_advent_2026',
                max_bonus_wins_per_player: maxBonusWins,
                first_bonus_guarantee: { enabled: false },
                days
            };
        }

        // Never send a preview to a server: the download is local-only.
        function exportDraft() {
            try {
                const json = JSON.stringify(buildPortableDraft(), null, 2) + '\n';
                const file = new Blob([json], { type: 'application/json' });
                const url = URL.createObjectURL(file);
                const download = document.createElement('a');
                download.href = url;
                download.download = 'christmas-advent-2026-UNAPPROVED-draft.json';
                document.body.appendChild(download);
                download.click();
                download.remove();
                URL.revokeObjectURL(url);
                fileStatus.textContent = 'Local JSON download requested. Still unapproved and never sent to the server.';
            } catch (error) {
                fileStatus.textContent = `Export blocked: ${error.message}`;
            }
        }

        // Parse into a temporary Map first: a malformed or incompatible import
        // must leave every already-previewed day unchanged.
        function decodePortableDay(input, expectedDay, schemaVersion) {
            const legacy = schemaVersion === 'christmas_advent_2026_draft_v1';
            const minimum = legacy ? input?.guaranteed_dspoinc : input?.dspoinc_min;
            const maximum = legacy ? input?.guaranteed_dspoinc : input?.dspoinc_max;
            const bounds = rangeForDay(expectedDay);
            if (!input || typeof input !== 'object' || Array.isArray(input) ||
                input.day_number !== expectedDay ||
                !Number.isSafeInteger(minimum) || !Number.isSafeInteger(maximum) ||
                minimum < bounds.low || maximum > bounds.high || minimum > maximum ||
                !Number.isSafeInteger(input.max_daily_claims) ||
                input.max_daily_claims < 1 || input.max_daily_claims > 100000 ||
                !input.bonus || typeof input.bonus !== 'object' || Array.isArray(input.bonus)) {
                throw new Error(`Invalid day ${expectedDay} structure.`);
            }
            const bonus = input.bonus;
            const ids = bonus.item_ids;
            const item = Array.isArray(ids) && ids.length === 0 ? 'none'
                : Array.isArray(ids) && ids.length === 1 &&
                    Number.isSafeInteger(ids[0]) && ids[0] >= 1 && ids[0] <= 1000000 &&
                    itemLabels.has(String(ids[0])) ? String(ids[0])
                : Array.isArray(ids) && ids.length === 2 &&
                    ids[0] === 60 && ids[1] === 61 && itemLabels.has('both') ? 'both'
                : null;
            if (item === null || typeof bonus.enabled !== 'boolean' ||
                !Number.isInteger(bonus.chance_basis_points) ||
                bonus.chance_basis_points < 0 || bonus.chance_basis_points > 10000 ||
                bonus.chance_basis_points % 10 !== 0 ||
                !Number.isInteger(bonus.max_winners) ||
                bonus.max_winners < 0 || bonus.max_winners > input.max_daily_claims ||
                bonus.quantity_per_win !== 1 ||
                bonus.enabled !== (item !== 'none') ||
                (item === 'none' && (bonus.chance_basis_points !== 0 || bonus.max_winners !== 0)) ||
                (item !== 'none' && (bonus.chance_basis_points <= 0 || bonus.max_winners <= 0))) {
                throw new Error(`Invalid day ${expectedDay} bonus.`);
            }
            return {
                minAmount: minimum,
                maxAmount: maximum,
                maxDailyClaims: input.max_daily_claims,
                item,
                chance: bonus.chance_basis_points / 100,
                maxWinners: bonus.max_winners
            };
        }

        function showImportedCard(day, entry) {
            const card = dayCard(day);
            if (!card) {
                throw new Error(`Missing December ${day} preview card.`);
            }
            setCardField(card, 'dspoinc', formatDraftRange(entry));
            setCardField(card, 'bonus', `${entry.chance}% · ${itemLabels.get(entry.item)}`);
            setCardField(card, 'allocation', `${entry.maxWinners} candidate winner(s) / ${entry.maxDailyClaims} claim(s) · NOT RESERVED`);
            setCardField(card, 'status', 'PREVIEW ONLY · NOT SAVED');
        }

        async function importDraft() {
            const inputFile = importInput.files && importInput.files[0];
            if (!inputFile) {
                return;
            }
            if (inputFile.size > 131072) {
                fileStatus.textContent = 'Import blocked: file is larger than the 128 KiB planning limit.';
                importInput.value = '';
                return;
            }
            try {
                const proposed = JSON.parse(await inputFile.text());
                if (!proposed || typeof proposed !== 'object' || Array.isArray(proposed) ||
                    !['christmas_advent_2026_draft_v1', 'christmas_advent_2026_draft_v2'].includes(proposed.schema_version) ||
                    proposed.event_key !== 'christmas_advent_2026' ||
                    !Number.isInteger(proposed.max_bonus_wins_per_player) ||
                    proposed.max_bonus_wins_per_player < 1 ||
                    proposed.max_bonus_wins_per_player > 3 ||
                    !proposed.first_bonus_guarantee ||
                    proposed.first_bonus_guarantee.enabled !== false ||
                    !Array.isArray(proposed.days) || proposed.days.length !== 24) {
                    throw new Error('Invalid 24-day schema or unsupported first-win guarantee.');
                }
                const loaded = new Map();
                for (let day = 1; day <= 24; day += 1) {
                    loaded.set(day, decodePortableDay(proposed.days[day - 1], day, proposed.schema_version));
                    if (!dayCard(day)) {
                        throw new Error(`December ${day} card missing.`);
                    }
                }
                previews.clear();
                for (const [day, entry] of loaded) {
                    previews.set(day, entry);
                    showImportedCard(day, entry);
                }
                perPlayerCapInput.value = String(proposed.max_bonus_wins_per_player);
                showDay(selectedDay());
                fileStatus.textContent = '24-day draft loaded into this browser tab only. Not saved or published.';
            } catch (error) {
                fileStatus.textContent = `Import blocked: ${error.message}`;
            } finally {
                importInput.value = '';
            }
        }

        // Owner-only draft persistence remains separate from publication and rewards.
        let savedRevision = null;
        const serverSaveButton = document.getElementById('christmas-draft-save-server');
        async function loadServerDraft() {
            try {
                const response = await fetch('/api/owner-control/v1/christmas-draft.php', { credentials: 'same-origin', cache: 'no-store' });
                const body = await response.json();
                if (!response.ok || body.success !== true) throw new Error('Draft persistence unavailable');
                serverSaveButton.disabled = body.write_enabled !== true;
                const saved = body.result;
                if (saved === null) { savedRevision = 0; fileStatus.textContent = 'Local unsaved draft. No saved server revision.'; return; }
                const proposed = saved.draft;
                if (!proposed || proposed.schema_version !== 'christmas_advent_2026_draft_v2' || proposed.event_key !== 'christmas_advent_2026' ||
                    !Number.isInteger(proposed.max_bonus_wins_per_player) || proposed.max_bonus_wins_per_player < 1 || proposed.max_bonus_wins_per_player > 3 ||
                    proposed.first_bonus_guarantee?.enabled !== false || !Array.isArray(proposed.days) || proposed.days.length !== 24 ||
                    !Number.isSafeInteger(saved.revision) || saved.revision < 1) throw new Error('Saved draft is incompatible');
                const loaded = new Map();
                for (let day = 1; day <= 24; day += 1) {
                    if (!dayCard(day)) throw new Error('Calendar unavailable');
                    loaded.set(day, decodePortableDay(proposed.days[day - 1], day, proposed.schema_version));
                }
                previews.clear();
                for (const [day, entry] of loaded) { previews.set(day, entry); showImportedCard(day, entry); }
                perPlayerCapInput.value = String(proposed.max_bonus_wins_per_player);
                savedRevision = saved.revision; showDay(selectedDay());
                fileStatus.textContent = `Saved on server: revision ${savedRevision}. Not published.`;
            } catch (error) { savedRevision = null; serverSaveButton.disabled = true; fileStatus.textContent = 'Server save unavailable. Local import/export remains available.'; }
        }
        async function saveServerDraft() {
            try {
                if (savedRevision === null) throw new Error('Reload saved draft before saving');
                const draft = buildPortableDraft();
                const csrfResponse = await fetch('/api/owner-control/v1/csrf.php', { credentials: 'same-origin', cache: 'no-store' });
                const csrfBody = await csrfResponse.json();
                if (!csrfResponse.ok || typeof csrfBody.csrf_token !== 'string') throw new Error('Owner authorization unavailable');
                const response = await fetch('/api/owner-control/v1/christmas-draft.php', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfBody.csrf_token },
                    body: JSON.stringify({ revision: savedRevision, draft })
                });
                const body = await response.json();
                if (!response.ok || body.success !== true) {
                    if (response.status === 409) { savedRevision = null; serverSaveButton.disabled = true; }
                    const details = Array.isArray(body.details) ? body.details.filter(value => typeof value === 'string').slice(0, 5).join(', ') : '';
                    throw new Error(response.status === 409 ? 'Draft changed elsewhere; reload before saving' : (details ? `Draft validation: ${details}` : 'Draft save unavailable'));
                }
                savedRevision = body.result.revision;
                const review = body.result.review;
                fileStatus.textContent = `Saved on server: revision ${savedRevision}, not published. Per-account exposure: ${review.min_per_player_dspoinc}–${review.max_per_player_dspoinc} DSPOINC; event at claim caps: ${review.min_event_dspoinc_at_claim_caps}–${review.max_event_dspoinc_at_claim_caps}.`;
            } catch (error) { fileStatus.textContent = error.message; }
        }
        function markLocalDraft() {
            fileStatus.textContent = 'Local unsaved draft. Save to create a new server revision.';
        }
        root.addEventListener('input', markLocalDraft);
        document.getElementById('christmas-draft-preview-button').addEventListener('click', markLocalDraft);
        document.getElementById('christmas-draft-clear-button').addEventListener('click', markLocalDraft);
        document.getElementById('christmas-draft-load-server').addEventListener('click', loadServerDraft);
        document.getElementById('christmas-draft-save-server').addEventListener('click', saveServerDraft);
        document.getElementById('christmas-draft-export-button').addEventListener('click', exportDraft);
        importInput.addEventListener('change', importDraft);
        dayInput.addEventListener('change', () => showDay(selectedDay()));
        document.getElementById('christmas-draft-preview-button').addEventListener('click', previewDay);
        document.getElementById('christmas-draft-clear-button').addEventListener('click', clearDay);
        showDay(selectedDay());
        loadActiveStoreCandidates().then(loadServerDraft); // Load catalog first so saved candidates can be decoded.
    })();
    </script>

    <footer>
        Owner Control V2 shell · read-only Store catalog · no database writes ·
        no legacy Admin API calls · no economy mutation
    </footer>
</main>
</body>
</html>
