<?php
require_once __DIR__ . '/includes/functions.php';

$categories = $pdo->query(
    'SELECT c.*, COUNT(p.id) AS n
       FROM categories c
  LEFT JOIN products p ON p.category_id = c.id
   GROUP BY c.id
   ORDER BY c.name'
)->fetchAll();

$featured = $pdo->query(
    "SELECT p.*, c.slug AS cat_slug
       FROM products p
       JOIN categories c ON c.id = p.category_id
      WHERE p.is_featured = 1
      ORDER BY p.created_at DESC
      LIMIT 8"
)->fetchAll();

$newest = $pdo->query(
    "SELECT p.*, c.slug AS cat_slug
       FROM products p
       JOIN categories c ON c.id = p.category_id
      ORDER BY p.created_at DESC
      LIMIT 4"
)->fetchAll();

$pageTitle = SITE_NAME . ' — ' . SITE_TAGLINE;
include __DIR__ . '/includes/header.php';
?>

<div class="container">

    <section class="hero fade-up">
        <span class="hero-tag">Est. forever</span>
        <h1>Stories worn on the sleeve.</h1>
        <p>Carefully curated vintage clothing from the 50s through the 90s.
           Each piece is restored, photographed, and shipped from our small workshop.</p>
        <a class="btn" href="<?= url('shop.php') ?>">Browse the collection →</a>
    </section>

    <section>
        <div class="section-title"><h2>Shop by category</h2></div>
        <div class="cat-pills">
            <a class="cat-pill" href="<?= url('shop.php') ?>">All</a>
            <?php foreach ($categories as $c): ?>
                <a class="cat-pill" href="<?= url('shop.php?cat=' . e($c['slug'])) ?>">
                    <?= e($c['name']) ?> <span style="opacity:.6">(<?= (int)$c['n'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section>
        <div class="section-title"><h2>Featured pieces</h2></div>
        <div class="products">
            <?php foreach ($featured as $p): ?>
                <?php include __DIR__ . '/includes/_product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section>
        <div class="section-title"><h2>Just arrived</h2></div>
        <div class="products">
            <?php foreach ($newest as $p): ?>
                <?php include __DIR__ . '/includes/_product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
