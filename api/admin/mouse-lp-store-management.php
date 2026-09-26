<?php
/**
 * Mouse LP Store offer administration.
 *
 * This endpoint manages only independent Mouse LP Store offer allocations.
 * It never changes the original Store catalog, Genetic catalog, player
 * inventory, League Points, DSPOINC, ownership, or purchase history.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin-auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function mouse_lp_store_admin_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function mouse_lp_store_admin_is_localhost(): bool
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    return strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
}

/**
 * The actor is derived only from the server session. Browser fields never
 * decide who administered an offer. The local label records the existing
 * localhost-only admin-auth development mode without impersonating a user.
 */
function mouse_lp_store_admin_actor(): string
{
    $actor = trim((string)(
        $_SESSION['admin_discord_id']
        ?? $_SESSION['admin_username']
        ?? $_SESSION['discord_id']
        ?? ''
    ));

    if ($actor !== '') {
        return substr($actor, 0, 190);
    }

    if (mouse_lp_store_admin_is_localhost()) {
        return 'local_admin';
    }

    throw new RuntimeException('Authenticated admin actor is unavailable.');
}

function mouse_lp_store_admin_request(): array
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        return is_array($_GET) ? $_GET : [];
    }

    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (is_array($decoded)) {
        return $decoded;
    }

    return is_array($_POST) ? $_POST : [];
}

/**
 * admin-auth.php owns the established admin DB path helper. It cannot be
 * included beside database.php because both legacy files declare that helper.
 * This local connection keeps this endpoint on the same verified DB path.
 */
function mouse_lp_store_admin_database(): PDO
{
    $pdo = new PDO('sqlite:' . getDatabasePath());
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function mouse_lp_store_admin_required_integer($value, string $field, int $minimum): int
{
    if (is_int($value)) {
        $integer = $value;
    } elseif (is_string($value) && preg_match('/^\d+$/', $value)) {
        $integer = (int)$value;
    } else {
        throw new InvalidArgumentException($field . ' must be an integer.');
    }

    if ($integer < $minimum) {
        throw new InvalidArgumentException($field . ' is out of range.');
    }

    return $integer;
}

function mouse_lp_store_admin_nullable_positive_integer($value, string $field): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    return mouse_lp_store_admin_required_integer($value, $field, 1);
}

function mouse_lp_store_admin_binary($value, string $field): int
{
    if ($value === true || $value === 1 || $value === '1') {
        return 1;
    }
    if ($value === false || $value === 0 || $value === '0') {
        return 0;
    }

    throw new InvalidArgumentException($field . ' must be 0 or 1.');
}

function mouse_lp_store_admin_source(PDO $pdo, string $sourceType, int $sourceItemId): ?array
{
    if ($sourceType === 'normal_store') {
        $statement = $pdo->prepare(
            'SELECT item_id AS source_item_id, item_name AS item_title, image_url,
                    price AS reference_dspoinc_price,
                    CASE WHEN COALESCE(is_active, active, 1) = 1 THEN 1 ELSE 0 END AS is_available
             FROM tbl_store_items WHERE item_id = ? LIMIT 1'
        );
    } elseif ($sourceType === 'genetic_item') {
        $statement = $pdo->prepare(
            'SELECT catalog_id AS source_item_id, display_title AS item_title, preview_path AS image_url,
                    base_price_dspoinc AS reference_dspoinc_price,
                    CASE WHEN is_active = 1 AND is_visible = 1 THEN 1 ELSE 0 END AS is_available,
                    trait_type, trait_value, is_active, is_visible
             FROM tbl_genetic_trait_catalog WHERE catalog_id = ? LIMIT 1'
        );
    } else {
        throw new InvalidArgumentException('Unsupported source type.');
    }

    $statement->execute([$sourceItemId]);
    $source = $statement->fetch(PDO::FETCH_ASSOC);
    return $source ?: null;
}

