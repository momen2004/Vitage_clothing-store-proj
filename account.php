<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

$orders = $pdo->prepare(
    'SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC'
);
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

$pageTitle = 'My account — Vitage';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="section-title"><h2>My account</h2></div>

    <?php if (isset($_GET['placed'])): ?>
        <div class="flash flash-success">
            Order #<?= (int)$_GET['placed'] ?> placed successfully. We'll be in touch.
        </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:2rem">
        <aside class="form-card" style="margin:0">
            <h3 style="margin-top:0">Profile</h3>
            <p style="font-family:var(--type);font-size:.9rem">
                <strong><?= e($user['full_name']) ?></strong><br>
                <?= e($user['email']) ?><br>
                <?= e($user['phone'] ?: '—') ?><br>
                <?= e($user['address'] ?: '—') ?>
            </p>
            <a href="<?= url('logout.php') ?>" class="btn btn-outline btn-sm">Log out</a>
        </aside>

        <section>
            <h3 style="font-style:italic">Order history</h3>
            <?php if (!$orders): ?>
                <div class="empty">
                    <p>No orders yet. Start exploring the archive.</p>
                    <a class="btn" href="<?= url('shop.php') ?>">Shop now</a>
                </div>
            <?php else: ?>
                <table class="cart-table">
                    <thead><tr><th>#</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= (int)$o['id'] ?></td>
                            <td><?= e(substr($o['created_at'], 0, 10)) ?></td>
                            <td><?= money((float)$o['total']) ?></td>
                            <td><span class="cat-pill" style="padding:.15rem .6rem"><?= e($o['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
