<?php
declare(strict_types=1);

/**
 * ONE-TIME SETUP — create the first admin user.
 *
 * DELETE this entire /setup folder from the server after use.
 * Leaving it online lets anyone who finds it create admin accounts.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

// Kill-switch: refuse entirely once any user exists (403 — no form rendered).
try {
    $countStmt = db()->prepare('SELECT COUNT(*) AS c FROM users');
    $countStmt->execute();
    $existingUsers = (int) ($countStmt->fetch()['c'] ?? 0);
} catch (Throwable $e) {
    error_log('setup/generate-admin.php count: ' . $e->getMessage());
    http_response_code(503);
    exit('Setup unavailable.');
}

if ($existingUsers > 0) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Setup disabled: an admin already exists. Delete the setup/ folder from the server.');
}

$done = false;
$error = '';
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Security check failed.';
    } else {
        $username = clean_text((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($username === '' || mb_strlen($username) > 64 || !preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            $error = 'Username must be 1–64 chars (letters, numbers, . _ -).';
        } elseif (strlen($password) < 10 || strlen($password) > 128) {
            $error = 'Password must be 10–128 characters.';
        } elseif (!hash_equals($password, $confirm)) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // First account is always role=admin — hardcoded, not from POST (prevents role confusion).
                $stmt = db()->prepare(
                    'INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)'
                );
                $stmt->execute([$username, $hash, 'admin']);
                $done = true;
                $info = 'Admin user created. Log in at admin/login.php, then DELETE the setup/ folder immediately.';
            } catch (Throwable $e) {
                error_log('setup/generate-admin.php insert: ' . $e->getMessage());
                $error = 'Could not create admin. See server logs.';
            }
        }
    }
}

$pageTitle = 'Create first admin — ' . STORE_NAME;
$isAdminSection = true;

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="section">
    <div class="wrap">
        <div class="login-panel">
            <h1>Create first admin</h1>
            <div class="alert alert-error">
                <strong>Delete after use.</strong> Remove the entire <code>setup/</code> directory from the server once this account exists.
            </div>

            <?php if ($done): ?>
                <div class="alert alert-success"><?= h($info) ?></div>
                <p><a class="btn btn-primary" href="<?= h(url('admin/login.php')) ?>">Go to login</a></p>
            <?php else: ?>
                <?php if ($error !== ''): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
                <form class="form" method="post" action="" autocomplete="off">
                    <?= csrf_field() ?>
                    <label>
                        Admin username
                        <input type="text" name="username" maxlength="64" required pattern="[A-Za-z0-9._\-]+">
                    </label>
                    <label>
                        Password
                        <span class="hint">Min 10 characters</span>
                        <input type="password" name="password" minlength="10" maxlength="128" required autocomplete="new-password">
                    </label>
                    <label>
                        Confirm password
                        <input type="password" name="password_confirm" minlength="10" maxlength="128" required autocomplete="new-password">
                    </label>
                    <button type="submit" class="btn btn-primary">Create admin</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
