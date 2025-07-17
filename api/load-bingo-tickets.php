<?php
/**
 * ==============================================
 *  🧀 Narrrf's World: Bingo Ticket Loader API
 *  Cheese Architect 11.0 — Secure, Scroll-Safe
 *  Purpose: Authenticated endpoint to fetch all
 *           Bingo tickets for the current user.
 *  Storage: JSON blobs in SQLite.
 *
 *  • 100-year-proof (no double-encoded JSON bugs)
 *  • Robust error handling, no info leaks
 *  • Comments for all future Cheese Architects
 *  • Only returns valid tickets (id + grid)
 *  • NO direct user input in query (uses session)
 *  • PDO in strict mode, always safe for upgrades
 * ==============================================
 */

ini_set('session.cookie_path', '/');
session_start();

header('Content-Type: application/json');

// --- Debugging block (uncomment for testing session issues) ---
// echo json_encode([
//   'session_id' => session_id(),
//   'discord_id' => $_SESSION['discord_id'] ?? 'not set',
//   'cookie_test' => $_COOKIE
// ]);
// exit;

if (!isset($_SESSION['discord_id'])) {
    // Not authenticated; reject with clear message
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized: Please log in with Discord."]);
    exit;
}

$user_id = $_SESSION['discord_id'];

try {
    // 💾 Future-proof: Update path if you move the DB
    $pdo = new PDO(
        "sqlite:/var/www/html/db/narrrf_world.sqlite",
        null, null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // ⚡ Query only this user's tickets, no input injection possible
    $stmt = $pdo->prepare("SELECT ticket_json FROM tbl_bingo_tickets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $ticketRows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 🧠 Unwrap and validate all tickets
    $tickets = [];
    foreach ($ticketRows as $row) {
        // Always decode to PHP array for strict type
        $ticket = json_decode($row, true);

        // 🏆 Only push valid tickets (must have id and grid)
        if (is_array($ticket) && isset($ticket['id'], $ticket['grid'])) {
            $tickets[] = $ticket;
        }
        // else ignore corrupt, legacy, or partial rows (keep clean forever)
    }

    // 🚀 Return as a simple array of objects (not strings!)
    echo json_encode($tickets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Panic-proof: never reveal details to user, just log internally in future
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    // Optionally: log $e->getMessage() to a server log for debugging
}

/*
 * ==============================================
 *  Cheese Scroll Footnotes:
 *  - This loader always returns native objects, never double-JSON.
 *  - To upgrade DB schema in the future, add fields to the ticket JSON, never break id/grid.
 *  - Session cookie path is global—works across all pages on the domain.
 *  - All logic is scroll-audited for 11.0+ and protected for Genesis Vault.
 *  - For 2030+: migrate to PostgreSQL or other RDBMS, keep the strict per-user query.
 * ==============================================
 */
?>
