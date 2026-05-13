<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
       FROM products p
       JOIN categories c ON c.id = p.category_id
      WHERE p.id = ?'
);
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    $pageTitle = 'Not found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container empty"><h2>Piece not found</h2><p>This vintage piece has wandered off.</p><a class="btn" href="' . url('shop.php') . '">Back to shop</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pdo->prepare('UPDATE products SET views = views + 1 WHERE id = ?')->execute([$id]);

$related = $pdo->prepare(
    'SELECT p.* FROM products p
      WHERE p.category_id = ? AND p.id <> ?
      ORDER BY RAND() LIMIT 4'
);
$related->execute([$p['category_id'], $id]);
$related = $related->fetchAll();

$sizes = array_filter(array_map('trim', explode(',', $p['sizes'] ?? '')));
$images = array_filter([$p['image_1'], $p['image_2'], $p['image_3']]);
if (!$images) $images = ['assets/images/p1.svg'];

$pageTitle = $p['name'] . ' — Vitage';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <p style="font-family:var(--type);color:var(--muted);margin-top:1rem">
        <a href="<?= url('shop.php') ?>">Shop</a> &rsaquo;
        <a href="<?= url('shop.php?cat=' . e($p['cat_slug'])) ?>"><?= e($p['cat_name']) ?></a> &rsaquo;
        <?= e($p['name']) ?>
    </p>

    <div class="product-detail">
        <div class="gallery">
            <div class="main-img"><img src="<?= url(e($images[0])) ?>" alt="<?= e($p['name']) ?>"></div>
            <?php if (count($images) > 1): ?>
                <div class="thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <img src="<?= url(e($img)) ?>" data-full="<?= url(e($img)) ?>" class="<?= $i===0?'active':'' ?>" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <small style="font-family:var(--type);letter-spacing:2px;color:var(--muted);text-transform:uppercase">
                <?= e($p['cat_name']) ?>
            </small>
            <h1><?= e($p['name']) ?></h1>
            <p class="price-big"><?= money((float)$p['price']) ?></p>
            <p><?= nl2br(e($p['description'] ?? $p['short_desc'])) ?></p>

            <form class="ajax-add-cart" method="post" action="<?= url('api/add_to_cart.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                <?php if ($sizes): ?>
                    <strong style="font-family:var(--type);font-size:.85rem;letter-spacing:1px;text-transform:uppercase">Size</strong>
                    <div class="size-picker">
                        <?php foreach ($sizes as $i => $sz): ?>
                            <label>
                                <input type="radio" name="size" value="<?= e($sz) ?>" <?= $i===0?'checked':'' ?>>
                                <span><?= e($sz) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex;gap:1rem;align-items:flex-end;margin:1rem 0">
                    <div class="field" style="margin:0">
                        <label>Quantity</label>
                        <input class="qty-input" type="number" name="quantity" value="1" min="1" max="<?= max(1,(int)$p['stock']) ?>">
                    </div>
                    <?php if ((int)$p['stock'] > 0): ?>
                        <button class="btn" type="submit">Add to cart</button>
                    <?php else: ?>
                        <button class="btn" disabled style="opacity:.6">Sold out</button>
                    <?php endif; ?>
                </div>
            </form>

            <ul class="meta-list">
                <li><span>Category</span><span><?= e($p['cat_name']) ?></span></li>
                <li><span>Stock</span><span><?= (int)$p['stock'] > 0 ? (int)$p['stock'] . ' in store' : 'Sold out' ?></span></li>
                <li><span>Sizes</span><span><?= e($p['sizes'] ?: 'One size') ?></span></li>
                <li><span>Item #</span><span>VTG-<?= str_pad((string)$p['id'], 4, '0', STR_PAD_LEFT) ?></span></li>
            </ul>
        </div>
    </div>

    <?php if ($related): ?>
        <div class="section-title"><h2>You might also love</h2></div>
        <div class="products">
            <?php foreach ($related as $rp): $p = $rp; include __DIR__ . '/includes/_product_card.php'; endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
