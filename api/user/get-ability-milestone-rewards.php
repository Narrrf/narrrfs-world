<?php
/**
 * Genesis Ability Milestone Rewards — Read Endpoint.
 *
 * Plain language for DEVS:
 * This endpoint checks which milestone reward chests are available for one
 * Genesis mouse. It is read-only. It does not give DSPOINC, does not claim
 * rewards, does not change abilities, and does not modify inventory.
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/genesis-ability-helpers.php';

const WEAPON_MILESTONE_REWARDS = [
    [
        'required_level' => 1,
        'reward_amount' => 25000,
        'title' => 'Weapon Unlock Chest',
        'description' => 'Your Genesis mouse unlocked the Weapons lane.',
    ],
    [
        'required_level' => 5,
        'reward_amount' => 10000,
        'title' => 'Weapon Training Chest',
        'description' => 'Your weapon ability reached level 5.',
    ],
    [
        'required_level' => 10,
        'reward_amount' => 25000,
        'title' => 'Weapon Specialist Chest',
        'description' => 'Your weapon ability reached level 10.',
    ],
    [
        'required_level' => 25,
        'reward_amount' => 75000,
        'title' => 'Weapon Master Chest',
        'description' => 'Your weapon ability reached level 25.',
    ],
    [
        'required_level' => 50,
        'reward_amount' => 150000,
        'title' => 'Legendary Weapon Chest',
        'description' => 'Your weapon ability reached level 50.',
    ],
];


const FITNESS_TRAIT_MILESTONE_REWARDS = [
    [
        'required_trait_level' => 10,
        'milestone_key' => 'fitness_trait_level_10',
        'reward_type' => 'store_item',
        'reward_reference_id' => 51,
        'reward_amount' => 0,
        'reward_currency' => 'ITEM',
        'title' => 'Halfway Fitness Journey Chest',
        'description' => 'YEAHHH! Your Genesis mouse reached Trait Level 10 — halfway to the Weapon Journey.',
        'preview_label' => '500 EMPIRE TOKEN',
        'source' => 'genesis_fitness_trait',
    ],
    [
        'required_trait_level' => 15,
        'milestone_key' => 'fitness_trait_level_15',
        'reward_type' => 'genetic_trait',
        'reward_reference_id' => 69,
        'reward_amount' => 100000,
        'reward_currency' => 'DSPOINC_FALLBACK',
        'title' => 'Deep Training Fitness Chest',
        'description' => 'Deep Training Milestone unlocked — only 5 more trait levels until the Weapon Lane opens.',
        'preview_label' => 'Gun Special Genetic Item or 100,000 DSPOINC fallback',
        'source' => 'genesis_fitness_trait',
    ],
    [
        'required_trait_level' => 20,
        'milestone_key' => 'fitness_trait_level_20',
        'reward_type' => 'store_item',
        'reward_reference_id' => 41,
        'reward_amount' => 0,
        'reward_currency' => 'ITEM',
        'title' => 'Weapon Journey Fitness Chest',
        'description' => 'Weapon Lane unlocked — your Genesis mouse reached Trait Level 20.',
        'preview_label' => '1000 EMPIRE Token',
        'source' => 'genesis_fitness_trait',
    ],
];

/**
 * Return true when running on localhost-style development hosts.
 */
