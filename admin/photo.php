<?php
declare(strict_types=1);

/**
 * Serve sell-request photos to authenticated staff only.
 * Uploads are denied to the public via uploads/sell/.htaccess.
 */
require_once __DIR__ . '/_guard.php';

$name = clean_text((string) ($_GET['f'] ?? ''));
// Whitelist filename shape — no path traversal, no odd extensions.
if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) !== 1) {
    http_response_code(404);
    exit('Not found');
}

$path = dirname(__DIR__) . '/uploads/sell/' . $name;
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
