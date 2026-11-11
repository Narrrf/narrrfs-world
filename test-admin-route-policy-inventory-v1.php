<?php
declare(strict_types=1);

$root = __DIR__;
$policyGates = [
    'NARRRFS_OWNER_CANARY_ENABLED',
    'NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED',
    'NARRRFS_OWNER_GENETIC_CATALOG_UI_ENABLED',
    'NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_READ_ENABLED',
    'NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_UI_ENABLED',
    'NARRRFS_OWNER_LAB_STORE_OFFERS_READ_ENABLED',
    'NARRRFS_OWNER_LAB_STORE_OFFERS_UI_ENABLED',
    'NARRRFS_OWNER_LAB_STORE_DRAFTS_READ_ENABLED',
    'NARRRFS_OWNER_LAB_STORE_DRAFTS_CATALOG_READ_ENABLED',
    'NARRRFS_OWNER_LAB_STORE_DRAFTS_MEDIA_UPLOAD_ENABLED',
    'NARRRFS_OWNER_LAB_STORE_DRAFTS_WRITE_ENABLED',
    'NARRRFS_OWNER_LAB_STORE_DRAFTS_UI_ENABLED',
    'NARRRFS_OWNER_BUG_TRACKER_READ_ENABLED',
    'NARRRFS_OWNER_BUG_TRACKER_UI_ENABLED',
    'NARRRFS_OWNER_BUG_TRACKER_DETAILS_ENABLED',
    'NARRRFS_OWNER_CSRF_ENABLED',
    'NARRRFS_OWNER_BUG_TRACKER_UPDATE_ENABLED',
];
$priorGates = [];
foreach ($policyGates as $gate) {
    $priorGates[$gate] = getenv($gate);
    putenv($gate);
}
$policies = require $root . '/api/config/admin-route-policies.php';

function policy_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

policy_test_assert(is_array($policies), 'policy registry is an array');
policy_test_assert(($policies['version'] ?? null) === 1, 'policy schema version is exact');
policy_test_assert(($policies['default'] ?? null) === 'deny', 'policy registry defaults to deny');
foreach ([
    'owner_control.canary.v1',
    'owner_control.genetic_catalog.read.v1',
    'owner_control.genetic_catalog.ui.v1',
    'owner_control.quest_hunt_overview.read.v1',
    'owner_control.quest_hunt_overview.ui.v1',
    'owner_control.lab_store_offers.read.v1',
    'owner_control.lab_store_offers.ui.v1',
    'owner_control.lab_store_drafts.read.v1',
    'owner_control.lab_store_drafts.catalog_read.v1',
    'owner_control.lab_store_drafts.media_upload.v1',
    'owner_control.lab_store_drafts.csrf.v1',
    'owner_control.lab_store_drafts.create.v1',
    'owner_control.lab_store_drafts.update.v1',
    'owner_control.lab_store_drafts.ui.v1',
    'owner_control.bug_tracker.read.v1',
    'owner_control.bug_tracker.ui.v1',
    'owner_control.bug_tracker.details.v1',
    'owner_control.csrf.v1',
    'owner_control.bug_tracker.update.v1',
] as $key) {
    $policy = $policies['routes'][$key] ?? null;
    policy_test_assert(is_array($policy), $key . ' policy is present');
    policy_test_assert(($policy['enabled'] ?? null) === false, $key . ' defaults disabled without its exact environment gate');
}