function milestone_read_is_localhost_env(): bool {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * Return the active Discord user id from session.
 *
 * Plain language for DEVS:
 * Production must use the Discord session. Localhost may pass user_id only for
 * controlled local testing, matching the claim endpoint and Reward Chamber test pattern.
 */
function get_active_milestone_user_id(): string {
    if (function_exists('narrrfs_touch_session')) {
        narrrfs_touch_session();
    } else {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    $sessionUserId = trim((string)($_SESSION['discord_id'] ?? ''));
    $request = get_milestone_request_data();
    $requestUserId = trim((string)($request['user_id'] ?? ''));

    if (milestone_read_is_localhost_env() && $requestUserId !== '') {
        error_log("🧬 Ability Milestone Read: Using local request user_id for testing: {$requestUserId}");
        return $requestUserId;
    }

    if ($requestUserId !== '' && $sessionUserId !== '' && $requestUserId !== $sessionUserId) {
        json_response([
            'success' => false,
            'error' => 'Unauthorized: user_id mismatch',
        ], 403);
    }

    return $sessionUserId;
}

/**
 * Return request data from JSON body and query string.
 */
function get_milestone_request_data(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '{}', true);

    if (!is_array($json)) {
        $json = [];
    }

    return array_merge($_GET, $json);
}

/**
 * Return one stable milestone key.
 */
function build_weapon_milestone_key(string $abilityKey, int $requiredLevel): string {
    return 'weapon_' . strtoupper($abilityKey) . '_level_' . $requiredLevel;
}


/**
 * Return one stable Fitness Journey trait milestone key.
 *
 * Plain language for DEVS:
 * Fitness Journey chests are based on highest Genesis trait level.
 * They are not based on seeded Fitness ability rows.
 */
function build_fitness_trait_milestone_key(int $requiredTraitLevel): string {
    return 'fitness_trait_level_' . $requiredTraitLevel;
}

/**
 * Return true when the selected Genesis mouse is controlled by this user.
 */
function user_controls_genesis_mouse(PDO $pdo, string $userId, string $tokenId, string $collection): bool {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM tbl_nft_ownership
        WHERE user_id = ?
          AND token_id = ?
          AND collection = ?
          AND COALESCE(is_verified, 0) = 1
        ORDER BY
          COALESCE(verified_at, '') DESC,
          COALESCE(acquired_at, '') DESC,
          ownership_id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $tokenId, $collection]);

    return (bool)$stmt->fetchColumn();
}

/**
 * Return current weapon ability rows for one Genesis mouse.
 */
