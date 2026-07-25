<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/upload.php';

$pageTitle = 'Sell your account — ' . STORE_NAME;
$pageDescription = 'Submit your eFootball account with a screenshot for a manual offer.';
$activeNav = 'sell';

$errors = [];
$success = false;
$old = [
    'name' => '',
    'whatsapp' => '',
    'email' => '',
    'platform' => '',
    'account_level' => '',
    'coin_balance' => '',
    'description' => '',
    'asking_price' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = 'Security check failed. Please reload the page and try again.';
    } elseif (honeypot_tripped($_POST['website'] ?? null)) {
        $success = true;
    } elseif (!rate_limit_allow('sell_account', 45)) {
        $wait = rate_limit_retry_after('sell_account', 45);
        $errors[] = 'Please wait ' . $wait . ' seconds before submitting again.';
    } else {
        $old['name'] = clean_text((string) ($_POST['name'] ?? ''));
        $old['whatsapp'] = clean_text((string) ($_POST['whatsapp'] ?? ''));
        $old['email'] = clean_text((string) ($_POST['email'] ?? ''));
        $old['platform'] = clean_text((string) ($_POST['platform'] ?? ''));
        $old['account_level'] = clean_text((string) ($_POST['account_level'] ?? ''));
        $old['coin_balance'] = clean_text((string) ($_POST['coin_balance'] ?? ''));
        $old['description'] = clean_text((string) ($_POST['description'] ?? ''));
        $old['asking_price'] = clean_text((string) ($_POST['asking_price'] ?? ''));

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
        if ($old['account_level'] === '' || mb_strlen($old['account_level']) > 50) {
            $errors[] = 'Account level is required (max 50 characters).';
        }
        if ($old['coin_balance'] !== '' && mb_strlen($old['coin_balance']) > 50) {
            $errors[] = 'Coin balance must be 50 characters or fewer.';
        }
        if ($old['description'] === '' || mb_strlen($old['description']) > 5000) {
            $errors[] = 'Description is required (max 5000 characters).';
        }
        if ($old['asking_price'] !== '' && mb_strlen($old['asking_price']) > 50) {
            $errors[] = 'Asking price must be 50 characters or fewer.';
        }

        $upload = ['ok' => false, 'path' => null, 'error' => null];
        // Only accept the file after other fields pass — avoids orphan uploads on validation errors.
        if ($errors === []) {
            $upload = store_sell_photo($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if (!$upload['ok']) {
                $errors[] = (string) $upload['error'];
            }
        }

        if ($errors === []) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO sell_requests
                        (name, whatsapp, email, platform, account_level, coin_balance, description, asking_price, photo_path, ip_address)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $old['name'],
                    $waDigits,
                    $old['email'] !== '' ? $old['email'] : null,
                    $old['platform'],
                    $old['account_level'],
                    $old['coin_balance'] !== '' ? $old['coin_balance'] : null,
                    $old['description'],
                    $old['asking_price'] !== '' ? $old['asking_price'] : null,
                    $upload['path'],
                    client_ip(),
                ]);
                $success = true;
                $old = array_map(static fn () => '', $old);
            } catch (Throwable $e) {
                error_log('sell-account.php: ' . $e->getMessage());
                delete_upload_if_exists($upload['path']);
                $errors[] = 'Could not save your request. Please try again later.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="wrap">
        <h1>Sell your account</h1>
        <p>Upload a clear screenshot of your squad/account. We review and follow up on WhatsApp.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($success): ?>
            <div class="alert alert-success">Thanks — your sell request and photo were received. We will message you on WhatsApp.</div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= h($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="form" method="post" action="<?= h(url('sell-account.php')) ?>" enctype="multipart/form-data" novalidate>
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
                <span class="hint">Include country code, e.g. 2126…</span>
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
                Account level
                <input type="text" name="account_level" maxlength="50" required value="<?= h($old['account_level']) ?>">
            </label>
            <label>
                Coin balance <span class="hint">(optional)</span>
                <input type="text" name="coin_balance" maxlength="50" value="<?= h($old['coin_balance']) ?>">
            </label>
            <label>
                Description
                <textarea name="description" rows="5" maxlength="5000" required><?= h($old['description']) ?></textarea>
            </label>
            <label>
                Asking price <span class="hint">(optional)</span>
                <input type="text" name="asking_price" maxlength="50" value="<?= h($old['asking_price']) ?>">
            </label>
            <label>
                Account photo / screenshot
                <span class="hint">Required. JPG, PNG, or WebP — max 3 MB</span>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
            </label>
            <button type="submit" class="btn btn-primary">Submit sell request</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