function mouse_lp_store_admin_sources(PDO $pdo, string $sourceType): array
{
    if ($sourceType === 'normal_store') {
        $rows = $pdo->query(
            'SELECT item_id AS source_item_id, item_name AS item_title, image_url,
                    price AS reference_dspoinc_price,
                    CASE WHEN COALESCE(is_active, active, 1) = 1 THEN 1 ELSE 0 END AS is_available
             FROM tbl_store_items ORDER BY is_available DESC, LOWER(item_name), item_id'
        )->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($sourceType === 'genetic_item') {
        $rows = $pdo->query(
            'SELECT catalog_id AS source_item_id, display_title AS item_title, preview_path AS image_url,
                    base_price_dspoinc AS reference_dspoinc_price, trait_type, trait_value,
                    is_active, is_visible,
                    CASE WHEN is_active = 1 AND is_visible = 1 THEN 1 ELSE 0 END AS is_available
             FROM tbl_genetic_trait_catalog
             ORDER BY is_available DESC, LOWER(display_title), catalog_id'
        )->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new InvalidArgumentException('Unsupported source type.');
    }

    return is_array($rows) ? $rows : [];
}

function mouse_lp_store_admin_offers(PDO $pdo): array
{
    $statement = $pdo->query(
        "SELECT o.offer_id, o.source_type, o.source_item_id, o.lp_price, o.dspoinc_price,
                o.stock, o.is_active, o.created_by, o.updated_by, o.created_at, o.updated_at,
                s.item_name AS normal_title, s.image_url AS normal_image,
                CASE WHEN COALESCE(s.is_active, s.active, 0) = 1 THEN 1 ELSE 0 END AS normal_available,
                g.display_title AS genetic_title, g.preview_path AS genetic_image,
                g.trait_type, g.trait_value,
                CASE WHEN g.is_active = 1 AND g.is_visible = 1 THEN 1 ELSE 0 END AS genetic_available
         FROM tbl_mouse_lp_store_offers o
         LEFT JOIN tbl_store_items s ON o.source_type = 'normal_store' AND s.item_id = o.source_item_id
         LEFT JOIN tbl_genetic_trait_catalog g ON o.source_type = 'genetic_item' AND g.catalog_id = o.source_item_id
         ORDER BY o.is_active DESC, o.updated_at DESC, o.offer_id DESC"
    );

    $offers = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $normal = $row['source_type'] === 'normal_store';
        $sourceExists = $normal ? $row['normal_title'] !== null : $row['genetic_title'] !== null;
        $offers[] = [
            'offer_id' => (int)$row['offer_id'],
            'source_type' => $row['source_type'],
            'source_item_id' => (int)$row['source_item_id'],
            'item_title' => $normal ? $row['normal_title'] : $row['genetic_title'],
            'image_url' => $normal ? $row['normal_image'] : $row['genetic_image'],
            'trait_type' => $normal ? null : $row['trait_type'],
            'trait_value' => $normal ? null : $row['trait_value'],
            'source_exists' => $sourceExists,
            'source_available' => $sourceExists && (int)($normal ? $row['normal_available'] : $row['genetic_available']) === 1,
            'lp_price' => $row['lp_price'] === null ? null : (int)$row['lp_price'],
            'dspoinc_price' => $row['dspoinc_price'] === null ? null : (int)$row['dspoinc_price'],
            'stock' => (int)$row['stock'],
            'is_active' => (int)$row['is_active'],
            'updated_at' => $row['updated_at'],
        ];
    }

    return $offers;
}

