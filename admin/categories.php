<?php
$pageTitle = 'Categories';
require_once __DIR__ . '/includes/admin_header.php';
global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            try {
                $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')
                    ->execute([$name, slugify($name)]);
                flash_set('Category added.', 'success');
            } catch (PDOException $e) {
                flash_set('That category already exists.', 'error');
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash_set('Category deleted (and its products).', 'success');
    } elseif ($action === 'rename') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name !== '' && $id) {
            $pdo->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?')
                ->execute([$name, slugify($name), $id]);
            flash_set('Category renamed.', 'success');
        }
    }
    redirect('admin/categories.php');
}

$rows = $pdo->query(
    'SELECT c.*, COUNT(p.id) AS n
       FROM categories c
  LEFT JOIN products p ON p.category_id = c.id
   GROUP BY c.id ORDER BY c.name'
)->fetchAll();
?>

<form method="post" class="form-card" style="margin:0 0 2rem 0;max-width:520px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <h3 style="margin-top:0">Add a category</h3>
    <div style="display:flex;gap:.5rem">
        <input class="qty-input" style="flex:1" name="name" placeholder="e.g. Jackets" required>
        <button class="btn">Add</button>
    </div>
</form>

<table class="admin-table">
    <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td>
                <form method="post" style="display:flex;gap:.5rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="rename">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input name="name" value="<?= e($r['name']) ?>" style="border:1px solid var(--border);padding:.3rem .5rem;border-radius:4px;background:var(--bg)">
                    <button class="btn btn-sm btn-outline">Rename</button>
                </form>
            </td>
            <td><?= e($r['slug']) ?></td>
            <td><?= (int)$r['n'] ?></td>
            <td>
                <form method="post" onsubmit="return confirm('Delete this category and ALL its products?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
