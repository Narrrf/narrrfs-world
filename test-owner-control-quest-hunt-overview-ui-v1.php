<?php
declare(strict_types=1);

function quest_hunt_overview_ui_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$ui = file_get_contents(__DIR__ . '/owner-control/v1/quest-hunt-overview.php');
$shell = file_get_contents(__DIR__ . '/owner-control/v1/index.php');
quest_hunt_overview_ui_test_assert(is_string($ui) && is_string($shell), 'overview UI and shell sources are readable');
foreach (["narrrfs_require_admin_request('owner_control.quest_hunt_overview.ui.v1')", '/api/owner-control/v1/quests/overview.php', "credentials: 'same-origin'", "cache: 'no-store'", 'textContent', 'Quest/Hunt overview is currently unavailable.'] as $required) {
    quest_hunt_overview_ui_test_assert(str_contains($ui, $required), 'UI includes required display-only behavior: ' . $required);
}
foreach (['/api/admin/', 'admin-interface.html', 'csrf.php', "method: 'POST'", 'innerHTML', 'fetch(\'/api/user/', 'fetch(\'/api/owner-control/v1/quests/claims'] as $forbidden) {
    quest_hunt_overview_ui_test_assert(!str_contains($ui, $forbidden), 'UI excludes legacy or protected request behavior: ' . $forbidden);
}
quest_hunt_overview_ui_test_assert(str_contains($shell, 'NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_UI_ENABLED') && str_contains($shell, 'NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_READ_ENABLED'), 'shell requires both exact default-off gates');
quest_hunt_overview_ui_test_assert(str_contains($shell, '/owner-control/v1/quest-hunt-overview.php'), 'shell names only the display-only overview page');
echo "PASS: owner-control Quest/Hunt overview display-only UI V1\n";
