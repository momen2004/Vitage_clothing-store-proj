<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? SITE_NAME . ' — ' . SITE_TAGLINE;
$cartCount = cart_count();
$user = current_user();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,500&family=Special+Elite&family=Cormorant+Garamond:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body data-theme="<?= e($_COOKIE['theme'] ?? 'sepia') ?>">

<header class="site-header">
    <div class="topbar">
        <div class="container topbar-inner">
            <span>Free shipping on vintage finds over $80 — hand-picked since forever.</span>
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">◐</button>
        </div>
    </div>

    <div class="container nav-inner">
        <a href="<?= url('index.php') ?>" class="brand">
            <span class="brand-mark">V</span>
            <span class="brand-text">
                <strong><?= e(SITE_NAME) ?></strong>
                <small><?= e(SITE_TAGLINE) ?></small>
            </span>
        </a>

        <form class="search" action="<?= url('shop.php') ?>" method="get" role="search">
            <input type="search" name="q" placeholder="Search the archives…" value="<?= e($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="Search">⌕</button>
        </form>

        <nav class="main-nav">
            <a href="<?= url('index.php') ?>">Home</a>
            <a href="<?= url('shop.php') ?>">Shop</a>
            <?php if ($user): ?>
                <a href="<?= url('account.php') ?>">Hi, <?= e(explode(' ', $user['full_name'])[0]) ?></a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= url('admin/index.php') ?>" class="badge-admin">Admin</a>
                <?php endif; ?>
                <a href="<?= url('logout.php') ?>">Logout</a>
            <?php else: ?>
                <a href="<?= url('login.php') ?>">Login</a>
                <a href="<?= url('register.php') ?>">Register</a>
            <?php endif; ?>
            <a href="<?= url('cart.php') ?>" class="cart-link">
                Cart <span class="cart-bubble" id="cartBubble"><?= (int)$cartCount ?></span>
            </a>
        </nav>
    </div>
</header>

<?php if ($flash = flash_pop()): ?>
    <div class="container">
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    </div>
<?php endif; ?>

<main class="site-main">
