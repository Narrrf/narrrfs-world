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

        if (!is_array($traits)) {
            $traits = [];
        }

        $traitsJson = json_encode($traits, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $rarity = null;

        $collectionCounts[$collection]++;
        $verifiedCollections[$collection] = true;

        /**
         * Upsert NFT ownership row.
         */
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
        'has_message' => $message !== ''
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