<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Services — ' . STORE_NAME;
$pageDescription = 'Coins via website or in-game, buy accounts by budget, sell with a photo.';
$activeNav = 'services';

require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="wrap">
        <h1>Services</h1>
        <p>Everything we offer — always confirmed manually. No payments are processed on this website.</p>
    </div>
</section>

<section class="section">
    <div class="wrap grid-2">
        <article class="service-block">
            <h3>Coins via website top-up</h3>
            <p>Browse packages the store owner publishes (amount + price). Message us to order; we complete delivery after confirming.</p>
            <a class="btn btn-navy btn-small" href="<?= h(url('coins.php')) ?>">Coin packages</a>
        </article>
        <article class="service-block">
            <h3>Coins via in-game trade</h3>
            <p>Same admin-managed prices, delivered through an in-game trade arranged with you live.</p>
            <a class="btn btn-navy btn-small" href="<?= h(url('coins.php')) ?>">Coin packages</a>
        </article>
        <article class="service-block">
            <h3>Buy an account</h3>
            <p>There is no public list of PS / Xbox / PC / Mobile accounts. Submit a request with your platform and price range; we match you.</p>
            <a class="btn btn-navy btn-small" href="<?= h(url('accounts.php')) ?>">Request account</a>
        </article>
        <article class="service-block">
            <h3>Sell your account</h3>
            <p>Send platform, level, description, and a required screenshot. We store the request securely and follow up on WhatsApp.</p>
            <a class="btn btn-navy btn-small" href="<?= h(url('sell-account.php')) ?>">Sell your account</a>
        </article>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
