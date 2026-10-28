<?php
declare(strict_types=1);

function genetic_catalog_ui_test_assert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}

$ui = file_get_contents(__DIR__ . '/owner-control/v1/genetic-catalog.php');
genetic_catalog_ui_test_assert(is_string($ui), 'Genetic Catalog UI source is readable');
foreach (["narrrfs_require_admin_request('owner_control.genetic_catalog.ui.v1')", '/api/owner-control/v1/genetic/catalog.php?', "credentials: 'same-origin'", "cache: 'no-store'", 'textContent', 'owner_control_genetic_catalog_read'] as $required) {
    genetic_catalog_ui_test_assert(str_contains($ui, $required), 'UI includes required display-only behavior: ' . $required);
}
foreach (['/api/admin/', 'admin-interface.html', 'csrf.php', 'method: \'POST\'', 'innerHTML', 'fetch(\'/api/user/', 'fetch(\'/api/marketplace/'] as $forbidden) {
    genetic_catalog_ui_test_assert(!str_contains($ui, $forbidden), 'UI excludes legacy or protected request behavior: ' . $forbidden);
}
echo "PASS: owner-control Genetic Catalog display-only UI V1\n";
