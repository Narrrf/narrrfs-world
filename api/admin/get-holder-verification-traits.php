<?php
// Return verified Genesis trait data for the Holder Verification -> Traits Verified admin tab.

header('Content-Type: application/json');

try {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);

    // Build one row per verified Genesis NFT, with VIP flag per user/wallet.
    $sql = "
        SELECT
            hv.user_id,
            hv.username,
            hv.wallet,
            hv.verified_at,

            o.ownership_id,
            o.token_id,
            o.nft_name,
            o.image_url,
            o.collection,
            o.is_verified,

            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM tbl_holder_verifications hv_vip
                    WHERE hv_vip.user_id = hv.user_id
                      AND hv_vip.wallet = hv.wallet
                      AND hv_vip.collection = 'vip'
                ) THEN 1
                ELSE 0
            END AS has_vip

        FROM tbl_holder_verifications hv
        INNER JOIN tbl_nft_ownership o
            ON o.user_id = hv.user_id
           AND o.wallet = hv.wallet
           AND o.collection = 'genesis'
           AND o.is_verified = 1

        WHERE hv.collection = 'genesis'
        ORDER BY hv.verified_at DESC, hv.username COLLATE NOCASE ASC, o.nft_name COLLATE NOCASE ASC
    ";

    $result = $db->query($sql);

    $users = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $userKey = ($row['user_id'] ?: 'unknown') . '|' . ($row['wallet'] ?: 'unknown');

        if (!isset($users[$userKey])) {
            $users[$userKey] = [
                'user_id' => $row['user_id'] ?? '',
                'username' => $row['username'] ?? 'Unknown user',
                'wallet' => $row['wallet'] ?? '',
                'verified_at' => $row['verified_at'] ?? '',
                'has_vip' => !empty($row['has_vip']),
                'has_genesis' => true,
                'genesis_nfts' => []
            ];
        }

        $ownershipId = (int)($row['ownership_id'] ?? 0);
        $tokenId = $row['token_id'] ?? '';
        $nftName = $row['nft_name'] ?? 'Unnamed NFT';
        $imageUrl = $row['image_url'] ?? '';

        $traitStmt = $db->prepare("
            SELECT trait_type, trait_value
            FROM tbl_nft_traits
            WHERE ownership_id = :ownership_id
            ORDER BY trait_type COLLATE NOCASE ASC, trait_value COLLATE NOCASE ASC
        ");
        $traitStmt->bindValue(':ownership_id', $ownershipId, SQLITE3_INTEGER);
        $traitResult = $traitStmt->execute();

        $traits = [];
        while ($traitRow = $traitResult->fetchArray(SQLITE3_ASSOC)) {
            $traits[] = [
                'trait_type' => $traitRow['trait_type'] ?? '',
                'trait_value' => $traitRow['trait_value'] ?? ''
            ];
        }

        $users[$userKey]['genesis_nfts'][] = [
            'token_id' => $tokenId,
            'nft_name' => $nftName,
            'image_url' => $imageUrl,
            'traits' => $traits
        ];
    }

    echo json_encode([
        'success' => true,
        'records' => array_values($users)
    ]);
} catch (Throwable $error) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}