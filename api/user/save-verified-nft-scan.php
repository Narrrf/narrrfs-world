<?php
/**
 * Save a full verified NFT scan into SQL for admin audit, holder verification,
 * Genesis trait search, and future ecosystem features.
 *
 * This endpoint is separate from verify-nft-holder.php:
 * - verify-nft-holder.php handles cryptographic verification + role logic
 * - save-verified-nft-scan.php persists the verified NFT snapshot to SQLite
 */

header('Content-Type: application/json');

/**
 * Genesis tier role IDs.
 *
 * Plain language for DEVS:
 * These are Discord recognition roles for verified Genesis ownership.
 * This file must only manage these six Genesis tier roles.
 * It must never touch Holder, VIP Holder, Moderator, Admin, Bot Master,
 * Champion, PokerOG, or any unrelated Discord role.
 */
const GENESIS_TIER_ROLE_LADDER = [
    [
        'key' => 'genesis_tier_1',
        'label' => 'Genesis Tier 1',
        'min_genesis' => 1,
        'role_id' => '1515508462333726902'
    ],
    [
        'key' => 'genesis_tier_2',
        'label' => 'Genesis Tier 2',
        'min_genesis' => 2,
        'role_id' => '1515508865976762400'
    ],
    [
        'key' => 'genesis_collector',
        'label' => 'Genesis Collector',
        'min_genesis' => 3,
        'role_id' => '1515509210228457632'
    ],
    [
        'key' => 'genesis_expert',
        'label' => 'Genesis Expert',
        'min_genesis' => 6,
        'role_id' => '1515508984054808757'
    ],
    [
        'key' => 'genesis_elite_holder',
        'label' => 'Genesis Elite Holder',
        'min_genesis' => 16,
        'role_id' => '1515509447500369990'
    ],
    [
        'key' => 'genesis_legend',
        'label' => 'Genesis Legend',
        'min_genesis' => 30,
        'role_id' => '1515509774760804352'
    ]
];

/**
 * Find the highest Genesis tier for a verified Genesis count.
 */
function resolveGenesisTierRole(int $genesisCount): ?array
{
    $matchedTier = null;

    foreach (GENESIS_TIER_ROLE_LADDER as $tier) {
        if ($genesisCount >= (int)$tier['min_genesis']) {
            $matchedTier = $tier;
        }
    }

    return $matchedTier;
}

/**
 * Call the existing Discord role API for add/remove actions.
 *
 * Plain language for DEVS:
 * This reuses api/discord/grant-role.php, which already supports add_role and
 * remove_role. The NFT scan must not fail if Discord is temporarily unavailable,
 * so callers catch exceptions and return a best-effort sync result.
 */
function callGenesisTierDiscordRoleApi(string $action, string $userId, string $roleId): array
{
    $payload = json_encode([
        'action' => $action,
        'user_id' => $userId,
        'role_id' => $roleId
    ]);

    $ch = curl_init('https://narrrfs.world/api/discord/grant-role.php');

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer admin_quest_system'
    ]);

    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($rawResponse === false) {
        throw new RuntimeException("Discord role API curl failed: {$curlError}");
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        throw new RuntimeException("Discord role API returned non-JSON response. HTTP {$httpCode}: " . substr($rawResponse, 0, 200));
    }

    if (empty($decoded['success'])) {
        $error = $decoded['error'] ?? 'Unknown Discord role API error';
        throw new RuntimeException("Discord role API failed for {$action} {$roleId}. HTTP {$httpCode}: {$error}");
    }

    return [
        'success' => true,
        'http_code' => $httpCode,
        'response' => $decoded
    ];
}

/**
 * Sync one user's Genesis tier roles after verified NFT ownership was committed.
 *
 * Plain language for DEVS:
 * This is a best-effort post-commit role sync.
 * The verified NFT scan has already been saved before this runs.
 * If Discord role sync fails, the NFT verification response still succeeds.
 *
 * Rules:
 * - Genesis count comes from the saved scan result.
 * - VIP NFTs do not count.
 * - The user receives only the highest matching Genesis tier role.
 * - The other five Genesis tier roles are removed.
 */
