<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$user = auth_user();
$flash = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $flashError = 'Security check failed.';
    } else {
        $action = clean_text((string) ($_POST['action'] ?? ''));

        try {
            if ($action === 'mark_read') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) {
                    $flashError = 'Invalid message id.';
                } else {
                    $stmt = db()->prepare('UPDATE messages SET is_read = 1 WHERE id = ?');
                    $stmt->execute([$id]);
                    $flash = 'Message marked as read.';
                }
            } elseif ($action === 'set_sell_status') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $status = clean_text((string) ($_POST['status'] ?? ''));
                $allowedStatus = ['new', 'contacted', 'closed'];
                if ($id === false || $id < 1 || !in_array($status, $allowedStatus, true)) {
                    $flashError = 'Invalid sell-request update.';
                } else {
                    $stmt = db()->prepare('UPDATE sell_requests SET status = ? WHERE id = ?');
                    $stmt->execute([$status, $id]);
                    $flash = 'Sell request updated.';
                }
            } elseif ($action === 'set_buy_status') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $status = clean_text((string) ($_POST['status'] ?? ''));
                $allowedStatus = ['new', 'contacted', 'closed'];
                if ($id === false || $id < 1 || !in_array($status, $allowedStatus, true)) {
                    $flashError = 'Invalid buy-request update.';
                } else {
                    $stmt = db()->prepare('UPDATE buy_requests SET status = ? WHERE id = ?');
                    $stmt->execute([$status, $id]);
                    $flash = 'Buy request updated.';
                }
            } else {
                $flashError = 'Unknown action.';
            }
        } catch (Throwable $e) {
            error_log('admin/dashboard.php: ' . $e->getMessage());
            $flashError = 'Could not update. Try again later.';
        }
    }
}

$messages = [];
$sells = [];
$buys = [];
$listError = false;

try {
    $m = db()->prepare(
        'SELECT id, name, email, whatsapp, subject, body, created_at, is_read
         FROM messages
         ORDER BY created_at DESC
         LIMIT 100'
    );
    $m->execute();
    $messages = $m->fetchAll();

    $s = db()->prepare(
        'SELECT id, name, whatsapp, email, platform, account_level, coin_balance,
                description, asking_price, photo_path, status, created_at
         FROM sell_requests
         ORDER BY created_at DESC
         LIMIT 100'
    );
    $s->execute();
    $sells = $s->fetchAll();

    $b = db()->prepare(
        'SELECT id, name, whatsapp, email, platform, price_range, notes, status, created_at
         FROM buy_requests
         ORDER BY created_at DESC
         LIMIT 100'
    );
    $b->execute();
    $buys = $b->fetchAll();
} catch (Throwable $e) {
    error_log('admin/dashboard list: ' . $e->getMessage());
    $listError = true;
}

