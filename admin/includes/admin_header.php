<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$pageTitle = $pageTitle ?? 'Admin — Vitage';
$current = basename($_SERVER['SCRIPT_NAME']);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Special+Elite&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="admin-body" data-theme="sepia">

<aside class="admin-sidebar">
    <a href="<?= url('admin/index.php') ?>" class="admin-brand">
        <span class="brand-mark">V</span>
        <div>
            <strong>Vitage</strong>
            <small>Admin console</small>
        </div>
    </a>
    <nav>
        <a href="<?= url('admin/index.php') ?>"     class="<?= $current==='index.php'?'active':'' ?>">Dashboard</a>
        <a href="<?= url('admin/products.php') ?>"  class="<?= $current==='products.php'||$current==='product_form.php'?'active':'' ?>">Products</a>
        <a href="<?= url('admin/categories.php') ?>"class="<?= $current==='categories.php'?'active':'' ?>">Categories</a>
        <a href="<?= url('admin/orders.php') ?>"    class="<?= $current==='orders.php'?'active':'' ?>">Orders</a>
        <a href="<?= url('admin/users.php') ?>"     class="<?= $current==='users.php'?'active':'' ?>">Users</a>
        <a href="<?= url('admin/analytics.php') ?>" class="<?= $current==='analytics.php'?'active':'' ?>">Analytics</a>
    </nav>
    <div class="admin-foot">
        <a href="<?= url('index.php') ?>">← Back to store</a>
        <a href="<?= url('logout.php') ?>">Log out</a>
    </div>
</aside>

<main class="admin-main">
    <header class="admin-topbar">
        <h1><?= e($pageTitle) ?></h1>
        <span style="font-family:var(--type);color:var(--muted)">Signed in as <?= e(current_user()['full_name']) ?></span>
    </header>

    <?php if ($flash = flash_pop()): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