putenv('NARRRFS_OWNER_CANARY_ENABLED=1');
putenv('NARRRFS_OWNER_GENETIC_CATALOG_READ_ENABLED=1');
putenv('NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_READ_ENABLED=1');
putenv('NARRRFS_OWNER_LAB_STORE_OFFERS_READ_ENABLED=1');
putenv('NARRRFS_OWNER_LAB_STORE_OFFERS_UI_ENABLED=1');
putenv('NARRRFS_OWNER_LAB_STORE_DRAFTS_READ_ENABLED=1');
putenv('NARRRFS_OWNER_LAB_STORE_DRAFTS_CATALOG_READ_ENABLED=1');
putenv('NARRRFS_OWNER_LAB_STORE_DRAFTS_MEDIA_UPLOAD_ENABLED=1');
putenv('NARRRFS_OWNER_LAB_STORE_DRAFTS_WRITE_ENABLED=1');
putenv('NARRRFS_OWNER_LAB_STORE_DRAFTS_UI_ENABLED=1');
putenv('NARRRFS_OWNER_BUG_TRACKER_READ_ENABLED=1');
putenv('NARRRFS_OWNER_BUG_TRACKER_UI_ENABLED=1');
putenv('NARRRFS_OWNER_BUG_TRACKER_DETAILS_ENABLED=1');
putenv('NARRRFS_OWNER_CSRF_ENABLED=1');
putenv('NARRRFS_OWNER_BUG_TRACKER_UPDATE_ENABLED=1');
$enabledPolicies = require $root . '/api/config/admin-route-policies.php';
$enabledCanary = $enabledPolicies['routes']['owner_control.canary.v1'] ?? null;
policy_test_assert(is_array($enabledCanary) && ($enabledCanary['enabled'] ?? null) === true, 'exact environment value enables only the canary policy');
$enabledCatalog = $enabledPolicies['routes']['owner_control.genetic_catalog.read.v1'] ?? null;
policy_test_assert(is_array($enabledCatalog) && ($enabledCatalog['enabled'] ?? null) === true, 'exact environment value enables only the Genetic Catalog read policy');
$enabledOverview = $enabledPolicies['routes']['owner_control.quest_hunt_overview.read.v1'] ?? null;
policy_test_assert(is_array($enabledOverview) && ($enabledOverview['enabled'] ?? null) === true, 'exact environment value enables only the Quest/Hunt overview read policy');
$enabledLabRead = $enabledPolicies['routes']['owner_control.lab_store_offers.read.v1'] ?? null;
$enabledLabUi = $enabledPolicies['routes']['owner_control.lab_store_offers.ui.v1'] ?? null;
policy_test_assert(is_array($enabledLabRead) && ($enabledLabRead['enabled'] ?? null) === true, 'exact environment value enables only the Lab Store API policy');
policy_test_assert(is_array($enabledLabUi) && ($enabledLabUi['enabled'] ?? null) === true, 'exact environment value enables only the Lab Store UI policy');
$enabledLabDraftRead = $enabledPolicies['routes']['owner_control.lab_store_drafts.read.v1'] ?? null;
$enabledLabDraftCatalogRead = $enabledPolicies['routes']['owner_control.lab_store_drafts.catalog_read.v1'] ?? null;
$enabledLabDraftMediaUpload = $enabledPolicies['routes']['owner_control.lab_store_drafts.media_upload.v1'] ?? null;
$enabledLabDraftCsrf = $enabledPolicies['routes']['owner_control.lab_store_drafts.csrf.v1'] ?? null;
$enabledLabDraftCreate = $enabledPolicies['routes']['owner_control.lab_store_drafts.create.v1'] ?? null;
$enabledLabDraftUpdate = $enabledPolicies['routes']['owner_control.lab_store_drafts.update.v1'] ?? null;
$enabledLabDraftUi = $enabledPolicies['routes']['owner_control.lab_store_drafts.ui.v1'] ?? null;
policy_test_assert(is_array($enabledLabDraftRead) && ($enabledLabDraftRead['enabled'] ?? null) === true, 'exact environment value enables only the Lab Store draft read policy');
policy_test_assert(is_array($enabledLabDraftCatalogRead) && ($enabledLabDraftCatalogRead['enabled'] ?? null) === true && ($enabledLabDraftCatalogRead['methods'] ?? null) === ['GET', 'HEAD'] && ($enabledLabDraftCatalogRead['mutating'] ?? null) === false, 'exact environment value enables only the Lab Store draft source-picker read policy');
policy_test_assert(is_array($enabledLabDraftMediaUpload) && ($enabledLabDraftMediaUpload['enabled'] ?? null) === true && ($enabledLabDraftMediaUpload['methods'] ?? null) === ['POST'] && ($enabledLabDraftMediaUpload['csrfScope'] ?? null) === 'owner_control.lab_store_drafts.media_upload.v1' && ($enabledLabDraftMediaUpload['contentTypes'] ?? null) === ['multipart/form-data'], 'exact environment value enables only the scoped Lab Store draft media upload policy');
policy_test_assert(is_array($enabledLabDraftCsrf) && ($enabledLabDraftCsrf['enabled'] ?? null) === true && ($enabledLabDraftCsrf['csrfIssuer'] ?? null) === true && ($enabledLabDraftCsrf['methods'] ?? null) === ['POST'] && !array_key_exists('csrfScope', $enabledLabDraftCsrf), 'exact draft write gate enables only the action-selecting dedicated CSRF issuer');
policy_test_assert(is_array($enabledLabDraftCreate) && ($enabledLabDraftCreate['enabled'] ?? null) === true && ($enabledLabDraftCreate['mutating'] ?? null) === true && ($enabledLabDraftCreate['csrfScope'] ?? null) === 'owner_control.lab_store_drafts.create.v1', 'exact environment value enables only the Lab Store draft create policy with its distinct scope');
policy_test_assert(is_array($enabledLabDraftUpdate) && ($enabledLabDraftUpdate['enabled'] ?? null) === true && ($enabledLabDraftUpdate['mutating'] ?? null) === true && ($enabledLabDraftUpdate['csrfScope'] ?? null) === 'owner_control.lab_store_drafts.update.v1', 'exact environment value enables only the Lab Store draft update policy with its distinct scope');
policy_test_assert(is_array($enabledLabDraftUi) && ($enabledLabDraftUi['enabled'] ?? null) === true, 'exact environment value enables only the Lab Store draft UI policy');
$enabledBugTrackerRead = $enabledPolicies['routes']['owner_control.bug_tracker.read.v1'] ?? null;
$enabledBugTrackerUi = $enabledPolicies['routes']['owner_control.bug_tracker.ui.v1'] ?? null;
$enabledBugTrackerDetails = $enabledPolicies['routes']['owner_control.bug_tracker.details.v1'] ?? null;
policy_test_assert(is_array($enabledBugTrackerRead) && ($enabledBugTrackerRead['enabled'] ?? null) === true, 'exact environment value enables only the Bug Tracker read policy');
policy_test_assert(is_array($enabledBugTrackerUi) && ($enabledBugTrackerUi['enabled'] ?? null) === true, 'exact environment value enables only the Bug Tracker UI policy');
policy_test_assert(is_array($enabledBugTrackerDetails) && ($enabledBugTrackerDetails['enabled'] ?? null) === true, 'exact environment value enables only the Bug Tracker details policy');
$enabledOwnerCsrf = $enabledPolicies['routes']['owner_control.csrf.v1'] ?? null;
$enabledBugTrackerUpdate = $enabledPolicies['routes']['owner_control.bug_tracker.update.v1'] ?? null;
policy_test_assert(is_array($enabledOwnerCsrf) && ($enabledOwnerCsrf['enabled'] ?? null) === true, 'exact environment value enables only the Owner Control CSRF policy');
policy_test_assert(is_array($enabledBugTrackerUpdate) && ($enabledBugTrackerUpdate['enabled'] ?? null) === true, 'exact environment value enables only the Bug Tracker update policy');
foreach ($priorGates as $gate => $value) {
    $value === false ? putenv($gate) : putenv($gate . '=' . $value);
}

