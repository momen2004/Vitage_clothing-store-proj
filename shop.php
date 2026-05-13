<?php
require_once __DIR__ . '/includes/functions.php';

$q     = trim($_GET['q']   ?? '');
$cat   = trim($_GET['cat'] ?? '');
$sort  = $_GET['sort'] ?? 'newest';
$min   = (float)($_GET['min'] ?? 0);
$max   = (float)($_GET['max'] ?? 0);
$page  = max(1, (int)($_GET['page'] ?? 1));
$per   = 12;
$off   = ($page - 1) * $per;

$where = ['1=1'];
$args  = [];

if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.short_desc LIKE ? OR p.description LIKE ?)';
    $like = '%' . $q . '%';
    array_push($args, $like, $like, $like);
}
if ($cat !== '') {
    $where[] = 'c.slug = ?';
    $args[]  = $cat;
}
if ($min > 0) { $where[] = 'p.price >= ?'; $args[] = $min; }
if ($max > 0) { $where[] = 'p.price <= ?'; $args[] = $max; }

$order = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    default      => 'p.created_at DESC',
};

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE $whereSql"
);
$countStmt->execute($args);
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT p.*, c.slug AS cat_slug, c.name AS cat_name
       FROM products p
       JOIN categories c ON c.id = p.category_id
      WHERE $whereSql
      ORDER BY $order
      LIMIT $per OFFSET $off"
);
$stmt->execute($args);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$pages = max(1, (int)ceil($total / $per));

$pageTitle = 'Shop — Vitage';
include __DIR__ . '/includes/header.php';

function qstr(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
}
?>

<div class="container">
    <div class="section-title"><h2>
        <?= $cat ? 'Category: ' . e(ucfirst($cat)) : ($q ? 'Search: ' . e($q) : 'All vintage finds') ?>
    </h2></div>

    <div class="cat-pills">
        <a class="cat-pill <?= $cat === '' ? 'active' : '' ?>" href="<?= url('shop.php') ?>">All</a>
        <?php foreach ($categories as $c): ?>
            <a class="cat-pill <?= $cat === $c['slug'] ? 'active' : '' ?>"
               href="<?= url('shop.php?cat=' . e($c['slug'])) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <form class="filter-bar" method="get">
        <?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
        <?php if ($q):   ?><input type="hidden" name="q"   value="<?= e($q)   ?>"><?php endif; ?>
        <label>Sort
            <select name="sort" onchange="this.form.submit()">
                <option value="newest"     <?= $sort==='newest'?'selected':'' ?>>Newest</option>
                <option value="price_asc"  <?= $sort==='price_asc'?'selected':'' ?>>Price: low → high</option>
                <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: high → low</option>
                <option value="name"       <?= $sort==='name'?'selected':'' ?>>Name (A–Z)</option>
            </select>
        </label>
        <label>Min $ <input type="number" name="min" min="0" step="1" value="<?= e((string)($_GET['min'] ?? '')) ?>" style="width:80px"></label>
        <label>Max $ <input type="number" name="max" min="0" step="1" value="<?= e((string)($_GET['max'] ?? '')) ?>" style="width:80px"></label>
        <button class="btn btn-sm" type="submit">Apply</button>
        <span style="margin-left:auto;color:var(--muted)"><?= (int)$total ?> piece(s) found</span>
    </form>

    <?php if (!$products): ?>
        <div class="empty">
            <h3 style="font-style:italic">Nothing in the archive matches.</h3>
            <p>Try clearing your filters or browsing all categories.</p>
            <a class="btn" href="<?= url('shop.php') ?>">See everything</a>
        </div>
    <?php else: ?>
        <div class="products">
            <?php foreach ($products as $p): include __DIR__ . '/includes/_product_card.php'; endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= e(qstr(['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
