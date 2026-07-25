<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (auth_check()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Security check failed. Please try again.';
    } elseif (!login_attempt_allowed()) {
        $wait = login_attempt_retry_after();
        $error = 'Too many attempts. Wait ' . max(1, $wait) . ' seconds.';
    } else {
        $username = clean_text((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $row = null;
        try {
            $stmt = db()->prepare(
                'SELECT id, username, password_hash, role, is_active
                 FROM users
                 WHERE username = ?
                 LIMIT 1'
            );
            $stmt->execute([$username]);
            $row = $stmt->fetch();
        } catch (Throwable $e) {
            error_log('admin/login.php: ' . $e->getMessage());
            $error = 'Login temporarily unavailable.';
        }

        // Always password_verify (dummy hash if missing) — avoids timing username/active leaks
        // from short-circuiting before verify.
        $hash = is_array($row) ? (string) $row['password_hash'] : AUTH_DUMMY_HASH;
        $verified = password_verify($password, $hash);
        $active = is_array($row) && (int) $row['is_active'] === 1;
        $role = is_array($row) ? (string) $row['role'] : '';
        $ok = $verified && $active && in_array($role, ROLE_WHITELIST, true);

        if ($ok && is_array($row)) {
            login_attempt_success();
            auth_login([
                'id' => (int) $row['id'],
                'username' => (string) $row['username'],
                'role' => $role,
            ]);
            try {
                $upd = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
                $upd->execute([(int) $row['id']]);
            } catch (Throwable $e) {
                error_log('admin/login last_login: ' . $e->getMessage());
            }
            header('Location: ' . url('admin/dashboard.php'));
            exit;
        }

        login_attempt_fail();
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Admin login — ' . STORE_NAME;
$isAdminSection = true;

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="section">
    <div class="wrap">
        <div class="login-panel">
            <h1>Staff login</h1>
            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>
            <form class="form" method="post" action="<?= h(url('admin/login.php')) ?>" autocomplete="username">
                <?= csrf_field() ?>
                <label>
                    Username
                    <input type="text" name="username" maxlength="64" required value="<?= h($username) ?>">
                </label>
                <label>
                    Password
                    <input type="password" name="password" required autocomplete="current-password">
                </label>
                <button type="submit" class="btn btn-primary">Log in</button>
            </form>
        </div>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
