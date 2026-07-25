<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Contact — ' . STORE_NAME;
$pageDescription = 'Contact Gunneeeers Store by form, WhatsApp, or email.';
$activeNav = 'contact';

$errors = [];
$success = false;
$old = [
    'name' => '',
    'email' => '',
    'whatsapp' => '',
    'subject' => '',
    'body' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = 'Security check failed. Please reload the page and try again.';
    } elseif (honeypot_tripped($_POST['website'] ?? null)) {
        $success = true;
    } elseif (!rate_limit_allow('contact', 45)) {
        $wait = rate_limit_retry_after('contact', 45);
        $errors[] = 'Please wait ' . $wait . ' seconds before submitting again.';
    } else {
        $old['name'] = clean_text((string) ($_POST['name'] ?? ''));
        $old['email'] = clean_text((string) ($_POST['email'] ?? ''));
        $old['whatsapp'] = clean_text((string) ($_POST['whatsapp'] ?? ''));
        $old['subject'] = clean_text((string) ($_POST['subject'] ?? ''));
        $old['body'] = clean_text((string) ($_POST['body'] ?? ''));

        if ($old['name'] === '' || mb_strlen($old['name']) > 100) {
            $errors[] = 'Name is required (max 100 characters).';
        }
        if ($old['email'] === '' || filter_var($old['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'A valid email is required.';
        }
        if (mb_strlen($old['email']) > 255) {
            $errors[] = 'Email is too long.';
        }
        $waDigits = '';
        if ($old['whatsapp'] !== '') {
            $waDigits = preg_replace('/\D+/', '', $old['whatsapp']) ?? '';
            if (strlen($waDigits) < 8 || strlen($waDigits) > 15) {
                $errors[] = 'WhatsApp number looks invalid (use 8–15 digits with country code).';
            }
        }
        if ($old['subject'] === '' || mb_strlen($old['subject']) > 200) {
            $errors[] = 'Subject is required (max 200 characters).';
        }
        if ($old['body'] === '' || mb_strlen($old['body']) > 5000) {
            $errors[] = 'Message is required (max 5000 characters).';
        }

        if ($errors === []) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO messages (name, email, whatsapp, subject, body, ip_address)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $old['name'],
                    $old['email'],
                    $waDigits !== '' ? $waDigits : null,
                    $old['subject'],
                    $old['body'],
                    client_ip(),
                ]);
                $success = true;
                $old = array_map(static fn () => '', $old);
            } catch (Throwable $e) {
                error_log('contact.php: ' . $e->getMessage());
                $errors[] = 'Could not send your message. Please try again later.';
            }
        }
    }
}

$waDirect = whatsapp_order_url('Hi ' . STORE_NAME . ', I have a question.');
$mailDirect = mailto_order_url('Question for ' . STORE_NAME, 'Hi,');

require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="wrap">
        <h1>Contact</h1>
        <p>Reach us directly or leave a message — we reply manually.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="cta-row" style="margin-bottom:1.75rem">
            <a class="btn btn-primary" href="<?= h($waDirect) ?>" rel="noopener noreferrer">WhatsApp</a>
            <a class="btn btn-ghost" href="<?= h($mailDirect) ?>">Email</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">Message received. We will get back to you soon.</div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= h($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="form" method="post" action="<?= h(url('contact.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="hp-field" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <label>
                Name
                <input type="text" name="name" maxlength="100" required value="<?= h($old['name']) ?>">
            </label>
            <label>
                Email
                <input type="email" name="email" maxlength="255" required value="<?= h($old['email']) ?>">
            </label>
            <label>
                WhatsApp <span class="hint">(optional)</span>
                <input type="text" name="whatsapp" maxlength="32" value="<?= h($old['whatsapp']) ?>">
            </label>
            <label>
                Subject
                <input type="text" name="subject" maxlength="200" required value="<?= h($old['subject']) ?>">
            </label>
            <label>
                Message
                <textarea name="body" rows="6" maxlength="5000" required><?= h($old['body']) ?></textarea>
            </label>
            <button type="submit" class="btn btn-primary">Send message</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
