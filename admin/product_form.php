<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];

$row = [
    'category_id' => null, 'name' => '', 'short_desc' => '', 'description' => '',
    'price' => '0.00', 'stock' => 0, 'sizes' => 'S,M,L',
    'image_1' => '', 'image_2' => '', 'image_3' => '', 'is_featured' => 0,
];

if ($isEdit) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $found = $st->fetch();
    if (!$found) { http_response_code(404); exit('Not found'); }
    $row = array_merge($row, $found);
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

function save_upload(string $field, string $current): string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return $current;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) return $current;

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',  'webp' => 'image/webp', 'gif' => 'image/gif'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) throw new RuntimeException('Unsupported image format.');

    $dir = __DIR__ . '/../uploads/products';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $fname = 'p_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest  = $dir . '/' . $fname;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        throw new RuntimeException('Upload failed.');
    }
    return 'uploads/products/' . $fname;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $row['category_id'] = (int)($_POST['category_id'] ?? 0);
    $row['name']        = trim($_POST['name'] ?? '');
    $row['short_desc']  = trim($_POST['short_desc'] ?? '');
    $row['description'] = trim($_POST['description'] ?? '');
    $row['price']       = (float)($_POST['price'] ?? 0);
    $row['stock']       = max(0, (int)($_POST['stock'] ?? 0));
    $row['sizes']       = trim($_POST['sizes'] ?? '');
    $row['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

    if ($row['name'] === '')      $errors[] = 'Name required.';
    if ($row['category_id'] <= 0) $errors[] = 'Pick a category.';
    if ($row['price'] < 0)        $errors[] = 'Price must be positive.';

    try {
        $row['image_1'] = save_upload('image_1', $row['image_1'] ?? '');
        $row['image_2'] = save_upload('image_2', $row['image_2'] ?? '');
        $row['image_3'] = save_upload('image_3', $row['image_3'] ?? '');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if (!$errors) {
        if ($isEdit) {
            $st = $pdo->prepare(
                'UPDATE products SET category_id=?, name=?, short_desc=?, description=?,
                  price=?, stock=?, sizes=?, image_1=?, image_2=?, image_3=?, is_featured=?
                 WHERE id=?'
            );
            $st->execute([
                $row['category_id'], $row['name'], $row['short_desc'], $row['description'],
                $row['price'], $row['stock'], $row['sizes'],
                $row['image_1'], $row['image_2'], $row['image_3'], $row['is_featured'], $id
            ]);
            log_activity('product_update', 'Product #' . $id);
            flash_set('Product updated.', 'success');
        } else {
            $st = $pdo->prepare(
                'INSERT INTO products
                  (category_id, name, short_desc, description, price, stock, sizes,
                   image_1, image_2, image_3, is_featured)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $row['category_id'], $row['name'], $row['short_desc'], $row['description'],
                $row['price'], $row['stock'], $row['sizes'],
                $row['image_1'], $row['image_2'], $row['image_3'], $row['is_featured']
            ]);
            log_activity('product_create', $row['name']);
            flash_set('Product created.', 'success');
        }
        redirect('admin/products.php');
    }
}

$pageTitle = $isEdit ? 'Edit product' : 'New product';
require_once __DIR__ . '/includes/admin_header.php';
?>

<?php foreach ($errors as $err): ?>
    <div class="flash flash-error"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="form-card wide" style="margin:0">
    <?= csrf_field() ?>

    <div class="row-2">
        <div class="field">
            <label>Name</label>
            <input name="name" value="<?= e($row['name']) ?>" required>
        </div>
        <div class="field">
            <label>Category</label>
            <select name="category_id" required>
                <option value="">— pick one —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $row['category_id']==$c['id']?'selected':'' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="field">
        <label>Short description</label>
        <input name="short_desc" value="<?= e($row['short_desc']) ?>">
    </div>

    <div class="field">
        <label>Full description</label>
        <textarea name="description" rows="5"><?= e($row['description']) ?></textarea>
    </div>

    <div class="row-2">
        <div class="field"><label>Price ($)</label>
            <input type="number" step="0.01" min="0" name="price" value="<?= e((string)$row['price']) ?>" required>
        </div>
        <div class="field"><label>Stock</label>
            <input type="number" min="0" name="stock" value="<?= (int)$row['stock'] ?>" required>
        </div>
    </div>

    <div class="field">
        <label>Sizes (comma-separated, e.g. S,M,L)</label>
        <input name="sizes" value="<?= e($row['sizes']) ?>">
    </div>

    <div class="row-2">
        <div class="field"><label>Image 1
            <small style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted)">
                (current: <?= e($row['image_1'] ?: '—') ?>)
            </small></label>
            <input type="file" name="image_1" accept="image/*">
        </div>
        <div class="field"><label>Image 2</label><input type="file" name="image_2" accept="image/*"></div>
    </div>
    <div class="row-2">
        <div class="field"><label>Image 3</label><input type="file" name="image_3" accept="image/*"></div>
        <div class="field" style="justify-content:center"><label>
            <input type="checkbox" name="is_featured" <?= $row['is_featured']?'checked':'' ?>>
            Featured on homepage
        </label></div>
    </div>

    <div style="display:flex;gap:.8rem">
        <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
        <a class="btn btn-outline" href="<?= url('admin/products.php') ?>">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
