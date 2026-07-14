<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

// "Clear all" is the only server-side action left; quantity edits & removals
// now happen via /api/update_cart.php in real time. //UPdates 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear') {
    csrf_verify();
    $pdo->prepare(
        'DELETE ci FROM cart_items ci JOIN cart c ON c.id = ci.cart_id WHERE c.user_id = ?'
    )->execute([current_user()['id']]);
    flash_set('Cart cleared.', 'success');
    redirect('cart.php');
}

$items    = cart_items_for_current_user();
$subtotal = cart_subtotal($items);
$shipping = ($subtotal > 0 && $subtotal < 80) ? 8.00 : 0.00;
$total    = $subtotal + $shipping;

$pageTitle = 'Your cart — Vitage';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="section-title"><h2>Your cart</h2></div>

    <?php if (!$items): ?>
        <div class="empty">
            <p>Your cart is empty.</p>
            <a class="btn" href="<?= url('shop.php') ?>">Find something timeless</a>
        </div>
    <?php else: ?>
        <table class="cart-table"
               id="cartTable"
               data-update-url="<?= e(url('api/update_cart.php')) ?>"
               data-csrf="<?= e(csrf_token()) ?>">
            <thead>
                <tr>
                    <th></th><th>Item</th><th>Size</th><th>Price</th>
                    <th>Qty</th><th>Subtotal</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr data-item-id="<?= (int)$it['id'] ?>"
                    data-price="<?= e(number_format((float)$it['price'], 2, '.', '')) ?>">
                    <td><img src="<?= url(e($it['image_1'] ?: 'assets/images/p1.svg')) ?>" alt=""></td>
                    <td><a href="<?= url('product.php?id=' . (int)$it['product_id']) ?>"><?= e($it['name']) ?></a></td>
                    <td><?= e($it['size'] ?: '—') ?></td>
                    <td><?= money((float)$it['price']) ?></td>
                    <td>
                        <input type="number" class="qty-input cart-qty"
                               value="<?= (int)$it['quantity'] ?>"
                               min="0" max="<?= max(1,(int)$it['stock']) ?>">
                    </td>
                    <td class="row-subtotal">$<?= number_format((float)$it['price'] * (int)$it['quantity'], 2) ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger cart-remove">Remove</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="display:flex;justify-content:space-between;margin-top:1rem;flex-wrap:wrap;gap:1rem">
            <form method="post" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-outline" type="submit"
                        onclick="return confirm('Clear all items?')">Clear cart</button>
                <span id="cartSaveStatus"
                      style="margin-left:1rem;font-family:var(--type);font-size:.8rem;color:var(--muted)"></span>
            </form>
            <div class="cart-totals" style="margin:0">
                <div class="box">
                    <div class="row"><span>Subtotal</span><span id="cartSubtotal">$<?= number_format($subtotal, 2) ?></span></div>
                    <div class="row"><span>Shipping</span><span id="cartShipping"><?= $shipping ? '$' . number_format($shipping, 2) : 'Free' ?></span></div>
                    <div class="row total"><span>Total</span><span id="cartTotal">$<?= number_format($total, 2) ?></span></div>
                    <a href="<?= url('checkout.php') ?>" class="btn" style="display:block;text-align:center;margin-top:1rem">Checkout →</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