function syncGenesisTierRolesAfterVerifiedScan(string $userId, int $genesisCount): array
{
    $result = [
        'attempted' => false,
        'success' => false,
        'user_id' => $userId,
        'genesis_count' => $genesisCount,
        'target_tier' => null,
        'target_role_id' => null,
        'added_role' => null,
        'removed_roles' => [],
        'errors' => []
    ];

    if ($userId === '') {
        $result['errors'][] = 'Missing user id.';
        return $result;
    }

    $targetTier = resolveGenesisTierRole($genesisCount);

    if ($targetTier === null) {
        $result['attempted'] = false;
        $result['success'] = true;
        $result['target_tier'] = 'none';
        return $result;
    }

    $result['attempted'] = true;
    $result['target_tier'] = $targetTier['label'];
    $result['target_role_id'] = $targetTier['role_id'];

    try {
        callGenesisTierDiscordRoleApi('add_role', $userId, $targetTier['role_id']);
        $result['added_role'] = $targetTier['role_id'];
    } catch (Throwable $error) {
        $result['errors'][] = $error->getMessage();
    }

    foreach (GENESIS_TIER_ROLE_LADDER as $tier) {
        if ($tier['role_id'] === $targetTier['role_id']) {
            continue;
        }

        try {
            callGenesisTierDiscordRoleApi('remove_role', $userId, $tier['role_id']);
            $result['removed_roles'][] = $tier['role_id'];
        } catch (Throwable $error) {
            $result['errors'][] = $error->getMessage();
        }
    }

    $result['success'] = empty($result['errors']);

    return $result;
}

/**
 * Check if a trait array has at least one usable trait.
 *
 * Plain language for DEVS:
 * A Genesis NFT needs real trait_type/value pairs before the Lab can show
 * scanned traits, Lab power, and trait training options.
 */
function hasUsableNftTraits(array $traits): bool
{
    foreach ($traits as $trait) {
        if (!is_array($trait)) {
            continue;
        }

        $traitType = trim((string)($trait['trait_type'] ?? $trait['type'] ?? ''));
        $traitValue = trim((string)($trait['value'] ?? ''));

        if ($traitType !== '' && $traitValue !== '') {
            return true;
        }
    }

    return false;
}

/**
 * Extract a canonical metadata URI from the incoming NFT metadata.
 *
 * Plain language for DEVS:
 * Wallet scans can return shallow NFT data, but still include metadataUri.
 * That URI is the canonical JSON source for image and attributes.
 */
function extractNftMetadataUri(array $metadata): string
{
    $candidateKeys = [
        'metadataUri',
        'metadata_uri',
        'json_uri',
        'uri',
        'external_url'
    ];

    foreach ($candidateKeys as $key) {
        $value = trim((string)($metadata[$key] ?? ''));

        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

/**
 * Validate that a metadata URI is safe enough for backend fetch.
 *
 * Plain language for DEVS:
 * This prevents the verifier from fetching random local/internal URLs.
 * For now we only allow HTTPS metadata URLs, which covers Gensuki/4everland.
 */
function isSafeNftMetadataUri(string $metadataUri): bool
{
    if ($metadataUri === '') {
        return false;
    }

    if (!preg_match('/^https:\/\/[^\s]+$/i', $metadataUri)) {
        return false;
    }

    return true;
}

/**
 * Fetch and decode canonical NFT metadata JSON.
 *
 * Plain language for DEVS:
 * This small curl wrapper lets the save endpoint repair shallow Genesis scan
 * data before it writes ownership, traits, and Lab metadata to SQLite.
 */
function fetchCanonicalNftMetadata(string $metadataUri): ?array
{
    if (!isSafeNftMetadataUri($metadataUri)) {
        return null;
    }

    $ch = curl_init($metadataUri);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: Narrrfs-World-Verified-NFT-Metadata-Fallback/1.0'
    ]);

    $responseBody = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($responseBody === false || $curlError || $httpCode < 200 || $httpCode >= 300) {
        error_log("⚠️ [save-verified-nft-scan] Metadata URI fetch failed. HTTP {$httpCode}. Error: {$curlError}. URI: {$metadataUri}");
        return null;
    }

    $decoded = json_decode((string)$responseBody, true);

    if (!is_array($decoded)) {
        error_log("⚠️ [save-verified-nft-scan] Metadata URI returned invalid JSON: {$metadataUri}");
        return null;
    }

    return $decoded;
}

/**
 * Normalize canonical metadata attributes into the Lab trait shape.
 *
 * Plain language for DEVS:
 * The Lab expects traits as trait_type/value pairs. This function accepts
 * standard NFT attributes and removes empty/broken entries.
 */
