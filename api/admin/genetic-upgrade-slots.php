<?php
/**
 * Admin-only Genetic Item upgrade-slot entitlement management.
 *
 * The entitlement is user-level. Changing it never mutates existing Genetic
 * Item timers, levels, ownership, listings, boosters, or economy state.
 */

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../user/genetic-upgrade-slot-helpers.php';
session_start();

function json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function request_data(): array {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        return $_GET ?? [];
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : [];
}

function require_genetic_slot_admin(): string {
    $isAdmin = $_SESSION['is_admin'] ?? false;
    $role = strtolower(trim((string)($_SESSION['admin_role'] ?? '')));
    $actor = trim((string)($_SESSION['admin_discord_id'] ?? $_SESSION['admin_username'] ?? ''));
    $allowedRoles = ['moderator', 'admin', 'owner', 'super_admin'];

    if (!($isAdmin === true || $isAdmin === 1 || $isAdmin === '1') || !in_array($role, $allowedRoles, true) || $actor === '') {
        json_response(['success' => false, 'error' => 'Admin access required'], 403);
    }

    return $actor;
}

function user_exists(PDO $pdo, string $userId): bool {
    $stmt = $pdo->prepare('SELECT discord_id FROM tbl_users WHERE discord_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    return (bool)$stmt->fetchColumn();
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'POST'], true)) {
        json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    $actor = require_genetic_slot_admin();
    $request = request_data();
    $userId = trim((string)($request['user_id'] ?? ''));

    if ($userId === '') {
        json_response(['success' => false, 'error' => 'Missing user_id'], 400);
    }

    $pdo = getDatabaseConnection();
    if (!user_exists($pdo, $userId)) {
        json_response(['success' => false, 'error' => 'User not found'], 404);
    }

    if ($method === 'GET') {
        json_response(['success' => true, 'data' => ['user_id' => $userId, 'upgrade_slots' => get_user_genetic_upgrade_slot_state($pdo, $userId)]]);
    }

    $rawSlots = $request['upgrade_slots'] ?? null;
    if (!is_int($rawSlots) || $rawSlots < GENETIC_UPGRADE_SLOT_MINIMUM || $rawSlots > GENETIC_UPGRADE_SLOT_MAXIMUM) {
        json_response(['success' => false, 'error' => 'upgrade_slots must be an integer from 1 to 3'], 400);
    }

    $pdo->beginTransaction();
    $oldLimit = get_user_genetic_upgrade_slot_limit($pdo, $userId);
    $upsert = $pdo->prepare(
        'INSERT INTO tbl_user_genetic_upgrade_slots (user_id, upgrade_slots, created_at, updated_at, updated_by)
         VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ?)
         ON CONFLICT(user_id) DO UPDATE SET upgrade_slots = excluded.upgrade_slots, updated_at = CURRENT_TIMESTAMP, updated_by = excluded.updated_by'
    );
    $upsert->execute([$userId, $rawSlots, $actor]);

    $audit = $pdo->prepare(
        "INSERT INTO tbl_user_genetic_upgrade_slot_audit (user_id, old_upgrade_slots, new_upgrade_slots, admin_user_id, action_type)
         VALUES (?, ?, ?, ?, 'genetic_upgrade_slots_changed')"
    );
    $audit->execute([$userId, $oldLimit, $rawSlots, $actor]);
    $pdo->commit();

    json_response(['success' => true, 'data' => ['user_id' => $userId, 'upgrade_slots' => get_user_genetic_upgrade_slot_state($pdo, $userId)]]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Genetic upgrade slots admin API error: ' . $error->getMessage());
    json_response(['success' => false, 'error' => 'Failed to update Genetic upgrade slots'], 500);
}
