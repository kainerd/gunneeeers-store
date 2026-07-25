<?php
declare(strict_types=1);

$isAdminSection = $isAdminSection ?? false;
?>
</main>
<?php if (!$isAdminSection): ?>
<footer class="site-footer">
    <div class="wrap footer-inner">
        <p class="footer-brand"><?= h(STORE_NAME) ?></p>
        <p class="footer-note">No online payments on this site. Orders are confirmed manually over WhatsApp or email.</p>
        <p class="footer-links">
            <a href="<?= h(url('contact.php')) ?>">Contact</a>
            <a href="<?= h(whatsapp_order_url('Hi, I have a question about ' . STORE_NAME)) ?>">WhatsApp</a>
            <a href="<?= h(mailto_order_url('Question about ' . STORE_NAME, 'Hi,')) ?>">Email</a>
        </p>
    </div>
</footer>
<?php endif; ?>
<script src="<?= h(url('js/main.js')) ?>" defer></script>
</body>
</html>
