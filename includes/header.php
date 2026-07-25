<?php
declare(strict_types=1);

/** @var string $pageTitle */
/** @var string $pageDescription */
/** @var string $activeNav */

$pageTitle = $pageTitle ?? STORE_NAME;
$pageDescription = $pageDescription ?? 'eFootball coins and accounts — message us to order. No online payments.';
$activeNav = $activeNav ?? '';
$isAdminSection = $isAdminSection ?? false;
$showAdminNav = $isAdminSection && auth_check();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= h($pageDescription) ?>">
    <title><?= h($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(url('css/style.css')) ?>">
</head>
<body class="<?= $isAdminSection ? 'is-admin' : 'is-public' ?>">
<?php if ($showAdminNav): ?>
<header class="site-header admin-header">
    <div class="wrap header-inner">
        <a class="brand" href="<?= h(url('admin/dashboard.php')) ?>"><?= h(STORE_NAME) ?> Admin</a>
        <nav class="site-nav admin-nav" aria-label="Admin">
            <a href="<?= h(url('admin/dashboard.php')) ?>">Dashboard</a>
            <?php if (auth_is_admin()): ?>
                <a href="<?= h(url('admin/coins.php')) ?>">Coin prices</a>
                <a href="<?= h(url('admin/users.php')) ?>">Users</a>
            <?php endif; ?>
            <a href="<?= h(url('index.php')) ?>">View store</a>
            <a href="<?= h(url('admin/logout.php')) ?>">Log out</a>
        </nav>
    </div>
</header>
<?php elseif (!$isAdminSection): ?>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="<?= h(url('index.php')) ?>"><?= h(STORE_NAME) ?></a>
        <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="site-nav">
            <span class="nav-toggle-bar" aria-hidden="true"></span>
            <span class="visually-hidden">Menu</span>
        </button>
        <nav class="site-nav" id="site-nav" aria-label="Primary">
            <a href="<?= h(url('index.php')) ?>" class="<?= $activeNav === 'home' ? 'is-active' : '' ?>">Home</a>
            <a href="<?= h(url('services.php')) ?>" class="<?= $activeNav === 'services' ? 'is-active' : '' ?>">Services</a>
            <a href="<?= h(url('coins.php')) ?>" class="<?= $activeNav === 'coins' ? 'is-active' : '' ?>">Coins</a>
            <a href="<?= h(url('accounts.php')) ?>" class="<?= $activeNav === 'accounts' ? 'is-active' : '' ?>">Buy account</a>
            <a href="<?= h(url('sell-account.php')) ?>" class="<?= $activeNav === 'sell' ? 'is-active' : '' ?>">Sell account</a>
            <a href="<?= h(url('contact.php')) ?>" class="<?= $activeNav === 'contact' ? 'is-active' : '' ?>">Contact</a>
        </nav>
    </div>
</header>
<?php else: ?>
<header class="site-header admin-header">
    <div class="wrap header-inner">
        <a class="brand" href="<?= h(url('index.php')) ?>"><?= h(STORE_NAME) ?></a>
    </div>
</header>
<?php endif; ?>
<main id="main">
