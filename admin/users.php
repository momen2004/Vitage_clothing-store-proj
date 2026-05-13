<?php
$pageTitle = 'Users';
require_once __DIR__ . '/includes/admin_header.php';
global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id === (int)current_user()['id']) {
        flash_set('You cannot modify your own admin account here.', 'error');
        redirect('admin/users.php');
    }
    if ($action === 'role') {
        $role = $_POST['role'] ?? 'user';
        if (in_array($role, ['user','admin'], true)) {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
            flash_set('Role updated.', 'success');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash_set('User deleted.', 'success');
    }
    redirect('admin/users.php');
}

$rows = $pdo->query(
    'SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total),0) AS spent
       FROM users u
  LEFT JOIN orders o ON o.user_id = u.id
   GROUP BY u.id
   ORDER BY u.id DESC'
)->fetchAll();
?>

<table class="admin-table">
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Orders</th><th>Spent</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= e($r['full_name']) ?></td>
            <td><?= e($r['email']) ?></td>
            <td>
                <?php if ((int)$r['id'] === (int)current_user()['id']): ?>
                    <span class="cat-pill" style="padding:.15rem .5rem">you</span>
                <?php else: ?>
                    <form method="post" style="display:flex;gap:.4rem">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="role">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <select name="role" onchange="this.form.submit()">
                            <option value="user" <?= $r['role']==='user'?'selected':'' ?>>user</option>
                            <option value="admin"<?= $r['role']==='admin'?'selected':'' ?>>admin</option>
                        </select>
                    </form>
                <?php endif; ?>
            </td>
            <td><?= (int)$r['order_count'] ?></td>
            <td><?= money((float)$r['spent']) ?></td>
            <td><?= e(substr($r['created_at'], 0, 10)) ?></td>
            <td>
                <?php if ((int)$r['id'] !== (int)current_user()['id']): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this user permanently?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
