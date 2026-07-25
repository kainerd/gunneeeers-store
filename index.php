<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = STORE_NAME . ' — eFootball Coins & Accounts';
$pageDescription = 'Buy eFootball coins and accounts, or sell your account. Orders confirmed on WhatsApp — no online payments.';
$activeNav = 'home';

require __DIR__ . '/includes/header.php';
?>
<section class="hero" aria-label="Welcome">
    <div class="wrap hero-inner">
        <p class="hero-brand"><?= h(STORE_NAME) ?></p>
        <h1>eFootball coins &amp; accounts</h1>
        <p>Message us to order. We confirm the deal, then deliver — website top-up, in-game trade, or account handoff.</p>
        <div class="cta-row">
            <a class="btn btn-primary" href="<?= h(url('coins.php')) ?>">Browse coins</a>
            <a class="btn btn-secondary" href="<?= h(whatsapp_order_url('Hi ' . STORE_NAME . ', I want to place an order.')) ?>">WhatsApp us</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="trust-strip" aria-label="Trust points">
            <span>Manual confirmation</span>
            <span>No card payments on-site</span>
            <span>WhatsApp &amp; email support</span>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="wrap">
        <div class="section-head">
            <h2>Services</h2>
            <p>Four ways we help — pick a path and we’ll confirm details with you directly.</p>
        </div>
        <div class="grid-2">
            <div class="service-block">
                <h3>Coins — website top-up</h3>
                <p>Packages and prices are set by the store. Order via WhatsApp after you pick a package.</p>
                <a class="btn btn-navy btn-small" href="<?= h(url('coins.php')) ?>">View packages</a>
            </div>
            <div class="service-block">
                <h3>Coins — in-game trade</h3>
                <p>Same admin-managed catalog, delivered through an in-game trade arranged with you.</p>
                <a class="btn btn-navy btn-small" href="<?= h(url('coins.php')) ?>">View packages</a>
            </div>
            <div class="service-block">
                <h3>Buy an account</h3>
                <p>Choose your platform and price range. We match you manually — no public account list.</p>
                <a class="btn btn-navy btn-small" href="<?= h(url('accounts.php')) ?>">Request account</a>
            </div>
            <div class="service-block">
                <h3>Sell your account</h3>
                <p>Upload a screenshot and your details. We review and reply on WhatsApp with an offer.</p>
                <a class="btn btn-navy btn-small" href="<?= h(url('sell-account.php')) ?>">Sell form</a>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="section-head">
            <h2>How it works</h2>
            <p>Simple, human, and off-platform for payment.</p>
        </div>
        <div class="grid-3">
            <div class="step">
                <h3>1. Message us</h3>
                <p>Use WhatsApp, email, or the buy/sell request forms.</p>
            </div>
            <div class="step">
                <h3>2. We confirm</h3>
                <p>Price, delivery method, and timing are agreed with a real person.</p>
            </div>
            <div class="step">
                <h3>3. We deliver</h3>
                <p>Coins or account transfer happens after confirmation. No checkout cart here.</p>
            </div>
        </div>
        <p style="margin-top:1.75rem">
            <a class="btn btn-primary" href="<?= h(url('services.php')) ?>">Full service details</a>
            <a class="btn btn-ghost" href="<?= h(url('contact.php')) ?>">Contact</a>
        </p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
