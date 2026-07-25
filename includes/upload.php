<?php
declare(strict_types=1);

/**
 * Secure image upload helper.
 * Tradeoff vs "just move_uploaded_file": we verify real MIME with finfo and
 * rewrite the filename so extension/path tricks cannot execute as PHP.
 */

const UPLOAD_MAX_BYTES = 3145728; // 3 MB
const UPLOAD_ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

/**
 * @return array{ok:bool, path:?string, error:?string} path is relative web path e.g. uploads/sell/abc.jpg
 */
function store_sell_photo(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'path' => null, 'error' => 'A screenshot/photo of the account is required.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'error' => 'Upload failed. Try a smaller JPG/PNG/WebP (max 3 MB).'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > UPLOAD_MAX_BYTES) {
        return ['ok' => false, 'path' => null, 'error' => 'Photo must be between 1 byte and 3 MB.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'path' => null, 'error' => 'Invalid upload.'];
    }

    // MIME from file contents — not from client-provided type/name.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    if (!isset(UPLOAD_ALLOWED_MIME[$mime])) {
        return ['ok' => false, 'path' => null, 'error' => 'Only JPG, PNG, or WebP images are allowed.'];
    }

    // Reject non-images / corrupt files (no @ — check return value explicitly).
    $imageInfo = getimagesize($tmp);
    if ($imageInfo === false) {
        return ['ok' => false, 'path' => null, 'error' => 'File does not look like a valid image.'];
    }

    $ext = UPLOAD_ALLOWED_MIME[$mime];
    $dir = dirname(__DIR__) . '/uploads/sell';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        error_log('store_sell_photo: cannot create upload dir');
        return ['ok' => false, 'path' => null, 'error' => 'Upload storage unavailable. Try again later.'];
    }

    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'path' => null, 'error' => 'Could not save the photo.'];
    }

    // Relative path for DB / admin <img src> — never trust user filename.
    return ['ok' => true, 'path' => 'uploads/sell/' . $name, 'error' => null];
}

function delete_upload_if_exists(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }
    // Only allow deleting under uploads/sell/ — path traversal defense.
    if (!preg_match('#^uploads/sell/[a-f0-9]{32}\.(jpg|png|webp)$#', $relativePath)) {
        return;
    }
    $full = dirname(__DIR__) . '/' . $relativePath;
    if (is_file($full)) {
        unlink($full);
    }
}
