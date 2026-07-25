<?php
declare(strict_types=1);

/**
 * Buy an account — visitors pick a budget range; staff follow up manually.
 * No public PS/Xbox/PC/Mobile catalog.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Buy an account — ' . STORE_NAME;
$pageDescription = 'Request an eFootball account by choosing your price range.';
$activeNav = 'accounts';

$errors = [];
$success = false;
$old = [
    'name' => '',
    'whatsapp' => '',
    'email' => '',
    'platform' => '',
    'price_range' => '',
    'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = 'Security check failed. Please reload the page and try again.';
    } elseif (honeypot_tripped($_POST['website'] ?? null)) {
        $success = true;
    } elseif (!rate_limit_allow('buy_account', 45)) {
        $wait = rate_limit_retry_after('buy_account', 45);
        $errors[] = 'Please wait ' . $wait . ' seconds before submitting again.';
    } else {
        $old['name'] = clean_text((string) ($_POST['name'] ?? ''));
        $old['whatsapp'] = clean_text((string) ($_POST['whatsapp'] ?? ''));
        $old['email'] = clean_text((string) ($_POST['email'] ?? ''));
        $old['platform'] = clean_text((string) ($_POST['platform'] ?? ''));
        $old['price_range'] = clean_text((string) ($_POST['price_range'] ?? ''));
        $old['notes'] = clean_text((string) ($_POST['notes'] ?? ''));

        if ($old['name'] === '' || mb_strlen($old['name']) > 100) {
            $errors[] = 'Name is required (max 100 characters).';
        }
        $waDigits = preg_replace('/\D+/', '', $old['whatsapp']) ?? '';
        if ($waDigits === '' || strlen($waDigits) < 8 || strlen($waDigits) > 15) {
            $errors[] = 'Enter a valid WhatsApp number (8–15 digits, country code included).';
        }
        if ($old['email'] !== '' && filter_var($old['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Email format looks invalid.';
        }
        if ($old['email'] !== '' && mb_strlen($old['email']) > 255) {
            $errors[] = 'Email is too long.';
        }
        if (!in_array($old['platform'], PLATFORM_WHITELIST, true)) {
            $errors[] = 'Please select a valid platform.';
        }
        // Whitelist price range before bind — never accept free-text ranges.
        if (!in_array($old['price_range'], PRICE_RANGE_WHITELIST, true)) {
            $errors[] = 'Please select a price range.';
        }
        if ($old['notes'] !== '' && mb_strlen($old['notes']) > 2000) {
            $errors[] = 'Notes must be 2000 characters or fewer.';
        }

        if ($errors === []) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO buy_requests
                        (name, whatsapp, email, platform, price_range, notes, ip_address)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $old['name'],
                    $waDigits,
                    $old['email'] !== '' ? $old['email'] : null,
                    $old['platform'],
                    $old['price_range'],
                    $old['notes'] !== '' ? $old['notes'] : null,
                    client_ip(),
                ]);
                $success = true;
                $old = array_map(static fn () => '', $old);
            } catch (Throwable $e) {
                error_log('accounts.php buy: ' . $e->getMessage());
                $errors[] = 'Could not save your request. Please try again later.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="wrap">
        <h1>Buy an account</h1>
        <p>Choose your platform and budget. We match you manually — no online payment on this site.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($success): ?>
            <div class="alert alert-success">Request received. We will contact you on WhatsApp with matching options.</div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= h($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="form" method="post" action="<?= h(url('accounts.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="hp-field" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <label>
                Name
                <input type="text" name="name" maxlength="100" required value="<?= h($old['name']) ?>">
            </label>
            <label>
                WhatsApp number
                <input type="text" name="whatsapp" maxlength="32" required value="<?= h($old['whatsapp']) ?>">
            </label>
            <label>
                Email <span class="hint">(optional)</span>
                <input type="email" name="email" maxlength="255" value="<?= h($old['email']) ?>">
            </label>
            <label>
                Platform
                <select name="platform" required>
                    <option value="">Select…</option>
                    <?php foreach (PLATFORM_WHITELIST as $p): ?>
                        <option value="<?= h($p) ?>" <?= $old['platform'] === $p ? 'selected' : '' ?>><?= h(platform_label($p)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Price range
                <select name="price_range" required>
                    <option value="">Select your budget…</option>
                    <?php foreach (PRICE_RANGE_WHITELIST as $r): ?>
                        <option value="<?= h($r) ?>" <?= $old['price_range'] === $r ? 'selected' : '' ?>><?= h(price_range_label($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Notes <span class="hint">(optional — e.g. preferred players)</span>
                <textarea name="notes" rows="4" maxlength="2000"><?= h($old['notes']) ?></textarea>
            </label>
            <button type="submit" class="btn btn-primary">Request account</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
