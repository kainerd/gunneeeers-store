<?php
declare(strict_types=1);

// Admin-only: manage coin package prices shown on the public coins page.
$requiredRole = 'admin';
require_once __DIR__ . '/_guard.php';

$flash = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $flashError = 'Security check failed.';
    } else {
        $action = clean_text((string) ($_POST['action'] ?? ''));

        try {
            if ($action === 'create' || $action === 'update') {
                $delivery = clean_text((string) ($_POST['delivery_method'] ?? ''));
                $amount = filter_var($_POST['coin_amount'] ?? null, FILTER_VALIDATE_INT);
                $price = clean_text((string) ($_POST['price_label'] ?? ''));
                $sort = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);
                $active = isset($_POST['is_active']) ? 1 : 0;

                if (!in_array($delivery, DELIVERY_WHITELIST, true)) {
                    $flashError = 'Invalid delivery method.';
                } elseif ($amount === false || $amount < 1 || $amount > 10000000) {
                    $flashError = 'Coin amount must be a positive number.';
                } elseif ($price === '' || mb_strlen($price) > 50) {
                    $flashError = 'Price label is required (max 50 characters), e.g. $9.99.';
                } elseif ($sort === false || $sort < 0 || $sort > 9999) {
                    $flashError = 'Sort order must be 0–9999.';
                } elseif ($action === 'create') {
                    $stmt = db()->prepare(
                        'INSERT INTO coin_packages (delivery_method, coin_amount, price_label, sort_order, is_active)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$delivery, $amount, $price, $sort, $active]);
                    $flash = 'Coin package added.';
                } else {
                    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                    if ($id === false || $id < 1) {
                        $flashError = 'Invalid package id.';
                    } else {
                        $stmt = db()->prepare(
                            'UPDATE coin_packages
                             SET delivery_method = ?, coin_amount = ?, price_label = ?, sort_order = ?, is_active = ?
                             WHERE id = ?'
                        );
                        $stmt->execute([$delivery, $amount, $price, $sort, $active, $id]);
                        $flash = 'Coin package updated.';
                    }
                }
            } elseif ($action === 'delete') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) {
                    $flashError = 'Invalid package id.';
                } else {
                    $stmt = db()->prepare('DELETE FROM coin_packages WHERE id = ?');
                    $stmt->execute([$id]);
                    $flash = 'Coin package removed.';
                }
            } else {
                $flashError = 'Unknown action.';
            }
        } catch (Throwable $e) {
            error_log('admin/coins.php: ' . $e->getMessage());
            $flashError = 'Could not save coin package.';
        }
    }
}

$packages = [];
try {
    $stmt = db()->prepare(
        'SELECT id, delivery_method, coin_amount, price_label, sort_order, is_active
         FROM coin_packages
         ORDER BY delivery_method ASC, sort_order ASC, coin_amount ASC'
    );
    $stmt->execute();
    $packages = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('admin/coins list: ' . $e->getMessage());
    $flashError = 'Could not load packages.';
}

$pageTitle = 'Coin prices — ' . STORE_NAME;
$isAdminSection = true;

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-banner">
    <div class="wrap">
        <h1>Coin prices</h1>
        <p>Add packages here. Only active ones appear on the public Coins page. Nothing is seeded — you control every price.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($flash !== ''): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>
        <?php if ($flashError !== ''): ?><div class="alert alert-error"><?= h($flashError) ?></div><?php endif; ?>

        <h2 class="section-head" style="margin-bottom:0.75rem">Add package</h2>
        <form class="form" method="post" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label>
                Delivery method
                <select name="delivery_method" required>
                    <?php foreach (DELIVERY_WHITELIST as $d): ?>
                        <option value="<?= h($d) ?>"><?= h(delivery_label($d)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Coin amount
                <input type="number" name="coin_amount" min="1" max="10000000" required>
            </label>
            <label>
                Price label
                <span class="hint">Shown to visitors, e.g. $12.00</span>
                <input type="text" name="price_label" maxlength="50" required placeholder="$12.00">
            </label>
            <label>
                Sort order
                <input type="number" name="sort_order" min="0" max="9999" value="10">
            </label>
            <label>
                <input type="checkbox" name="is_active" value="1" checked> Active (visible on site)
            </label>
            <button type="submit" class="btn btn-primary">Add package</button>
        </form>

        <h2 class="section-head" style="margin:2rem 0 0.75rem">Current packages</h2>
        <?php if ($packages === []): ?>
            <p class="empty-state">No coin packages yet. Add your first price above.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Coins</th>
                            <th>Price</th>
                            <th>Sort</th>
                            <th>Active</th>
                            <th>Edit / delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $pkg): ?>
                            <tr>
                                <td colspan="6" style="padding:0">
                                    <form method="post" action="" class="inline-edit-row">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= h((string) $pkg['id']) ?>">
                                        <div class="admin-edit-grid">
                                            <select name="delivery_method" required>
                                                <?php foreach (DELIVERY_WHITELIST as $d): ?>
                                                    <option value="<?= h($d) ?>" <?= (string) $pkg['delivery_method'] === $d ? 'selected' : '' ?>><?= h(delivery_label($d)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="number" name="coin_amount" min="1" value="<?= h((string) $pkg['coin_amount']) ?>" required>
                                            <input type="text" name="price_label" maxlength="50" value="<?= h((string) $pkg['price_label']) ?>" required>
                                            <input type="number" name="sort_order" min="0" max="9999" value="<?= h((string) $pkg['sort_order']) ?>">
                                            <label><input type="checkbox" name="is_active" value="1" <?= (int) $pkg['is_active'] === 1 ? 'checked' : '' ?>> On</label>
                                            <div class="cta-row">
                                                <button type="submit" name="action" value="update" class="btn btn-navy btn-small">Save</button>
                                                <button type="submit" name="action" value="delete" class="btn btn-ghost btn-small">Delete</button>
                                            </div>
                                        </div>
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
