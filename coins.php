<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Coin packages — ' . STORE_NAME;
$pageDescription = 'eFootball coin packages by website top-up or in-game trade. Prices set by the store.';
$activeNav = 'coins';

$packages = [];
$dbError = false;

try {
    $stmt = db()->prepare(
        'SELECT id, delivery_method, coin_amount, price_label
         FROM coin_packages
         WHERE is_active = 1
         ORDER BY delivery_method ASC, sort_order ASC, coin_amount ASC'
    );
    $stmt->execute();
    $packages = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('coins.php: ' . $e->getMessage());
    $dbError = true;
}

$grouped = ['website' => [], 'in_game' => []];
foreach ($packages as $row) {
    $method = (string) ($row['delivery_method'] ?? '');
    if ($method === 'website' || $method === 'in_game') {
        $grouped[$method][] = $row;
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="wrap">
        <h1>Coin packages</h1>
        <p>Prices are set by the store owner. Order on WhatsApp — we confirm before delivery. No online payment here.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($dbError): ?>
            <div class="alert alert-error">Packages are temporarily unavailable. Please try again later or contact us directly.</div>
        <?php elseif ($packages === []): ?>
            <div class="alert">No coin packages listed yet. Message us on WhatsApp for current prices, or check back soon.</div>
            <div class="cta-row">
                <a class="btn btn-primary" href="<?= h(whatsapp_order_url('Hi ' . STORE_NAME . ', I want current coin prices.')) ?>" rel="noopener noreferrer">Ask on WhatsApp</a>
                <a class="btn btn-ghost" href="<?= h(url('contact.php')) ?>">Contact form</a>
            </div>
        <?php else: ?>
            <?php foreach (['website' => 'Website top-up', 'in_game' => 'In-game trade'] as $methodKey => $methodLabel): ?>
                <?php if ($grouped[$methodKey] === []) {
                    continue;
                } ?>
                <div class="section-head" style="margin-top:1.5rem">
                    <h2><?= h($methodLabel) ?></h2>
                    <p><?= $methodKey === 'website'
                        ? 'Delivered via website top-up after we confirm with you.'
                        : 'Delivered via arranged in-game trade after we confirm with you.' ?></p>
                </div>
                <div class="grid-2" style="margin-bottom:2rem">
                    <?php foreach ($grouped[$methodKey] as $pkg): ?>
                        <?php
                        $amount = (int) $pkg['coin_amount'];
                        $price = (string) $pkg['price_label'];
                        $msg = 'Hi ' . STORE_NAME . ', I want to order ' . $amount . ' coins via ' . $methodLabel . ' (' . $price . ').';
                        $wa = whatsapp_order_url($msg);
                        $mail = mailto_order_url(
                            'Coin order: ' . $amount . ' via ' . $methodLabel,
                            $msg
                        );
                        ?>
                        <article class="package-block">
                            <h3><?= h(number_format($amount)) ?> coins</h3>
                            <p class="meta"><?= h($methodLabel) ?></p>
                            <p class="price"><?= h($price) ?></p>
                            <div class="cta-row">
                                <a class="btn btn-primary btn-small" href="<?= h($wa) ?>" rel="noopener noreferrer">Order on WhatsApp</a>
                                <a class="btn btn-ghost btn-small" href="<?= h($mail) ?>">Email instead</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