$containmentPath = $root . '/deploy/apache/narrrfs-emergency-containment.conf';
$dockerfilePath = $root . '/Dockerfile';
$saveScorePath = $root . '/api/dev/save-score.php';

$containment = file_get_contents($containmentPath);
$dockerfile = file_get_contents($dockerfilePath);
$saveScore = file_get_contents($saveScorePath);

policy_test_assert(is_string($containment), 'containment source is readable');
policy_test_assert(is_string($dockerfile), 'Dockerfile is readable');
policy_test_assert(is_string($saveScore), 'save-score source is readable');

policy_test_assert(str_contains($containment, '^/admin-interface\\.html'), 'legacy admin page remains denied');
policy_test_assert(str_contains($containment, '^/api/admin'), 'legacy admin API remains denied');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/canary\.php'), 'owner-control containment retains the exact canary exception');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/genetic/catalog\.php'), 'owner-control containment names the exact Genetic Catalog route');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/quests/overview\.php'), 'owner-control containment names the exact Quest/Hunt overview route');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/lab-store/offers\.php'), 'owner-control containment names the exact Lab Store API route');
foreach (['drafts', 'draft-details', 'draft-create', 'draft-update', 'draft-csrf'] as $draftRoute) policy_test_assert(str_contains($containment, '/api/owner-control/v1/lab-store/' . $draftRoute . '\\.php'), 'owner-control containment names exact Lab Store draft route ' . $draftRoute);
policy_test_assert(str_contains($containment, '/api/owner-control/v1/lab-store/draft-sources\\.php'), 'owner-control containment names the exact Lab Store draft source-picker route');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/lab-store/draft-media-upload\\.php'), 'owner-control containment names the exact Lab Store draft media upload route');
policy_test_assert(!str_contains($containment, '/api/owner-control/v1/lab-store/(?:'), 'containment has no broad Lab Store exception');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/bug-tracker\.php'), 'owner-control containment names the exact Bug Tracker list route');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/bug-tracker-details\.php'), 'owner-control containment names the exact Bug Tracker details route');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/csrf\.php'), 'owner-control containment names the exact Bug Tracker CSRF route');
policy_test_assert(str_contains($containment, '/api/owner-control/v1/bug-tracker-update\.php'), 'owner-control containment names the exact Bug Tracker update route');
policy_test_assert(!str_contains($containment, '/api/owner-control/v1/bug-tracker(?:/|$)'), 'containment has no broad Bug Tracker exception');
policy_test_assert(!str_contains($containment, '/api/owner-control/v1/csrf(?:/|$)'), 'containment has no broad CSRF exception');
policy_test_assert(str_contains($containment, '^/db'), 'database URL remains denied');
policy_test_assert(str_contains($containment, '/var/www/html/db'), 'database filesystem path remains denied');
policy_test_assert(str_contains($dockerfile, 'a2enconf narrrfs-emergency-containment'), 'Docker image enables containment');
policy_test_assert(str_contains($dockerfile, 'apache2ctl configtest'), 'Docker image validates Apache configuration');

