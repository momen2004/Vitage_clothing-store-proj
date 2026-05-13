<?php /* expects $p (product row) in scope */ ?>
<article class="product-card fade-up">
    <a href="<?= url('product.php?id=' . (int)$p['id']) ?>" class="img-wrap">
        <?php if (!empty($p['is_featured'])): ?>
            <span class="badge">Featured</span>
        <?php elseif ((int)$p['stock'] <= 0): ?>
            <span class="badge" style="background:var(--muted)">Sold out</span>
        <?php endif; ?>
        <img src="<?= url(e($p['image_1'] ?: 'assets/images/p1.svg')) ?>"
             alt="<?= e($p['name']) ?>" loading="lazy">
    </a>
    <div class="body">
        <h3><a href="<?= url('product.php?id=' . (int)$p['id']) ?>" style="color:var(--ink)"><?= e($p['name']) ?></a></h3>
        <p class="short"><?= e($p['short_desc'] ?? '') ?></p>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto">
            <span class="price"><?= money((float)$p['price']) ?></span>
        </div>
        <div class="actions">
            <a class="btn btn-outline btn-sm" href="<?= url('product.php?id=' . (int)$p['id']) ?>">View</a>
            <?php if ((int)$p['stock'] > 0): ?>
                <form class="ajax-add-cart" method="post" action="<?= url('api/add_to_cart.php') ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button class="btn btn-sm" type="submit">Add</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>
