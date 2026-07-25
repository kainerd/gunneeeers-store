<?php
declare(strict_types=1);

/**
 * Include after setting optional $requiredRole ('admin' or null for any staff).
 * Redirects unauthenticated users to login.
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$requiredRole = $requiredRole ?? null;
auth_require(is_string($requiredRole) ? $requiredRole : null);

$isAdminSection = true;