function fetch_weapon_ability_rows(PDO $pdo, string $tokenId, string $collection): array {
    $stmt = $pdo->prepare("
        SELECT
            ability_upgrade_id,
            user_id,
            token_id,
            collection,
            category,
            ability_key,
            current_level,
            upgrade_status,
            unlock_source_trait_level
        FROM tbl_nft_ability_upgrades
        WHERE token_id = ?
          AND collection = ?
          AND category = 'Weapons'
          AND ability_key IN ('ATK', 'DEF', 'SPECIAL')
        ORDER BY
            CASE ability_key
                WHEN 'ATK' THEN 1
                WHEN 'DEF' THEN 2
                WHEN 'SPECIAL' THEN 3
                ELSE 99
            END
    ");
    $stmt->execute([$tokenId, $collection]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Return already claimed milestone keys for this user and mouse.
 */
function fetch_claimed_milestone_keys(PDO $pdo, string $userId, string $tokenId, string $collection): array {
    $stmt = $pdo->prepare("
        SELECT milestone_key
        FROM tbl_lab_milestone_rewards
        WHERE user_id = ?
          AND token_id = ?
          AND collection = ?
    ");
    $stmt->execute([$userId, $tokenId, $collection]);

    $keys = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $key) {
        $keys[(string)$key] = true;
    }

    return $keys;
}

/**
 * Return the selected mouse's highest Genesis trait level.
 *
 * Plain language for DEVS:
 * Weapon Ability rewards must not unlock just because seeded Weapon ability rows
 * exist at level 1. The real Weapons lane unlock requires this Genesis mouse to
 * have at least one Genesis trait at level 20+.
 */
function fetch_highest_genesis_trait_level(PDO $pdo, string $tokenId, string $collection): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(current_level), 0)
        FROM tbl_nft_trait_upgrades
        WHERE token_id = ?
          AND collection = ?
    ");
    $stmt->execute([$tokenId, $collection]);

    return (int)$stmt->fetchColumn();
}

try {
    $userId = get_active_milestone_user_id();

    if ($userId === '') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Discord login required',
        ]);
        exit;
    }

    $request = get_milestone_request_data();
    $tokenId = trim((string)($request['token_id'] ?? ''));
    $collection = trim((string)($request['collection'] ?? 'genesis')) ?: 'genesis';

    if ($tokenId === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'token_id is required',
        ]);
        exit;
    }

    if ($collection !== 'genesis') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Only Genesis collection is supported for ability milestones',
        ]);
        exit;
    }

    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if (!user_controls_genesis_mouse($pdo, $userId, $tokenId, $collection)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'You do not control this verified Genesis mouse',
        ]);
        exit;
    }

            $abilityRows = fetch_weapon_ability_rows($pdo, $tokenId, $collection);
    $claimedKeys = fetch_claimed_milestone_keys($pdo, $userId, $tokenId, $collection);
    $highestGenesisTraitLevel = fetch_highest_genesis_trait_level($pdo, $tokenId, $collection);
    $weaponsUnlocked = $highestGenesisTraitLevel >= 20;

    $rewards = [];

    foreach ($abilityRows as $row) {
        $abilityKey = strtoupper((string)($row['ability_key'] ?? ''));
        $currentLevel = (int)($row['current_level'] ?? 0);

        if (!is_valid_nft_ability_key('Weapons', $abilityKey)) {
            continue;
        }

        foreach (WEAPON_MILESTONE_REWARDS as $milestone) {
            $requiredLevel = (int)$milestone['required_level'];
            $milestoneKey = build_weapon_milestone_key($abilityKey, $requiredLevel);

            $rewards[] = [
                'milestone_key' => $milestoneKey,
                'source' => 'genesis_weapon_ability',
                'ability_category' => 'Weapons',
                'ability_key' => $abilityKey,
                'current_level' => $currentLevel,
                'required_level' => $requiredLevel,
                'reward_amount' => (int)$milestone['reward_amount'],
                'reward_currency' => 'DSPOINC',
                'reward_type' => 'dspoinc',
                'reward_reference_id' => null,
                'title' => $milestone['title'],
                'description' => $milestone['description'],
                'highest_genesis_trait_level' => $highestGenesisTraitLevel,
                'unlock_required_trait_level' => 20,
                'category_unlocked' => $weaponsUnlocked,
                'eligible' => $weaponsUnlocked && $currentLevel >= $requiredLevel,
                'claimed' => isset($claimedKeys[$milestoneKey]),
            ];
        }
    }

    $fitnessTraitRewards = [];

    foreach (FITNESS_TRAIT_MILESTONE_REWARDS as $milestone) {
        $requiredTraitLevel = (int)$milestone['required_trait_level'];
        $milestoneKey = build_fitness_trait_milestone_key($requiredTraitLevel);

        $fitnessTraitRewards[] = [
            'milestone_key' => $milestoneKey,
            'source' => 'genesis_fitness_trait',
            'ability_category' => 'Fitness',
            'ability_key' => 'TRAIT',
            'current_level' => $highestGenesisTraitLevel,
            'required_level' => $requiredTraitLevel,
            'required_trait_level' => $requiredTraitLevel,
            'reward_amount' => (int)$milestone['reward_amount'],
            'reward_currency' => (string)$milestone['reward_currency'],
            'reward_type' => (string)$milestone['reward_type'],
            'reward_reference_id' => $milestone['reward_reference_id'],
            'reward_preview' => (string)$milestone['preview_label'],
            'title' => (string)$milestone['title'],
            'description' => (string)$milestone['description'],
            'highest_genesis_trait_level' => $highestGenesisTraitLevel,
            'unlock_required_trait_level' => $requiredTraitLevel,
            'category_unlocked' => $highestGenesisTraitLevel >= $requiredTraitLevel,
            'eligible' => $highestGenesisTraitLevel >= $requiredTraitLevel,
            'claimed' => isset($claimedKeys[$milestoneKey]),
        ];
    }

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'token_id' => $tokenId,
        'collection' => $collection,
        'highest_genesis_trait_level' => $highestGenesisTraitLevel,
        'weapon_unlock_required_trait_level' => 20,
        'weapons_unlocked' => $weaponsUnlocked,
        'rewards' => $rewards,
        'fitness_trait_rewards' => $fitnessTraitRewards,
    ]);


} catch (Throwable $e) {
    error_log('❌ Ability milestone rewards read failed: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load ability milestone rewards',
    ]);
}