try {
    checkAdminAuthentication();
    $request = mouse_lp_store_admin_request();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = trim((string)($request['action'] ?? ''));
    $pdo = mouse_lp_store_admin_database();

    if ($method === 'GET' && $action === 'list_sources') {
        $sourceType = trim((string)($request['source_type'] ?? ''));
        mouse_lp_store_admin_json(['success' => true, 'source_type' => $sourceType, 'sources' => mouse_lp_store_admin_sources($pdo, $sourceType)]);
    }

    if ($method === 'GET' && $action === 'list_offers') {
        mouse_lp_store_admin_json(['success' => true, 'offers' => mouse_lp_store_admin_offers($pdo)]);
    }

    if ($method !== 'POST' || !in_array($action, ['save_offer', 'set_offer_active'], true)) {
        mouse_lp_store_admin_json(['success' => false, 'error' => 'Method or action not allowed.'], 405);
    }

    $actor = mouse_lp_store_admin_actor();

    if ($action === 'set_offer_active') {
        $offerId = mouse_lp_store_admin_required_integer($request['offer_id'] ?? null, 'offer_id', 1);
        $isActive = mouse_lp_store_admin_binary($request['is_active'] ?? null, 'is_active');
        $statement = $pdo->prepare('UPDATE tbl_mouse_lp_store_offers SET is_active = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE offer_id = ?');
        $statement->execute([$isActive, $actor, $offerId]);
        if ($statement->rowCount() !== 1) {
            mouse_lp_store_admin_json(['success' => false, 'error' => 'Offer not found.'], 404);
        }
        mouse_lp_store_admin_json(['success' => true, 'offer_id' => $offerId, 'is_active' => $isActive, 'offers' => mouse_lp_store_admin_offers($pdo)]);
    }

    $sourceType = trim((string)($request['source_type'] ?? ''));
    if (!in_array($sourceType, ['normal_store', 'genetic_item'], true)) {
        throw new InvalidArgumentException('source_type must be normal_store or genetic_item.');
    }
    $sourceItemId = mouse_lp_store_admin_required_integer($request['source_item_id'] ?? null, 'source_item_id', 1);
    $lpPrice = mouse_lp_store_admin_nullable_positive_integer($request['lp_price'] ?? null, 'lp_price');
    $dspoincPrice = mouse_lp_store_admin_nullable_positive_integer($request['dspoinc_price'] ?? null, 'dspoinc_price');
    $stock = mouse_lp_store_admin_required_integer($request['stock'] ?? null, 'stock', 0);
    $isActive = mouse_lp_store_admin_binary($request['is_active'] ?? null, 'is_active');
    if ($lpPrice === null && $dspoincPrice === null) {
        throw new InvalidArgumentException('Enable at least one payment method.');
    }
    if (!mouse_lp_store_admin_source($pdo, $sourceType, $sourceItemId)) {
        mouse_lp_store_admin_json(['success' => false, 'error' => 'Source catalog item was not found.'], 404);
    }

    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'INSERT INTO tbl_mouse_lp_store_offers
            (source_type, source_item_id, lp_price, dspoinc_price, stock, is_active, created_by, updated_by, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
         ON CONFLICT(source_type, source_item_id) DO UPDATE SET
            lp_price = excluded.lp_price,
            dspoinc_price = excluded.dspoinc_price,
            stock = excluded.stock,
            is_active = excluded.is_active,
            updated_by = excluded.updated_by,
            updated_at = CURRENT_TIMESTAMP'
    );
    $statement->execute([$sourceType, $sourceItemId, $lpPrice, $dspoincPrice, $stock, $isActive, $actor, $actor]);
    $lookup = $pdo->prepare('SELECT offer_id FROM tbl_mouse_lp_store_offers WHERE source_type = ? AND source_item_id = ?');
    $lookup->execute([$sourceType, $sourceItemId]);
    $offerId = (int)$lookup->fetchColumn();
    $pdo->commit();

    mouse_lp_store_admin_json(['success' => true, 'offer_id' => $offerId, 'offers' => mouse_lp_store_admin_offers($pdo)]);
} catch (InvalidArgumentException $error) {
    mouse_lp_store_admin_json(['success' => false, 'error' => $error->getMessage()], 422);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Mouse LP Store management: ' . $error->getMessage());
    mouse_lp_store_admin_json(['success' => false, 'error' => 'Mouse LP Store management is unavailable.'], 500);
}
