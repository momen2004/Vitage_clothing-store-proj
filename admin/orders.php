<?php
$pageTitle = 'Orders';
require_once __DIR__ . '/includes/admin_header.php';
global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['pending','paid','shipped','delivered','cancelled'], true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        log_activity('order_status', 'Order #' . $id . ' → ' . $newStatus);
        flash_set('Order updated.', 'success');
    }
    redirect('admin/orders.php' . (isset($_POST['view']) ? '?id=' . $id : ''));
}

$viewId = (int)($_GET['id'] ?? 0);

if ($viewId) {
    $order = $pdo->prepare(
        'SELECT o.*, u.full_name, u.email
           FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?'
    );
    $order->execute([$viewId]);
    $order = $order->fetch();

    $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $items->execute([$viewId]);
    $items = $items->fetchAll();
    ?>

    <a href="<?= url('admin/orders.php') ?>" class="btn btn-outline btn-sm">← All orders</a>

    <?php if (!$order): ?>
        <p>Order not found.</p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;margin-top:1.5rem">
            <div>
                <h3>Order #<?= (int)$order['id'] ?></h3>
                <table class="admin-table">
                    <thead><tr><th>Item</th><th>Size</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= e($it['name']) ?></td>
                            <td><?= e($it['size'] ?: '—') ?></td>
                            <td><?= money((float)$it['price']) ?></td>
                            <td><?= (int)$it['quantity'] ?></td>
                            <td><?= money((float)$it['price'] * (int)$it['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="text-align:right;font-family:var(--type);font-size:1.1rem;margin-top:1rem">
                    Total: <strong><?= money((float)$order['total']) ?></strong>
                </p>
            </div>
            <aside class="form-card" style="margin:0">
                <h4 style="margin-top:0">Customer</h4>
                <p><strong><?= e($order['full_name']) ?></strong><br>
                   <?= e($order['email']) ?><br>
                   <?= e($order['phone']) ?></p>
                <h4>Shipping to</h4>
                <p><?= nl2br(e($order['shipping_addr'])) ?></p>
                <?php if ($order['notes']): ?>
                    <h4>Notes</h4>
                    <p><?= nl2br(e($order['notes'])) ?></p>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                    <input type="hidden" name="view" value="1">
                    <div class="field"><label>Status</label>
                        <select name="status">
                            <?php foreach (['pending','paid','shipped','delivered','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn">Update status</button>
                </form>
            </aside>
        </div>
    <?php endif; ?>

<?php } else {
    $rows = $pdo->query(
        'SELECT o.id, o.total, o.status, o.created_at, u.full_name
           FROM orders o JOIN users u ON u.id = o.user_id
          ORDER BY o.id DESC'
    )->fetchAll();
?>
    <table class="admin-table">
        <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><?= money((float)$r['total']) ?></td>
                <td><?= e($r['status']) ?></td>
                <td><?= e(substr($r['created_at'], 0, 16)) ?></td>
                <td><a class="btn btn-outline btn-sm" href="<?= url('admin/orders.php?id=' . (int)$r['id']) ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">No orders yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
<?php } ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
