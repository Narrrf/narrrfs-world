<?php
/** Server-only ownership and League Point checks for Mouse LP Store V1. */

function mouse_lp_store_named_mouse(PDO $pdo, string $userId, string $tokenId): ?array
{
    $stmt = $pdo->prepare("SELECT o.token_id, o.collection, n.custom_name, o.image_url
        FROM tbl_nft_ownership o
        INNER JOIN tbl_nft_custom_names n
          ON n.token_id = o.token_id AND n.collection = o.collection AND n.user_id = o.user_id
        WHERE o.user_id = ? AND o.token_id = ?
          AND LOWER(COALESCE(o.collection, '')) = 'genesis'
          AND COALESCE(o.is_verified, 0) = 1
          AND TRIM(COALESCE(n.custom_name, '')) <> '' LIMIT 1");
    $stmt->execute([$userId, $tokenId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function mouse_lp_store_active_season(PDO $pdo): array
{
    $rows = $pdo->query("SELECT season_id, season_name FROM tbl_seasons WHERE is_active = 1 ORDER BY season_id ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) !== 1 || (int)$rows[0]['season_id'] < 1 || trim((string)$rows[0]['season_name']) === '') {
        throw new RuntimeException('League season is unavailable.');
    }
    return $rows[0];
}

function mouse_lp_store_totals(PDO $pdo, string $tokenId, int $seasonId): array
{
    $stmt = $pdo->prepare("SELECT
        COALESCE((SELECT SUM(points_awarded) FROM tbl_mousefight_league_point_awards WHERE token_id = ? AND collection = 'genesis'), 0) AS lifetime_lp,
        COALESCE((SELECT SUM(points_awarded) FROM tbl_mousefight_league_point_awards WHERE token_id = ? AND collection = 'genesis' AND season_id = ?), 0) AS season_lp,
        COALESCE((SELECT SUM(points_spent) FROM tbl_mousefight_league_point_spends WHERE token_id = ? AND collection = 'genesis'), 0) AS spent_lp");
    $stmt->execute([$tokenId, $tokenId, $seasonId, $tokenId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['lifetime_lp' => 0, 'season_lp' => 0, 'spent_lp' => 0];
    $row['lifetime_lp'] = (int)$row['lifetime_lp']; $row['season_lp'] = (int)$row['season_lp']; $row['spent_lp'] = (int)$row['spent_lp'];
    $row['spendable_lp'] = $row['lifetime_lp'] - $row['spent_lp'];
    return $row;
}