policy_test_assert(
    str_contains($saveScore, 'SECURITY RECOVERY V2 — authenticated score + audited DSPOINC persistence'),
    'save-score is explicitly scoped to guarded score/economy recovery'
);

policy_test_assert(
    str_contains($saveScore, 'INSERT INTO tbl_tetris_scores') &&
    str_contains($saveScore, 'INSERT INTO tbl_glyph_memory_scores') &&
    str_contains($saveScore, 'INSERT INTO tbl_user_scores') &&
    str_contains($saveScore, 'INSERT INTO tbl_score_adjustments'),
    'save-score uses only the approved leaderboard and DSPOINC audit targets'
);

policy_test_assert(
    !str_contains($saveScore, 'tbl_wl_role_grants') &&
    !preg_match('/INSERT\s+INTO\s+tbl_season_settings/i', $saveScore) &&
    !preg_match('/\b(?:UPDATE|DELETE|REPLACE)\b\s+/i', $saveScore),
    'save-score does not restore WL, season-setting, update, delete, or replace mutations'
);

policy_test_assert(
    str_contains($saveScore, "'leaderboard_only' => true") &&
    str_contains($saveScore, "'leaderboard_only' => false") &&
    str_contains($saveScore, "'rewards_credited' => false") &&
    str_contains($saveScore, "'rewards_credited' => true"),
    'save-score distinguishes Glyph leaderboard-only from classic rewarded results'
);

$trackedCallers = [];
$guardPath = realpath($root . '/api/config/admin-authorization.php');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/api', FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    if (realpath($file->getPathname()) === $guardPath) {
        continue;
    }
    $contents = file_get_contents($file->getPathname());
    if (is_string($contents) && str_contains($contents, 'narrrfs_require_admin_request')) {
        $trackedCallers[] = $file->getPathname();
    }
}
policy_test_assert(
    in_array(realpath($root . '/api/owner-control/v1/genetic/catalog.php'), array_map('realpath', $trackedCallers), true),
    'Genetic Catalog route calls the exact owner guard'
);
policy_test_assert(
    in_array(realpath($root . '/api/owner-control/v1/quests/overview.php'), array_map('realpath', $trackedCallers), true),
    'Quest/Hunt overview route calls the exact owner guard'
);

policy_test_assert(in_array(realpath($root . '/api/owner-control/v1/lab-store/offers.php'), array_map('realpath', $trackedCallers), true), 'Lab Store offers route calls the exact owner guard');
foreach (['drafts.php', 'draft-details.php', 'draft-create.php', 'draft-update.php', 'draft-csrf.php'] as $draftRoute) policy_test_assert(in_array(realpath($root . '/api/owner-control/v1/lab-store/' . $draftRoute), array_map('realpath', $trackedCallers), true), 'Lab Store draft route calls the exact owner guard: ' . $draftRoute);
policy_test_assert(in_array(realpath($root . '/api/owner-control/v1/lab-store/draft-sources.php'), array_map('realpath', $trackedCallers), true), 'Lab Store draft source-picker route calls the exact owner guard');
policy_test_assert(in_array(realpath($root . '/api/owner-control/v1/lab-store/draft-media-upload.php'), array_map('realpath', $trackedCallers), true), 'Lab Store draft media upload route calls the exact owner guard');
policy_test_assert(in_array(realpath($root . '/api/owner-control/v1/bug-tracker.php'), array_map('realpath', $trackedCallers), true), 'Bug Tracker list route calls the exact owner guard');
policy_test_assert(in_array(realpath($root . '/api/owner-control/v1/bug-tracker-details.php'), array_map('realpath', $trackedCallers), true), 'Bug Tracker details route calls the exact owner guard');
echo "PASS: admin route policy inventory Stage B2\n";
