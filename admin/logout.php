<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

auth_logout();

// Start a fresh session after destroy so CSRF/cookies stay consistent for next login.
security_bootstrap();

header('Location: ' . url('admin/login.php'));
exit;
