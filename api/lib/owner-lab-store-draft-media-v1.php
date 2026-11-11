<?php
declare(strict_types=1);

// Private-draft presentation storage only. This helper never opens SQLite and
// never writes a catalog, offer, inventory, or any protected domain record.
const NARRRFS_LAB_STORE_DRAFT_MEDIA_MAX_BYTES = 2000000;
const NARRRFS_LAB_STORE_DRAFT_MEDIA_MAX_DIMENSION = 4096;

function narrrfs_lab_store_draft_media_directory(): string
{
    $local = PHP_OS_FAMILY === 'Windows' || str_contains((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), 'xampp');
    return $local
        ? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'store-items' . DIRECTORY_SEPARATOR . 'drafts'
        : '/data/public/store-items/drafts';
}

function narrrfs_lab_store_draft_media_store(array $file, ?callable $isUploaded = null, ?callable $move = null, ?string $directoryOverride = null): string
{
    $isUploaded ??= 'is_uploaded_file';
    $move ??= 'move_uploaded_file';
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    $tmp = $file['tmp_name'] ?? null;
    $size = $file['size'] ?? null;
    if (!is_int($error) || $error !== UPLOAD_ERR_OK || !is_string($tmp) || !is_int($size) || $size < 1 || $size > NARRRFS_LAB_STORE_DRAFT_MEDIA_MAX_BYTES || !$isUploaded($tmp)) {
        throw new InvalidArgumentException('invalid_upload');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo === false ? false : finfo_file($finfo, $tmp);
    if ($finfo !== false) finfo_close($finfo);
    $extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    $dimensions = @getimagesize($tmp);
    if (!is_string($mime) || !isset($extensions[$mime]) || !is_array($dimensions) || !isset($dimensions[0], $dimensions[1], $dimensions['mime']) || $dimensions['mime'] !== $mime || (int) $dimensions[0] < 1 || (int) $dimensions[1] < 1 || (int) $dimensions[0] > NARRRFS_LAB_STORE_DRAFT_MEDIA_MAX_DIMENSION || (int) $dimensions[1] > NARRRFS_LAB_STORE_DRAFT_MEDIA_MAX_DIMENSION) {
        throw new InvalidArgumentException('invalid_image');
    }
    $directory = $directoryOverride ?? narrrfs_lab_store_draft_media_directory();
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) throw new RuntimeException('media_storage_unavailable');
    if (!is_writable($directory)) throw new RuntimeException('media_storage_unavailable');
    $filename = 'draft-media-' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $target = $directory . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($target) || !$move($tmp, $target) || !is_file($target) || (int) filesize($target) < 1) throw new RuntimeException('media_write_failed');
    return '/public/store-items/drafts/' . $filename;
}
