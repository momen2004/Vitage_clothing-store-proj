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
    // Field not present at all in the request (form didn't post multipart).
    if (!isset($_FILES[$field])) return $current;

    $err = (int)$_FILES[$field]['error'];

    // No file chosen — keep whatever's already on the row.
    if ($err === UPLOAD_ERR_NO_FILE) return $current;

    // PHP rejected the upload before our code ran. Translate to a useful message.
    if ($err !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'File is larger than upload_max_filesize in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'File is larger than MAX_FILE_SIZE in the form.',
            UPLOAD_ERR_PARTIAL    => 'The upload was interrupted — try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server has no tmp directory configured for uploads.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
        ];
        throw new RuntimeException(
            'Image upload failed (' . $field . '): ' . ($messages[$err] ?? 'Unknown error #' . $err)
        );
    }

    // Verify extension AND that the file is actually an image.
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',  'webp' => 'image/webp', 'gif' => 'image/gif'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        throw new RuntimeException('Unsupported image format on ' . $field . ' (use JPG, PNG, WEBP or GIF).');
    }
    $info = @getimagesize($_FILES[$field]['tmp_name']);
    if ($info === false) {
        throw new RuntimeException('Uploaded ' . $field . ' is not a valid image.');
    }

    // Make sure the destination directory exists and is writable.
    $dir = __DIR__ . '/../uploads/products';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        throw new RuntimeException('Could not create uploads/products/ — check folder permissions.');
    }
    if (!is_writable($dir)) {
        throw new RuntimeException(
            'uploads/products/ is not writable by the web server. ' .
            'On Linux run: chmod -R 775 uploads/  (and chown to the Apache user if needed).'
        );
    }

    $fname = 'p_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest  = $dir . '/' . $fname;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        throw new RuntimeException('move_uploaded_file failed for ' . $field . '.');
    }
    return 'uploads/products/' . $fname;
}

// Detect the case where php.ini's post_max_size was exceeded — every $_POST
// and $_FILES entry is empty even though REQUEST_METHOD is POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)
    && ($cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0)) > 0) {
    $errors[] = 'The upload was too large (' . round($cl / 1048576, 1) .
        ' MB) — raise post_max_size and upload_max_filesize in php.ini.';
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
