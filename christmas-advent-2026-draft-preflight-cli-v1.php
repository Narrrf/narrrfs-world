<?php
/**
 * Local-only preflight for portable Christmas 2026 owner draft JSON files.
 *
 * DEVS FOR DECADES:
 * A VALID draft is NEVER a published or owner-approved payout policy. This
 * script does not open SQLite, call an API, mutate a file or grant rewards.
 * It is CLI-only and consumes a bounded local JSON document.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/api/lib/christmas-advent-2026-draft-preflight-v1.php';

if ($argc !== 2 || !is_file($argv[1]) || !is_readable($argv[1]) ||
    filesize($argv[1]) === false || filesize($argv[1]) > 131072) {
    fwrite(STDERR, "USAGE: php christmas-advent-2026-draft-preflight-cli-v1.php /path/to/draft.json (max 128 KiB)\n");
    exit(2);
}

try {
    $contents = file_get_contents($argv[1]);
    if ($contents === false) {
        throw new RuntimeException('draft_read_failed');
    }
    $data = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($data) || array_is_list($data)) {
        throw new RuntimeException('draft_requires_object');
    }
    $result = christmas26_draft_preflight_v1($data);
    $result['status'] = 'UNAPPROVED_PLANNING_DOCUMENT';
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    exit($result['valid'] ? 0 : 1);
} catch (Throwable $error) {
    fwrite(STDERR, 'STOP: cannot validate draft: ' . $error->getMessage() . PHP_EOL);
    exit(2);
}
