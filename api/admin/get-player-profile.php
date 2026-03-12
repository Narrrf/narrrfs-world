<?php
header('Content-Type: application/json');

try {
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';

    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);

    $query = trim($_GET['q'] ?? '');

    if ($query === '') {
        throw new Exception('Missing search query');
    }

    $safeQuery = SQLite3::escapeString($query);

    /**
     * Find the real user-id column in tbl_users.
     */
    $userIdColumn = null;
    $userNameColumn = null;

    $columnsResult = $db->query("PRAGMA table_info(tbl_users)");
    while ($column = $columnsResult->fetchArray(SQLITE3_ASSOC)) {
        $columnName = $column['name'] ?? '';

        if (in_array($columnName, ['user_id', 'discord_id', 'discord_user_id', 'id'], true) && !$userIdColumn) {
            $userIdColumn = $columnName;
        }

        if (in_array($columnName, ['username', 'discord_username', 'name'], true) && !$userNameColumn) {
            $userNameColumn = $columnName;
        }
    }

    $user = false;

    if ($userIdColumn && $userNameColumn) {
        $userSql = "
            SELECT {$userIdColumn} AS user_id, {$userNameColumn} AS username
            FROM tbl_users
            WHERE {$userIdColumn} = '{$safeQuery}'
               OR LOWER({$userNameColumn}) LIKE LOWER('%{$safeQuery}%')
            ORDER BY {$userNameColumn} ASC
            LIMIT 1
        ";

        $userResult = $db->query($userSql);
        $user = $userResult ? $userResult->fetchArray(SQLITE3_ASSOC) : false;
    }

    if (!$user) {
        $verificationSql = "
            SELECT user_id, username, wallet
            FROM tbl_holder_verifications
            WHERE user_id = '{$safeQuery}'
               OR LOWER(username) LIKE LOWER('%{$safeQuery}%')
               OR wallet = '{$safeQuery}'
            ORDER BY verified_at DESC
            LIMIT 1
        ";

        $verificationResult = $db->query($verificationSql);
        $verificationUser = $verificationResult ? $verificationResult->fetchArray(SQLITE3_ASSOC) : false;

        if (!$verificationUser) {
            echo json_encode([
                'success' => false,
                'error' => 'No player found'
            ]);
            exit;
        }

        $user = [
            'user_id' => $verificationUser['user_id'],
            'username' => $verificationUser['username'] ?: 'Unknown'
        ];
    }

    $userId = $user['user_id'];

    $balance = (int)$db->querySingle("
        SELECT COALESCE(SUM(score), 0)
        FROM tbl_user_scores
        WHERE user_id = '" . SQLite3::escapeString($userId) . "'
    ");

    $wallet = $db->querySingle("
        SELECT wallet
        FROM tbl_holder_verifications
        WHERE user_id = '" . SQLite3::escapeString($userId) . "'
        ORDER BY verified_at DESC
        LIMIT 1
    ");

    $genesisCount = (int)$db->querySingle("
        SELECT COUNT(*)
        FROM tbl_nft_ownership
        WHERE user_id = '" . SQLite3::escapeString($userId) . "'
          AND collection = 'genesis'
          AND is_verified = 1
    ");

    $vipCount = (int)$db->querySingle("
        SELECT COUNT(*)
        FROM tbl_nft_ownership
        WHERE user_id = '" . SQLite3::escapeString($userId) . "'
          AND collection = 'vip'
          AND is_verified = 1
    ");

    $roles = [];
    $rolesResult = $db->query("
        SELECT DISTINCT role_name
        FROM tbl_user_roles
        WHERE user_id = '" . SQLite3::escapeString($userId) . "'
        ORDER BY role_name ASC
    ");

    if ($rolesResult) {
        while ($row = $rolesResult->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['role_name'])) {
                $roles[] = $row['role_name'];
            }
        }
    }

    $traits = [];
    $traitsResult = $db->query("
        SELECT trait_type, trait_value
        FROM tbl_nft_traits
        WHERE user_id = '" . SQLite3::escapeString($userId) . "'
        ORDER BY trait_type ASC, trait_value ASC
        LIMIT 25
    ");

    if ($traitsResult) {
        while ($row = $traitsResult->fetchArray(SQLITE3_ASSOC)) {
            $traits[] = [
                'trait_type' => $row['trait_type'],
                'trait_value' => $row['trait_value']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'profile' => [
            'user_id' => $userId,
            'username' => $user['username'] ?: 'Unknown',
            'balance' => $balance,
            'wallet' => $wallet ?: '',
            'genesis_count' => $genesisCount,
            'vip_count' => $vipCount,
            'roles' => $roles,
            'traits' => $traits
        ]
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $error->getMessage()
    ]);
}