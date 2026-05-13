<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';

global $pdo;

$stats = [
    'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'users'    => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn(),
    'orders'   => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'revenue'  => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'")->fetchColumn(),
    'low_stock'=> (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock < 5')->fetchColumn(),
    'pending'  => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
];

$recentOrders = $pdo->query(
    'SELECT o.id, o.total, o.status, o.created_at, u.full_name
       FROM orders o JOIN users u ON u.id = o.user_id
      ORDER BY o.id DESC LIMIT 6'
)->fetchAll();

$topProducts = $pdo->query(
    'SELECT p.id, p.name, p.image_1, SUM(oi.quantity) AS sold
       FROM order_items oi JOIN products p ON p.id = oi.product_id
   GROUP BY p.id ORDER BY sold DESC LIMIT 5'
)->fetchAll();
?>

<div class="stats">
    <div class="stat-card"><div class="label">Revenue</div>
        <div class="value"><?= money($stats['revenue']) ?></div>
        <div class="trend">All time</div></div>
    <div class="stat-card"><div class="label">Orders</div>
        <div class="value"><?= $stats['orders'] ?></div>
        <div class="trend"><?= $stats['pending'] ?> pending</div></div>
    <div class="stat-card"><div class="label">Products</div>
        <div class="value"><?= $stats['products'] ?></div>
        <div class="trend"><?= $stats['low_stock'] ?> low-stock</div></div>
    <div class="stat-card"><div class="label">Customers</div>
        <div class="value"><?= $stats['users'] ?></div>
        <div class="trend">Registered users</div></div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:2rem">
    <section>
        <h3 style="font-style:italic">Recent orders</h3>
        <?php if (!$recentOrders): ?>
            <p style="font-family:var(--type);color:var(--muted)">No orders yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><a href="<?= url('admin/orders.php?id=' . (int)$o['id']) ?>">#<?= (int)$o['id'] ?></a></td>
                        <td><?= e($o['full_name']) ?></td>
                        <td><?= money((float)$o['total']) ?></td>
                        <td><?= e($o['status']) ?></td>
                        <td><?= e(substr($o['created_at'], 0, 16)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section>
        <h3 style="font-style:italic">Best sellers</h3>
        <?php if (!$topProducts): ?>
            <p style="font-family:var(--type);color:var(--muted)">No sales yet.</p>
        <?php else: ?>
            <table class="admin-table">
                <tbody>
                <?php foreach ($topProducts as $tp): ?>
                    <tr>
                        <td><img src="<?= url(e($tp['image_1'])) ?>" alt=""></td>
                        <td><?= e($tp['name']) ?></td>
                        <td><strong><?= (int)$tp['sold'] ?></strong> sold</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