$pageTitle = 'Dashboard — ' . STORE_NAME;
$isAdminSection = true;

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-banner">
    <div class="wrap">
        <h1>Dashboard</h1>
        <p>
            Signed in as <?= h((string) ($user['username'] ?? '')) ?>
            · role <span class="badge <?= ($user['role'] ?? '') === 'admin' ? 'badge-admin' : '' ?>"><?= h((string) ($user['role'] ?? '')) ?></span>
        </p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($flash !== ''): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>
        <?php if ($flashError !== ''): ?><div class="alert alert-error"><?= h($flashError) ?></div><?php endif; ?>
        <?php if ($listError): ?><div class="alert alert-error">Could not load submissions.</div><?php endif; ?>

        <h2 class="section-head" style="margin-bottom:0.75rem">Messages</h2>
        <?php if ($messages === []): ?>
            <p class="empty-state">No messages yet.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $row): ?>
                            <tr>
                                <td><?= h((string) $row['created_at']) ?></td>
                                <td>
                                    <?= h((string) $row['name']) ?><br>
                                    <small><?= h((string) $row['email']) ?></small>
                                    <?php if (!empty($row['whatsapp'])): ?>
                                        <br><small>WA: <?= h((string) $row['whatsapp']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h((string) $row['subject']) ?>
                                    <?php if (!(int) $row['is_read']): ?> <span class="badge">New</span><?php endif; ?>
                                </td>
                                <td><?= nl2br(h((string) $row['body'])) ?></td>
                                <td>
                                    <?php if (!(int) $row['is_read']): ?>
                                        <form method="post" action="">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="id" value="<?= h((string) $row['id']) ?>">
                                            <button type="submit" class="btn btn-ghost btn-small">Mark read</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="section-head" style="margin:2rem 0 0.75rem">Buy requests</h2>
        <?php if ($buys === []): ?>
            <p class="empty-state">No buy requests yet.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Contact</th>
                            <th>Wanted</th>
                            <th>Notes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buys as $row): ?>
                            <tr>
                                <td><?= h((string) $row['created_at']) ?></td>
                                <td>
                                    <?= h((string) $row['name']) ?><br>
                                    <small>WA: <?= h((string) $row['whatsapp']) ?></small>
                                    <?php if (!empty($row['email'])): ?>
                                        <br><small><?= h((string) $row['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h(platform_label((string) $row['platform'])) ?><br>
                                    <strong><?= h(price_range_label((string) $row['price_range'])) ?></strong>
                                </td>
                                <td><?= $row['notes'] !== null && $row['notes'] !== '' ? nl2br(h((string) $row['notes'])) : '—' ?></td>
                                <td>
                                    <form method="post" action="">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="set_buy_status">
                                        <input type="hidden" name="id" value="<?= h((string) $row['id']) ?>">
                                        <select name="status">
                                            <?php foreach (['new', 'contacted', 'closed'] as $st): ?>
                                                <option value="<?= h($st) ?>" <?= (string) $row['status'] === $st ? 'selected' : '' ?>><?= h($st) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-ghost btn-small" style="margin-top:0.35rem">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="section-head" style="margin:2rem 0 0.75rem">Sell requests</h2>
        <?php if ($sells === []): ?>
            <p class="empty-state">No sell requests yet.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Contact</th>
                            <th>Account</th>
                            <th>Photo / details</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sells as $row): ?>
                            <?php
                            $photo = (string) ($row['photo_path'] ?? '');
                            $safePhoto = preg_match('#^uploads/sell/([a-f0-9]{32}\.(jpg|png|webp))$#', $photo, $photoMatch) === 1;
                            ?>
                            <tr>
                                <td><?= h((string) $row['created_at']) ?></td>
                                <td>
                                    <?= h((string) $row['name']) ?><br>
                                    <small>WA: <?= h((string) $row['whatsapp']) ?></small>
                                    <?php if (!empty($row['email'])): ?>
                                        <br><small><?= h((string) $row['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h(platform_label((string) $row['platform'])) ?><br>
                                    <small><?= h((string) $row['account_level']) ?></small>
                                    <?php if (!empty($row['asking_price'])): ?>
                                        <br><small>Ask: <?= h((string) $row['asking_price']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($safePhoto): ?>
                                        <a href="<?= h(url('admin/photo.php?f=' . rawurlencode($photoMatch[1]))) ?>" target="_blank" rel="noopener noreferrer">
                                            <img class="sell-thumb" src="<?= h(url('admin/photo.php?f=' . rawurlencode($photoMatch[1]))) ?>" alt="Account screenshot">
                                        </a>
                                    <?php else: ?>
                                        <span class="empty-state">No photo</span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['coin_balance'])): ?>
                                        <br><small>Coins: <?= h((string) $row['coin_balance']) ?></small>
                                    <?php endif; ?>
                                    <div><?= nl2br(h((string) $row['description'])) ?></div>
                                </td>
                                <td>
                                    <form method="post" action="">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="set_sell_status">
                                        <input type="hidden" name="id" value="<?= h((string) $row['id']) ?>">
                                        <select name="status">
                                            <?php foreach (['new', 'contacted', 'closed'] as $st): ?>
                                                <option value="<?= h($st) ?>" <?= (string) $row['status'] === $st ? 'selected' : '' ?>><?= h($st) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-ghost btn-small" style="margin-top:0.35rem">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
