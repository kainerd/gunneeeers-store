<?php
declare(strict_types=1);

// Admin-only: manage staff users. Role enforced server-side in auth_require.
$requiredRole = 'admin';
require_once __DIR__ . '/_guard.php';

$flash = '';
$flashError = '';
$current = auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $flashError = 'Security check failed.';
    } else {
        $action = clean_text((string) ($_POST['action'] ?? ''));

        try {
            if ($action === 'create') {
                $newUser = clean_text((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $role = clean_text((string) ($_POST['role'] ?? ''));

                if ($newUser === '' || mb_strlen($newUser) > 64 || !preg_match('/^[a-zA-Z0-9._-]+$/', $newUser)) {
                    $flashError = 'Username must be 1–64 chars (letters, numbers, . _ -).';
                } elseif (strlen($password) < 10 || strlen($password) > 128) {
                    $flashError = 'Password must be 10–128 characters.';
                } elseif (!in_array($role, ROLE_WHITELIST, true)) {
                    // Whitelist role — never bind an unchecked role string.
                    $flashError = 'Invalid role.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = db()->prepare(
                        'INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)'
                    );
                    $stmt->execute([$newUser, $hash, $role]);
                    $flash = 'User created.';
                }
            } elseif ($action === 'toggle_active') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) {
                    $flashError = 'Invalid user id.';
                } elseif ($current !== null && $id === (int) $current['id']) {
                    $flashError = 'You cannot deactivate your own account.';
                } else {
                    $stmt = db()->prepare(
                        'UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?'
                    );
                    $stmt->execute([$id]);
                    $flash = 'User status updated.';
                }
            } else {
                $flashError = 'Unknown action.';
            }
        } catch (PDOException $e) {
            // SQLSTATE 23000 = duplicate username — generic message, no schema leak.
            if ($e->getCode() === '23000') {
                $flashError = 'That username is already taken.';
            } else {
                error_log('admin/users.php: ' . $e->getMessage());
                $flashError = 'Could not save user.';
            }
        } catch (Throwable $e) {
            error_log('admin/users.php: ' . $e->getMessage());
            $flashError = 'Could not save user.';
        }
    }
}

$users = [];
try {
    $stmt = db()->prepare(
        'SELECT id, username, role, is_active, created_at, last_login_at
         FROM users
         ORDER BY role ASC, username ASC'
    );
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('admin/users list: ' . $e->getMessage());
    $flashError = 'Could not load users.';
}

$pageTitle = 'Users — ' . STORE_NAME;
$isAdminSection = true;

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-banner">
    <div class="wrap">
        <h1>Users &amp; roles</h1>
        <p>Admin can create staff or admin accounts. Passwords are hashed — plaintext is never stored.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($flash !== ''): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>
        <?php if ($flashError !== ''): ?><div class="alert alert-error"><?= h($flashError) ?></div><?php endif; ?>

        <h2 class="section-head" style="margin-bottom:0.75rem">Create user</h2>
        <form class="form" method="post" action="" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label>
                Username
                <input type="text" name="username" maxlength="64" required pattern="[A-Za-z0-9._\-]+">
            </label>
            <label>
                Password
                <span class="hint">Min 10 characters</span>
                <input type="password" name="password" minlength="10" maxlength="128" required autocomplete="new-password">
            </label>
            <label>
                Role
                <select name="role" required>
                    <?php foreach (ROLE_WHITELIST as $r): ?>
                        <option value="<?= h($r) ?>"><?= h($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary">Create user</button>
        </form>

        <h2 class="section-head" style="margin:2rem 0 0.75rem">Existing users</h2>
        <div class="admin-table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Active</th>
                        <th>Last login</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= h((string) $u['username']) ?></td>
                            <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-admin' : '' ?>"><?= h((string) $u['role']) ?></span></td>
                            <td><?= (int) $u['is_active'] === 1 ? 'Yes' : 'No' ?></td>
                            <td><?= h((string) ($u['last_login_at'] ?? '—')) ?></td>
                            <td>
                                <?php if ($current !== null && (int) $u['id'] !== (int) $current['id']): ?>
                                    <form method="post" action="">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="id" value="<?= h((string) $u['id']) ?>">
                                        <button type="submit" class="btn btn-ghost btn-small">
                                            <?= (int) $u['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <small>(you)</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
