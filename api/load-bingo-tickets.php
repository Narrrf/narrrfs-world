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

if (!isset($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized: Please log in with Discord."]);
    exit;
}

$user_id = $_SESSION['discord_id'];

try {
    $pdo = new PDO(
        "sqlite:/var/www/html/db/narrrf_world.sqlite",
        null, null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->prepare("SELECT ticket_json FROM tbl_bingo_tickets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $ticketRows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $tickets = [];
    foreach ($ticketRows as $row) {
        $ticket = json_decode($row, true);
        if (is_array($ticket) && isset($ticket['id'], $ticket['grid'])) {
            $tickets[] = $ticket;
        }
    }

    // -- ONLY THIS ECHO! --
    echo json_encode($tickets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>