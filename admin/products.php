<?php
$pageTitle = 'Products';
require_once __DIR__ . '/includes/admin_header.php';
global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    log_activity('product_delete', 'Product #' . $id);
    flash_set('Product deleted.', 'success');
    redirect('admin/products.php');
}

$q = trim($_GET['q'] ?? '');
$args = [];
$where = '1=1';
if ($q !== '') {
    $where .= ' AND p.name LIKE ?';
    $args[] = '%' . $q . '%';
}
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS cat_name
       FROM products p JOIN categories c ON c.id = p.category_id
      WHERE $where
      ORDER BY p.id DESC"
);
$stmt->execute($args);
$rows = $stmt->fetchAll();
?>

<div class="admin-actions">
    <a href="<?= url('admin/product_form.php') ?>" class="btn">+ New product</a>
    <form method="get" style="margin-left:auto">
        <input type="search" name="q" placeholder="Search products…" value="<?= e($q) ?>">
        <button class="btn btn-outline btn-sm" type="submit">Search</button>
    </form>
</div>

<table class="admin-table">
    <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Views</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><img src="<?= url(e($r['image_1'])) ?>" alt=""></td>
            <td><?= e($r['name']) ?><?php if ($r['is_featured']): ?> <span class="cat-pill" style="padding:.1rem .4rem;font-size:.7rem">★</span><?php endif; ?></td>
            <td><?= e($r['cat_name']) ?></td>
            <td><?= money((float)$r['price']) ?></td>
            <td><?= (int)$r['stock'] < 5 ? '<span style="color:var(--accent)">' . (int)$r['stock'] . '</span>' : (int)$r['stock'] ?></td>
            <td><?= (int)$r['views'] ?></td>
            <td>
                <a class="btn btn-outline btn-sm" href="<?= url('admin/product_form.php?id=' . (int)$r['id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this product?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">No products yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
