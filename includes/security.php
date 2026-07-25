<?php
declare(strict_types=1);

/**
 * Shared security helpers: sessions, CSRF, escaping, input cleaning, rate limits.
 * Include this early on every public/admin PHP entry point.
 */

/**
 * Start a hardened session (call once per request, before any output).
 */
function security_bootstrap(): void
{
    // Never leak PHP warnings/stack traces to the browser (log only).
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');

    if (session_status() === PHP_SESSION_ACTIVE) {
        send_security_headers();
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,   // Secure flag only when HTTPS — avoids breaking local XAMPP HTTP
        'httponly' => true,    // Mitigates JS cookie theft (XSS)
        'samesite' => 'Lax',   // CSRF mitigation without breaking top-level wa.me / mailto navigations
    ]);

    session_start();

    // Periodic session ID rotation reduces fixation risk for long-lived sessions.
    $now = time();
    $lastRegen = (int) ($_SESSION['_last_regen'] ?? 0);
    if ($lastRegen === 0 || ($now - $lastRegen) > 900) { // every 15 minutes
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = $now;
    }

    send_security_headers();
}

/**
 * Security headers on every HTML/PHP response (complements .htaccess).
 */
function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    // style/font: allow Google Fonts only (no unsafe-inline). Scripts stay self-only.
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/**
 * HTML-escape for XSS prevention. Use for EVERY user/DB value echoed into HTML,
 * including href, value, and data-* attributes.
 */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Trim + strip control characters from text input before validation/storage.
 */
function clean_text(string $value): string
{
    $value = trim($value);
    // Strip ASCII controls; keep normal Unicode letters/symbols for names/descriptions.
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    return $value;
}

/**
 * Generate / store a per-session CSRF token (random_bytes → hex).
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/**
 * Hidden input HTML for forms. Always echo via this helper (token already safe hex).
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

/**
 * Verify POST CSRF with hash_equals (timing-safe). Returns false if missing/mismatch.
 */
function csrf_verify(?string $token): bool
{
    $sessionToken = $_SESSION['_csrf'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '' || $token === null || $token === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

/**
 * Per-session rate limiter (easy to swap for Redis/IP store later).
 * Returns true if allowed; false if caller must wait.
 *
 * @param string $bucket Logical action name (e.g. "contact", "login")
 * @param int    $minSeconds Minimum seconds between successful checks
 * @param bool   $record If false, only check without updating the timestamp
 */
function rate_limit_allow(string $bucket, int $minSeconds = 30, bool $record = true): bool
{
    if (!isset($_SESSION['_rate']) || !is_array($_SESSION['_rate'])) {
        $_SESSION['_rate'] = [];
    }

    $now = time();
    $last = (int) ($_SESSION['_rate'][$bucket] ?? 0);

    if ($last > 0 && ($now - $last) < $minSeconds) {
        return false;
    }

    if ($record) {
        $_SESSION['_rate'][$bucket] = $now;
    }
    return true;
}

/**
 * Failed-login lockout (session-scoped; easy to swap for Redis/IP store).
 * Returns true if the attempt may proceed.
 */
function login_attempt_allowed(): bool
{
    if (!isset($_SESSION['_login_fails'])) {
        $_SESSION['_login_fails'] = 0;
        $_SESSION['_login_lock_until'] = 0;
    }
    $until = (int) ($_SESSION['_login_lock_until'] ?? 0);
    if ($until > time()) {
        return false;
    }
    // Minimum gap between login POSTs (per session).
    return rate_limit_allow('admin_login_gap', 3, false);
}

function login_attempt_retry_after(): int
{
    $until = (int) ($_SESSION['_login_lock_until'] ?? 0);
    $lockLeft = max(0, $until - time());
    $gapLeft = rate_limit_retry_after('admin_login_gap', 3);
    return max($lockLeft, $gapLeft);
}

function login_attempt_fail(int $maxFails = 8, int $lockSeconds = 900): void
{
    rate_limit_allow('admin_login_gap', 3, true);
    $_SESSION['_login_fails'] = (int) ($_SESSION['_login_fails'] ?? 0) + 1;
    if ((int) $_SESSION['_login_fails'] >= $maxFails) {
        // Lock after repeated failures — slows online password guessing.
        $_SESSION['_login_lock_until'] = time() + $lockSeconds;
        $_SESSION['_login_fails'] = 0;
    }
}

function login_attempt_success(): void
{
    rate_limit_allow('admin_login_gap', 3, true);
    $_SESSION['_login_fails'] = 0;
    $_SESSION['_login_lock_until'] = 0;
}

/**
 * Seconds remaining before the bucket allows another attempt (0 if allowed).
 */
function rate_limit_retry_after(string $bucket, int $minSeconds = 30): int
{
    $last = (int) (($_SESSION['_rate'][$bucket] ?? 0));
    if ($last === 0) {
        return 0;
    }
    $remaining = $minSeconds - (time() - $last);
    return max(0, $remaining);
}

/**
 * Honeypot: field must be empty. Bots that fill every input get rejected quietly.
 */
function honeypot_tripped(?string $value): bool
{
    return $value !== null && trim($value) !== '';
}