function normalizeCanonicalNftTraits(array $attributes): array
{
    $normalizedTraits = [];

    foreach ($attributes as $attribute) {
        if (!is_array($attribute)) {
            continue;
        }

        $traitType = trim((string)($attribute['trait_type'] ?? $attribute['type'] ?? ''));
        $traitValue = trim((string)($attribute['value'] ?? ''));

        if ($traitType === '' || $traitValue === '') {
            continue;
        }

        $normalizedTraits[] = [
            'trait_type' => $traitType,
            'value' => $traitValue
        ];
    }

    return $normalizedTraits;
}

/**
 * Enrich shallow Genesis NFT metadata from metadataUri before saving.
 *
 * Plain language for DEVS:
 * Newly minted Genesis NFTs can arrive from Helius/wallet scan with:
 * image = ""
 * attributes = []
 * metadataUri = valid canonical Gensuki JSON
 *
 * This function fetches metadataUri only for Genesis and only when image or
 * traits are missing. It never invents traits and it does not change ownership.
 */
function enrichGenesisNftMetadataFromUri(
    string $collection,
    string $imageUrl,
    array $metadata,
    array $traits
): array {
    if ($collection !== 'genesis') {
        return [
            'image_url' => $imageUrl,
            'metadata' => $metadata,
            'traits' => $traits
        ];
    }

    $hasImage = trim($imageUrl) !== '';
    $hasTraits = hasUsableNftTraits($traits);

    if ($hasImage && $hasTraits) {
        return [
            'image_url' => $imageUrl,
            'metadata' => $metadata,
            'traits' => $traits
        ];
    }

    $metadataUri = extractNftMetadataUri($metadata);

    if (!isSafeNftMetadataUri($metadataUri)) {
        return [
            'image_url' => $imageUrl,
            'metadata' => $metadata,
            'traits' => $traits
        ];
    }

    $canonicalMetadata = fetchCanonicalNftMetadata($metadataUri);

    if (!is_array($canonicalMetadata)) {
        return [
            'image_url' => $imageUrl,
            'metadata' => $metadata,
            'traits' => $traits
        ];
    }

    $canonicalImageUrl = trim((string)($canonicalMetadata['image'] ?? ''));
    $canonicalAttributes = is_array($canonicalMetadata['attributes'] ?? null)
        ? $canonicalMetadata['attributes']
        : [];

    $canonicalTraits = normalizeCanonicalNftTraits($canonicalAttributes);

    if ($imageUrl === '' && $canonicalImageUrl !== '') {
        $imageUrl = $canonicalImageUrl;
    }

    if (!$hasTraits && !empty($canonicalTraits)) {
        $traits = $canonicalTraits;
    }

    $metadata = array_merge($metadata, [
        'name' => $canonicalMetadata['name'] ?? ($metadata['name'] ?? ''),
        'description' => $canonicalMetadata['description'] ?? ($metadata['description'] ?? ''),
        'image' => $imageUrl,
        'attributes' => $traits,
        'properties' => $canonicalMetadata['properties'] ?? ($metadata['properties'] ?? null),
        'metadataUri' => $metadataUri
    ]);

    return [
        'image_url' => $imageUrl,
        'metadata' => $metadata,
        'traits' => $traits
    ];
}

