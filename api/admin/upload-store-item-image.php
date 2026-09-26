<?php
/**
 * Store item image upload boundary.
 *
 * This endpoint accepts a validated presentation image for one existing Store
 * item. It never creates items, changes purchase rules, or accepts browser
 * supplied filesystem paths, names, URLs, or administrator identity.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/admin-auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

const STORE_ITEM_IMAGE_MAX_BYTES = 2000000;
const STORE_ITEM_IMAGE_MAX_DIMENSION = 4096;

function store_item_image_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function store_item_image_is_local(): bool
{
    return PHP_OS_FAMILY === 'Windows'
        || strpos((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), 'xampp') !== false;
}

function store_item_image_storage_directory(): string
{
    if (store_item_image_is_local()) {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'store-items';
    }

    return '/data/public/store-items';
}

function store_item_image_upload_error(int $error): string
{
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'The image exceeds the server upload limit.',
        UPLOAD_ERR_FORM_SIZE => 'The image exceeds the form upload limit.',
        UPLOAD_ERR_PARTIAL => 'The image upload was incomplete.',
        UPLOAD_ERR_NO_FILE => 'Choose an image before uploading.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server temporary upload directory is unavailable.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
        UPLOAD_ERR_EXTENSION => 'The server rejected the image upload.',
    ];

    return $messages[$error] ?? 'The image upload failed.';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    store_item_image_json(['success' => false, 'error' => 'Method not allowed.'], 405);
}

checkAdminAuthentication();

$itemIdRaw = $_POST['item_id'] ?? null;
if (!is_string($itemIdRaw) || !preg_match('/^[1-9]\d*$/', $itemIdRaw)) {
    store_item_image_json(['success' => false, 'error' => 'item_id must be a positive integer.'], 422);
}
$itemId = (int)$itemIdRaw;

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    store_item_image_json(['success' => false, 'error' => 'Choose an image before uploading.'], 422);
}
$image = $_FILES['image'];
$uploadError = (int)($image['error'] ?? UPLOAD_ERR_NO_FILE);
if ($uploadError !== UPLOAD_ERR_OK) {
    store_item_image_json(['success' => false, 'error' => store_item_image_upload_error($uploadError)], 422);
}

$tmpName = (string)($image['tmp_name'] ?? '');
$fileSize = (int)($image['size'] ?? 0);
if ($fileSize < 1 || $fileSize > STORE_ITEM_IMAGE_MAX_BYTES) {
    store_item_image_json(['success' => false, 'error' => 'Images must be no larger than 2 MB.'], 422);
}
if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    store_item_image_json(['success' => false, 'error' => 'The uploaded image could not be verified.'], 422);
}

$mimeInfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $mimeInfo === false ? false : finfo_file($mimeInfo, $tmpName);
if ($mimeInfo !== false) {
    finfo_close($mimeInfo);
}

$extensions = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
];
if (!is_string($mime) || !isset($extensions[$mime])) {
    store_item_image_json(['success' => false, 'error' => 'Only PNG, JPEG, and WEBP images are allowed.'], 422);
}

$imageInfo = @getimagesize($tmpName);
if ($imageInfo === false || !isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])) {
    store_item_image_json(['success' => false, 'error' => 'The uploaded file is not a valid image.'], 422);
}
$width = (int)$imageInfo[0];
$height = (int)$imageInfo[1];
if ($imageInfo['mime'] !== $mime || $width < 1 || $height < 1
    || $width > STORE_ITEM_IMAGE_MAX_DIMENSION || $height > STORE_ITEM_IMAGE_MAX_DIMENSION) {
    store_item_image_json(['success' => false, 'error' => 'The image type or dimensions are invalid.'], 422);
}

$db = null;
try {
    $db = new SQLite3(getDatabasePath());
    $itemStatement = $db->prepare('SELECT item_id FROM tbl_store_items WHERE item_id = ?');
    $itemStatement->bindValue(1, $itemId, SQLITE3_INTEGER);
    $itemResult = $itemStatement->execute();
    if (!$itemResult->fetchArray(SQLITE3_ASSOC)) {
        store_item_image_json(['success' => false, 'error' => 'Store item not found.'], 404);
    }

    $storageDirectory = store_item_image_storage_directory();
    if (!is_dir($storageDirectory) && store_item_image_is_local()) {
        if (!mkdir($storageDirectory, 0775, true) && !is_dir($storageDirectory)) {
            throw new RuntimeException('Local Store image directory could not be created.');
        }
    }
    if (!is_dir($storageDirectory) || !is_writable($storageDirectory)) {
        throw new RuntimeException('Store image directory is unavailable.');
    }

    $filename = sprintf('store-item-%d-%s.%s', $itemId, bin2hex(random_bytes(16)), $extensions[$mime]);
    $targetPath = $storageDirectory . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($targetPath)) {
        throw new RuntimeException('Generated Store image filename already exists.');
    }
    if (!move_uploaded_file($tmpName, $targetPath) || !is_file($targetPath) || filesize($targetPath) < 1) {
        throw new RuntimeException('Uploaded Store image could not be written.');
    }

    $publicUrl = '/public/store-items/' . $filename;
    $updateStatement = $db->prepare('UPDATE tbl_store_items SET image_url = ? WHERE item_id = ?');
    $updateStatement->bindValue(1, $publicUrl, SQLITE3_TEXT);
    $updateStatement->bindValue(2, $itemId, SQLITE3_INTEGER);
    $updateResult = $updateStatement->execute();
    if ($updateResult === false || $db->changes() !== 1) {
        error_log('Store image upload orphaned file after database update failure: ' . $targetPath);
        store_item_image_json(['success' => false, 'error' => 'Image was stored but the Store item could not be updated.'], 500);
    }

    store_item_image_json([
        'success' => true,
        'item_id' => $itemId,
        'image_url' => $publicUrl,
    ]);
} catch (Throwable $error) {
    error_log('Store item image upload: ' . $error->getMessage());
    store_item_image_json(['success' => false, 'error' => 'Store image upload is unavailable.'], 500);
} finally {
    if ($db instanceof SQLite3) {
        $db->close();
    }
}
