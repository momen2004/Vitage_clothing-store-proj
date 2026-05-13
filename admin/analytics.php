<?php
$pageTitle = 'Analytics';
require_once __DIR__ . '/includes/admin_header.php';
global $pdo;

// Sales for last 14 days
$daily = $pdo->query(
    "SELECT DATE(created_at) AS d, COUNT(*) AS orders, SUM(total) AS revenue
       FROM orders
      WHERE status <> 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
   GROUP BY DATE(created_at)
   ORDER BY d ASC"
)->fetchAll();

// Build complete 14-day series (fill missing with 0)
$series = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} day"));
    $series[$d] = ['orders' => 0, 'revenue' => 0];
}
foreach ($daily as $row) {
    $series[$row['d']] = ['orders' => (int)$row['orders'], 'revenue' => (float)$row['revenue']];
}
$maxRev = max(array_column($series, 'revenue')) ?: 1;

$topProducts = $pdo->query(
    'SELECT p.name, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue
       FROM order_items oi JOIN products p ON p.id = oi.product_id
   GROUP BY p.id ORDER BY sold DESC LIMIT 10'
)->fetchAll();

$catSales = $pdo->query(
    'SELECT c.name, SUM(oi.quantity) AS sold
       FROM order_items oi
       JOIN products p   ON p.id = oi.product_id
       JOIN categories c ON c.id = p.category_id
   GROUP BY c.id ORDER BY sold DESC'
)->fetchAll();

$logs = $pdo->query(
    'SELECT l.*, u.full_name FROM activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        ORDER BY l.id DESC LIMIT 20'
)->fetchAll();

$totalRev   = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'")->fetchColumn();
$totalOrd   = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$avgOrder   = $totalOrd ? $totalRev / $totalOrd : 0;
$totalViews = (int)$pdo->query('SELECT COALESCE(SUM(views),0) FROM products')->fetchColumn();
?>

<div class="stats">
    <div class="stat-card"><div class="label">Revenue</div><div class="value"><?= money($totalRev) ?></div><div class="trend">All time</div></div>
    <div class="stat-card"><div class="label">Orders</div><div class="value"><?= $totalOrd ?></div><div class="trend">All time</div></div>
    <div class="stat-card"><div class="label">Avg order</div><div class="value"><?= money($avgOrder) ?></div><div class="trend">Per order</div></div>
    <div class="stat-card"><div class="label">Product views</div><div class="value"><?= $totalViews ?></div><div class="trend">All time</div></div>
</div>

<div class="chart-wrap">
    <h3 style="font-style:italic">Revenue — last 14 days</h3>
    <div class="bar-chart">
        <?php foreach ($series as $date => $row):
            $h = (int)round(($row['revenue'] / $maxRev) * 180);
        ?>
            <div class="bar"
                 style="height: <?= $h ?>px"
                 data-label="<?= e(substr($date, 5)) ?>"
                 data-value="<?= $row['revenue'] > 0 ? '$' . number_format($row['revenue'], 0) : '' ?>"></div>
        <?php endforeach; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">
    <section>
        <h3 style="font-style:italic">Top products</h3>
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th></tr></thead>
            <tbody>
                <?php foreach ($topProducts as $tp): ?>
                    <tr><td><?= e($tp['name']) ?></td><td><?= (int)$tp['sold'] ?></td><td><?= money((float)$tp['revenue']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$topProducts): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:2rem">No data yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h3 style="font-style:italic">By category</h3>
        <table class="admin-table">
            <thead><tr><th>Category</th><th>Items sold</th></tr></thead>
            <tbody>
                <?php foreach ($catSales as $cs): ?>
                    <tr><td><?= e($cs['name']) ?></td><td><?= (int)$cs['sold'] ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$catSales): ?>
                    <tr><td colspan="2" style="text-align:center;color:var(--muted);padding:2rem">No data yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<section style="margin-top:2.5rem">
    <h3 style="font-style:italic">Recent activity</h3>
    <table class="admin-table">
        <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Detail</th></tr></thead>
        <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= e(substr($l['created_at'], 0, 16)) ?></td>
                    <td><?= e($l['full_name'] ?: '—') ?></td>
                    <td><?= e($l['action']) ?></td>
                    <td><?= e($l['detail'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
