<?php
/**
 * MouseFight Event Lucky Loser V1.
 * The bot supplies only a fight ID; durable rank, recipient, and reward facts
 * are loaded from SQLite inside one transaction.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/mouse-lp-store-delivery.php';

const MOUSEFIGHT_LUCKY_LOSER_EVENT_MODE = 'admin_bracket_event';
const MOUSEFIGHT_LUCKY_LOSER_FINISHED_STATUS = 'finished';
const MOUSEFIGHT_LUCKY_LOSER_REWARD_TYPE = 'event_lucky_loser_green_elixir';
const MOUSEFIGHT_LUCKY_LOSER_ITEM_ID = 33;
const MOUSEFIGHT_LUCKY_LOSER_QUANTITY = 1;
const MOUSEFIGHT_LUCKY_LOSER_ACTOR = 'mousefight_discord_bot';

function mousefight_lucky_loser_output(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

function mousefight_lucky_loser_auth_token(): string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach (['Authorization', 'authorization'] as $name) {
        if (isset($headers[$name])) return trim((string)$headers[$name]);
    }
    return '';
}

function mousefight_lucky_loser_require_bot_auth(): void {
    $provided = mousefight_lucky_loser_auth_token();
    $valid = array_values(array_filter([
        $_ENV['DISCORD_BOT_SECRET'] ?? getenv('DISCORD_BOT_SECRET'),
        $_ENV['DISCORD_SECRET'] ?? getenv('DISCORD_SECRET')
    ]));
    if ($provided === '' || !in_array($provided, $valid, true)) {
        mousefight_lucky_loser_output(['success' => false, 'error' => 'unauthorized'], 401);
    }
}

function mousefight_lucky_loser_receipt(array $row, bool $replayed): array {
    return [
        'success' => true,
        'replayed' => $replayed,
        'reward' => [
            'reward_id' => (int)$row['reward_id'],
            'fight_id' => $row['fight_id'],
            'reward_type' => $row['reward_type'],
            'recipient_user_id' => $row['recipient_user_id'],
            'item_id' => (int)$row['item_id'],
            'quantity' => (int)$row['quantity'],
            'inventory_id' => (int)$row['inventory_id'],
            'completed_at' => $row['completed_at']
        ]
    ];
}

function mousefight_lucky_loser_grant(PDO $pdo, string $fightId): array {
    $transactionStarted = false;
    $pdo->exec('BEGIN IMMEDIATE');
    $transactionStarted = true;
    try {
        $fight = $pdo->prepare('SELECT fight_id, mode, status FROM tbl_mousefights WHERE fight_id = ? LIMIT 1');
        $fight->execute([$fightId]);
        $fight = $fight->fetch(PDO::FETCH_ASSOC);
        if (!$fight) throw new DomainException('fight_not_found');
        if ($fight['mode'] !== MOUSEFIGHT_LUCKY_LOSER_EVENT_MODE) throw new DomainException('fight_not_event');
        if ($fight['status'] !== MOUSEFIGHT_LUCKY_LOSER_FINISHED_STATUS) throw new DomainException('fight_not_finished');

        $losers = $pdo->prepare('SELECT user_id FROM tbl_mousefight_participants WHERE fight_id = ? AND final_rank = 2');
        $losers->execute([$fightId]);
        $losers = $losers->fetchAll(PDO::FETCH_ASSOC);
        if (count($losers) !== 1 || trim((string)$losers[0]['user_id']) === '') throw new DomainException('final_rank_2_ambiguous');
        $recipient = trim((string)$losers[0]['user_id']);

        $item = $pdo->prepare('SELECT item_id FROM tbl_store_items WHERE item_id = ? AND COALESCE(is_active, 0) = 1 LIMIT 1');
        $item->execute([MOUSEFIGHT_LUCKY_LOSER_ITEM_ID]);
        if (!$item->fetch(PDO::FETCH_ASSOC)) throw new DomainException('green_elixir_unavailable');

        $existing = $pdo->prepare('SELECT * FROM tbl_mousefight_event_rewards WHERE fight_id = ? AND reward_type = ? LIMIT 1');
        $existing->execute([$fightId, MOUSEFIGHT_LUCKY_LOSER_REWARD_TYPE]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!hash_equals((string)$row['recipient_user_id'], $recipient)) throw new DomainException('durable_recipient_conflict');
            $pdo->exec('COMMIT');
            return mousefight_lucky_loser_receipt($row, true);
        }

        $inventoryId = mouse_lp_store_deliver_normal($pdo, $recipient, MOUSEFIGHT_LUCKY_LOSER_ITEM_ID);
        $insert = $pdo->prepare('INSERT INTO tbl_mousefight_event_rewards (fight_id, reward_type, recipient_user_id, item_id, quantity, inventory_id, granted_by_actor) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$fightId, MOUSEFIGHT_LUCKY_LOSER_REWARD_TYPE, $recipient, MOUSEFIGHT_LUCKY_LOSER_ITEM_ID, MOUSEFIGHT_LUCKY_LOSER_QUANTITY, $inventoryId, MOUSEFIGHT_LUCKY_LOSER_ACTOR]);
        $rewardId = (int)$pdo->lastInsertId();
        $receipt = $pdo->prepare('SELECT * FROM tbl_mousefight_event_rewards WHERE reward_id = ?');
        $receipt->execute([$rewardId]);
        $row = $receipt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('reward_receipt_missing');
        $pdo->exec('COMMIT');
        return mousefight_lucky_loser_receipt($row, false);
    } catch (Throwable $error) {
        if ($transactionStarted) {
            try { $pdo->exec('ROLLBACK'); } catch (Throwable $rollbackError) {}
        }
        throw $error;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') mousefight_lucky_loser_output(['success' => false, 'error' => 'method_not_allowed'], 405);
mousefight_lucky_loser_require_bot_auth();
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || array_keys($input) !== ['fight_id']) mousefight_lucky_loser_output(['success' => false, 'error' => 'invalid_request'], 422);
$fightId = trim((string)$input['fight_id']);
if ($fightId === '' || strlen($fightId) > 200) mousefight_lucky_loser_output(['success' => false, 'error' => 'invalid_fight_id'], 422);
try {
    mousefight_lucky_loser_output(mousefight_lucky_loser_grant(getDatabaseConnection(), $fightId));
} catch (DomainException $error) {
    mousefight_lucky_loser_output(['success' => false, 'error' => $error->getMessage()], 422);
} catch (Throwable $error) {
    error_log('MouseFight Lucky Loser reward failure: ' . $error->getMessage());
    mousefight_lucky_loser_output(['success' => false, 'error' => 'reward_transaction_failed'], 500);
}
