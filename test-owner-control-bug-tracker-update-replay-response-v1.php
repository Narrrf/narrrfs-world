<?php
declare(strict_types=1);
$endpoint = file_get_contents(__DIR__ . '/api/owner-control/v1/bug-tracker-update.php');
$helper = file_get_contents(__DIR__ . '/api/lib/owner-bug-tracker-update-v1.php');
if (!is_string($endpoint) || !is_string($helper)) exit(1);
foreach (['$retryMetadata', "'idempotent_replay'", "'owner_control_version'", "'status_history_written'"] as $marker) {
    if (!str_contains($endpoint, $marker)) throw new RuntimeException('missing replay response marker');
}
$sensitiveMarkers = [
    "'title' => " . '$title',
    "'description' => " . '$description',
    "'resolution_notes' => " . '$resolutionNotes',
];
foreach ($sensitiveMarkers as $sensitive) {
    if (str_contains(substr($helper, strpos($helper, '$receiptResult')), $sensitive)) throw new RuntimeException('free-form content leaked to receipt');
}
if (str_contains(substr($helper, strpos($helper, '$receiptResult')), "'assigned_to' =>")) throw new RuntimeException('assignment value leaked to receipt');
if (str_contains(substr($helper, strpos($helper, '$receiptResult')), "'reporter' =>")) throw new RuntimeException('reporter content leaked to receipt');
if (str_contains($endpoint, "'assigned_to' => \$retryMetadata")) throw new RuntimeException('assignment value leaked to replay response');
echo "PASS test-owner-control-bug-tracker-update-replay-response-v1\n";
