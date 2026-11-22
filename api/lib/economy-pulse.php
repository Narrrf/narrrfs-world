<?php
declare(strict_types=1);

/**
 * Returns the two public economy aggregates using the legacy holder semantics.
 * No identity, balance list, or mutable database access leaves this helper.
 */
function narrrfs_public_economy_pulse(SQLite3 $db): array
{
    $total = $db->querySingle('SELECT COALESCE(SUM(score), 0) FROM tbl_user_scores');
    if ($total === false || $total === null) {
        throw new RuntimeException('Aggregate unavailable');
    }
    $rows = $db->query("SELECT COALESCE(SUM(s.score), 0) AS balance
        FROM tbl_user_scores s LEFT JOIN tbl_users u
        ON TRIM(CAST(u.discord_id AS TEXT)) = TRIM(CAST(s.user_id AS TEXT))
        GROUP BY s.user_id, u.discord_id, u.username HAVING balance > 0");
    if ($rows === false) {
        throw new RuntimeException('Aggregate unavailable');
    }
    $count = 0;
    $sum = 0;
    while (($row = $rows->fetchArray(SQLITE3_ASSOC)) !== false) {
        $count++;
        $sum += (int)$row['balance'];
    }
    return [
        'total_supply' => (int)$total,
        'average_balance' => $count > 0 ? (int)round($sum / $count) : 0,
    ];
}
