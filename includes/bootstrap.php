<?php
declare(strict_types=1);

/**
 * Common bootstrap for every public/admin entry point.
 */

/** Allowed platforms — whitelist before any SQL bind. */
const PLATFORM_WHITELIST = ['mobile', 'ps', 'xbox', 'pc'];

/** Staff roles — whitelist before any SQL bind. */
const ROLE_WHITELIST = ['admin', 'staff'];

/** Buy-account price ranges — whitelist before any SQL bind. */
const PRICE_RANGE_WHITELIST = [
    'under_25',
    '25_50',
    '50_100',
    '100_200',
    '200_plus',
];

/** Coin delivery methods — whitelist before bind. */
const DELIVERY_WHITELIST = ['website', 'in_game'];

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/store.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

security_bootstrap();

/**
 * App base path when installed under a subdirectory (e.g. /gunneeeers-store).
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = dirname($script);
    // Entry points under /admin or /setup should resolve to site root.
    if (str_ends_with($dir, '/admin') || str_ends_with($dir, '/setup')) {
        $dir = dirname($dir);
    }
    $base = rtrim($dir, '/');
    if ($base === '.' || $base === '/') {
        $base = '';
    }
    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $root = base_path();
    if ($path === '') {
        return $root === '' ? '/' : $root . '/';
    }
    return ($root === '' ? '' : $root) . '/' . $path;
}

function platform_label(string $platform): string
{
    return match ($platform) {
        'mobile' => 'Mobile',
        'ps' => 'PlayStation',
        'xbox' => 'Xbox',
        'pc' => 'PC',
        default => $platform,
    };
}

function price_range_label(string $range): string
{
    return match ($range) {
        'under_25' => 'Under $25',
        '25_50' => '$25 – $50',
        '50_100' => '$50 – $100',
        '100_200' => '$100 – $200',
        '200_plus' => '$200+',
        default => $range,
    };
}

function delivery_label(string $method): string
{
    return match ($method) {
        'website' => 'Website top-up',
        'in_game' => 'In-game trade',
        default => $method,
    };
}

function client_ip(): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if (!is_string($ip) || $ip === '') {
        return null;
    }
    // Store raw REMOTE_ADDR only — do not trust X-Forwarded-For without a trusted proxy config.
    return substr(clean_text($ip), 0, 45);
}
