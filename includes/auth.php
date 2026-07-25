<?php
declare(strict_types=1);

/**
 * Session auth helpers for role-based staff dashboard.
 * Role checks are server-side only — never trust a client-supplied role field.
 */

/** Dummy bcrypt hash for timing-safe login when user is missing/inactive. */
const AUTH_DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

function auth_user(): ?array
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => (string) ($_SESSION['user_username'] ?? ''),
        'role' => (string) $_SESSION['user_role'],
    ];
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function auth_is_admin(): bool
{
    $user = auth_user();
    return $user !== null && $user['role'] === 'admin';
}

function auth_login(array $user): void
{
    // session_regenerate_id on login: mitigates session fixation.
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_username'] = (string) $user['username'];
    // Role taken from DB row only — never from POST.
    $_SESSION['user_role'] = (string) $user['role'];
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

/**
 * Require login. Optionally require a specific role (whitelist-checked).
 */
function auth_require(?string $requiredRole = null): void
{
    if (!auth_check()) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }

    if ($requiredRole !== null) {
        // Whitelist role before comparing — reject unexpected values.
        if (!in_array($requiredRole, ROLE_WHITELIST, true)) {
            http_response_code(403);
            exit('Forbidden');
        }
        $user = auth_user();
        if ($user === null || $user['role'] !== $requiredRole) {
            http_response_code(403);
            exit('Forbidden — admin role required.');
        }
    }
}