try {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !is_array($input)) {
        throw new Exception('Invalid JSON payload');
    }

    $userId = trim($input['user_id'] ?? '');
    $username = trim($input['username'] ?? '');
    $wallet = trim($input['wallet'] ?? '');
    $signature = trim($input['signature'] ?? '');
    $message = trim($input['message'] ?? '');
    $nfts = $input['nfts'] ?? [];

    if ($userId === '' || $username === '' || $wallet === '') {
        throw new Exception('Missing required user or wallet fields');
    }

    if (!is_array($nfts)) {
        throw new Exception('Invalid NFT payload');
    }

    // Basic Solana wallet format validation
    $walletPattern = '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/';
    if (!preg_match($walletPattern, $wallet)) {
        throw new Exception('Invalid Solana wallet format');
    }

    $db->exec('BEGIN TRANSACTION');

    /**
     * Reset previous verified state for this wallet so the new scan becomes
     * the current truth. This prevents stale verified ownership/traits from
     * remaining after repeated scans.
     */
    $resetOwnership = $db->prepare("
        UPDATE tbl_nft_ownership
        SET is_verified = 0
        WHERE wallet = :wallet
    ");
    $resetOwnership->bindValue(':wallet', $wallet, SQLITE3_TEXT);
    $resetOwnership->execute();

    $resetTraits = $db->prepare("
        DELETE FROM tbl_nft_traits
        WHERE wallet = :wallet
    ");
    $resetTraits->bindValue(':wallet', $wallet, SQLITE3_TEXT);
    $resetTraits->execute();

    /**
     * Remove old holder verification summary rows for this user+wallet.
     * Fresh rows will be inserted again below from the current scan.
     */
    $deleteExistingVerifications = $db->prepare("
        DELETE FROM tbl_holder_verifications
        WHERE user_id = :user_id AND wallet = :wallet
    ");
    $deleteExistingVerifications->bindValue(':user_id', $userId, SQLITE3_TEXT);
    $deleteExistingVerifications->bindValue(':wallet', $wallet, SQLITE3_TEXT);
    $deleteExistingVerifications->execute();

    $collectionCounts = [
        'genesis' => 0,
        'vip' => 0
    ];

    $verifiedCollections = [];

    /**
     * Prepared statements for NFT ownership upsert flow.
     */
    $selectOwnership = $db->prepare("
        SELECT ownership_id
        FROM tbl_nft_ownership
        WHERE wallet = :wallet
          AND token_id = :token_id
          AND collection = :collection
        LIMIT 1
    ");

    /**
     * Reset verified ownership for the same NFT token before assigning the
     * current verified owner. This is the critical ownership-transfer guard:
     * upgrades belong to the NFT, but only the current verified holder may
     * access them through the read/action layer.
     */
    $resetPreviousTokenOwnership = $db->prepare("
        UPDATE tbl_nft_ownership
        SET is_verified = 0
        WHERE token_id = :token_id
          AND collection = :collection
          AND wallet != :wallet
    ");

    /**
     * Remove trait rows that still point at an older ownership record for the
     * same NFT token. Fresh trait rows for the current verified holder will be
     * inserted again below.
     */
    $deletePreviousTokenTraits = $db->prepare("
        DELETE FROM tbl_nft_traits
        WHERE token_id = :token_id
          AND collection = :collection
          AND wallet != :wallet
    ");

    $insertOwnership = $db->prepare("
        INSERT INTO tbl_nft_ownership (
            wallet,
            token_id,
            collection,
            traits,
            rarity,
            mint_date,
            acquired_at,
            user_id,
            username,
            nft_name,
            image_url,
            metadata_json,
            is_verified,
            verified_at,
            last_seen_at
        ) VALUES (
            :wallet,
            :token_id,
            :collection,
            :traits,
            :rarity,
            NULL,
            CURRENT_TIMESTAMP,
            :user_id,
            :username,
            :nft_name,
            :image_url,
            :metadata_json,
            1,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        )
    ");

    $updateOwnership = $db->prepare("
        UPDATE tbl_nft_ownership
        SET
            user_id = :user_id,
            username = :username,
            nft_name = :nft_name,
            image_url = :image_url,
            metadata_json = :metadata_json,
            traits = :traits,
            rarity = :rarity,
            is_verified = 1,
            verified_at = CURRENT_TIMESTAMP,
            last_seen_at = CURRENT_TIMESTAMP
        WHERE ownership_id = :ownership_id
    ");

    $insertTrait = $db->prepare("
        INSERT OR IGNORE INTO tbl_nft_traits (
            ownership_id,
            user_id,
            username,
            wallet,
            token_id,
            collection,
            nft_name,
            trait_type,
            trait_value,
            verified_at,
            created_at,
            updated_at
        ) VALUES (
            :ownership_id,
            :user_id,
            :username,
            :wallet,
            :token_id,
            :collection,
            :nft_name,
            :trait_type,
            :trait_value,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        )
    ");

    /**
     * Prepared statement for fresh holder verification summary rows.
     */
    $insertVerification = $db->prepare("
        INSERT INTO tbl_holder_verifications (
            user_id,
            username,
            wallet,
            collection,
            nft_count,
            role_granted,
            verified_at,
            created_at
        ) VALUES (
            :user_id,
            :username,
            :wallet,
            :collection,
            :nft_count,
            1,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        )
    ");

    /**
     * Process each verified NFT from the frontend scan.
     */
    foreach ($nfts as $nft) {
        if (!is_array($nft)) {
            continue;
        }

        $tokenId = trim(
            $nft['token_id']
            ?? $nft['tokenId']
            ?? $nft['mint']
            ?? $nft['mintAddress']
            ?? $nft['address']
            ?? ''
        );

$collectionRaw = trim($nft['collection'] ?? '');
$collection = strtolower($collectionRaw);

$nftName = trim($nft['nft_name'] ?? $nft['name'] ?? 'Unnamed NFT');
$imageUrl = trim($nft['image_url'] ?? $nft['image'] ?? '');
$metadata = $nft['metadata_json'] ?? $nft['metadata'] ?? $nft;
$traits = $nft['traits'] ?? $nft['attributes'] ?? [];

if ($tokenId === '' || $collection === '') {
    continue;
}

if (!in_array($collection, ['genesis', 'vip'], true)) {
    continue;
}

if (is_string($metadata)) {
    $decodedMetadata = json_decode($metadata, true);
    $metadata = is_array($decodedMetadata) ? $decodedMetadata : [];
}

if (!is_array($metadata)) {
    $metadata = [];
}

if (!is_array($traits)) {
    $traits = [];
}

/**
 * Genesis metadata safety fallback.
 *
 * Plain language for DEVS:
 * Newly minted Genesis NFTs can arrive from the wallet scan with empty image
 * and empty attributes while still containing a valid metadataUri. Before we
 * save ownership, we fetch that canonical metadata and use it to prevent Lab
 * cards from being saved with placeholder art or zero scanned traits.
 */
$metadataFallback = enrichGenesisNftMetadataFromUri($collection, $imageUrl, $metadata, $traits);

$imageUrl = $metadataFallback['image_url'];
$metadata = $metadataFallback['metadata'];
$traits = $metadataFallback['traits'];

$traitsJson = json_encode($traits, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$rarity = null;

$collectionCounts[$collection]++;
$verifiedCollections[$collection] = true;

        /**
         * Upsert NFT ownership row.
         *
         * IMPORTANT: token-level ownership must be normalized before the upsert
         * so a previous verified holder instantly loses access after transfer.
         */
        $resetPreviousTokenOwnership->reset();
        $resetPreviousTokenOwnership->clear();
        $resetPreviousTokenOwnership->bindValue(':token_id', $tokenId, SQLITE3_TEXT);
        $resetPreviousTokenOwnership->bindValue(':collection', $collection, SQLITE3_TEXT);
        $resetPreviousTokenOwnership->bindValue(':wallet', $wallet, SQLITE3_TEXT);
        $resetPreviousTokenOwnership->execute();

        $deletePreviousTokenTraits->reset();
        $deletePreviousTokenTraits->clear();
        $deletePreviousTokenTraits->bindValue(':token_id', $tokenId, SQLITE3_TEXT);
        $deletePreviousTokenTraits->bindValue(':collection', $collection, SQLITE3_TEXT);
        $deletePreviousTokenTraits->bindValue(':wallet', $wallet, SQLITE3_TEXT);
        $deletePreviousTokenTraits->execute();

        $selectOwnership->reset();
        $selectOwnership->clear();
        $selectOwnership->bindValue(':wallet', $wallet, SQLITE3_TEXT);
        $selectOwnership->bindValue(':token_id', $tokenId, SQLITE3_TEXT);
        $selectOwnership->bindValue(':collection', $collection, SQLITE3_TEXT);
        $existingOwnershipResult = $selectOwnership->execute();

        $existingOwnership = $existingOwnershipResult
            ? $existingOwnershipResult->fetchArray(SQLITE3_ASSOC)
            : false;

        $ownershipId = null;

        if ($existingOwnership && isset($existingOwnership['ownership_id'])) {
            $ownershipId = (int)$existingOwnership['ownership_id'];

            $updateOwnership->reset();
            $updateOwnership->clear();
            $updateOwnership->bindValue(':user_id', $userId, SQLITE3_TEXT);
            $updateOwnership->bindValue(':username', $username, SQLITE3_TEXT);
            $updateOwnership->bindValue(':nft_name', $nftName, SQLITE3_TEXT);
            $updateOwnership->bindValue(':image_url', $imageUrl, SQLITE3_TEXT);
            $updateOwnership->bindValue(':metadata_json', $metadataJson, SQLITE3_TEXT);
            $updateOwnership->bindValue(':traits', $traitsJson, SQLITE3_TEXT);
            $updateOwnership->bindValue(':rarity', $rarity, SQLITE3_TEXT);
            $updateOwnership->bindValue(':ownership_id', $ownershipId, SQLITE3_INTEGER);
            $updateOwnership->execute();
        } else {
            $insertOwnership->reset();
            $insertOwnership->clear();
            $insertOwnership->bindValue(':wallet', $wallet, SQLITE3_TEXT);
            $insertOwnership->bindValue(':token_id', $tokenId, SQLITE3_TEXT);
            $insertOwnership->bindValue(':collection', $collection, SQLITE3_TEXT);
            $insertOwnership->bindValue(':traits', $traitsJson, SQLITE3_TEXT);
            $insertOwnership->bindValue(':rarity', $rarity, SQLITE3_TEXT);
            $insertOwnership->bindValue(':user_id', $userId, SQLITE3_TEXT);
            $insertOwnership->bindValue(':username', $username, SQLITE3_TEXT);
            $insertOwnership->bindValue(':nft_name', $nftName, SQLITE3_TEXT);
            $insertOwnership->bindValue(':image_url', $imageUrl, SQLITE3_TEXT);
            $insertOwnership->bindValue(':metadata_json', $metadataJson, SQLITE3_TEXT);
            $insertOwnership->execute();

            $ownershipId = (int)$db->lastInsertRowID();
        }

        /**
         * Persist Genesis traits as normalized rows for future admin search/filter.
         * VIP is stored as ownership only, per your plan.
         */
        if ($collection === 'genesis') {
            foreach ($traits as $trait) {
                if (!is_array($trait)) {
                    continue;
                }

                $traitType = trim($trait['trait_type'] ?? $trait['type'] ?? '');
                $traitValueRaw = $trait['value'] ?? '';
                $traitValue = trim((string)$traitValueRaw);

                if ($traitType === '' || $traitValue === '') {
                    continue;
                }

                $insertTrait->reset();
                $insertTrait->clear();
                $insertTrait->bindValue(':ownership_id', $ownershipId, SQLITE3_INTEGER);
                $insertTrait->bindValue(':user_id', $userId, SQLITE3_TEXT);
                $insertTrait->bindValue(':username', $username, SQLITE3_TEXT);
                $insertTrait->bindValue(':wallet', $wallet, SQLITE3_TEXT);
                $insertTrait->bindValue(':token_id', $tokenId, SQLITE3_TEXT);
                $insertTrait->bindValue(':collection', $collection, SQLITE3_TEXT);
                $insertTrait->bindValue(':nft_name', $nftName, SQLITE3_TEXT);
                $insertTrait->bindValue(':trait_type', $traitType, SQLITE3_TEXT);
                $insertTrait->bindValue(':trait_value', $traitValue, SQLITE3_TEXT);
                $insertTrait->execute();
            }
        }
    }

    /**
     * Rebuild holder verification summary rows from this fresh scan.
     */
    foreach (['genesis', 'vip'] as $collectionName) {
        $count = (int)($collectionCounts[$collectionName] ?? 0);

        if ($count <= 0) {
            continue;
        }

        $insertVerification->reset();
        $insertVerification->clear();
        $insertVerification->bindValue(':user_id', $userId, SQLITE3_TEXT);
        $insertVerification->bindValue(':username', $username, SQLITE3_TEXT);
        $insertVerification->bindValue(':wallet', $wallet, SQLITE3_TEXT);
        $insertVerification->bindValue(':collection', $collectionName, SQLITE3_TEXT);
        $insertVerification->bindValue(':nft_count', $count, SQLITE3_INTEGER);
        $insertVerification->execute();
    }

        $db->exec('COMMIT');

    /**
     * Best-effort Genesis tier role sync.
     *
     * Plain language for DEVS:
     * The SQL ownership scan is already committed above.
     * This role sync must never roll back or break holder verification.
     * Profile and Stake Lab both call this endpoint, so this single hook keeps
     * Genesis Discord tier roles fresh from both verification lanes.
     */
    $genesisTierRoleSync = syncGenesisTierRolesAfterVerifiedScan(
        $userId,
        (int)($collectionCounts['genesis'] ?? 0)
    );

    echo json_encode([
        'success' => true,
        'message' => 'Verified NFT scan saved successfully',
        'user_id' => $userId,
        'username' => $username,
        'wallet' => $wallet,
        'collections' => array_keys($verifiedCollections),
        'counts' => $collectionCounts,
        'saved_nft_count' => array_sum($collectionCounts),
        'has_signature' => $signature !== '',
        'has_message' => $message !== '',
        'genesis_tier_role_sync' => $genesisTierRoleSync
    ]);
} catch (Throwable $error) {
    if (isset($db) && $db instanceof SQLite3) {
        @$db->exec('ROLLBACK');
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}