<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$user  = current_user();
$items = cart_items_for_current_user();

if (!$items) { redirect('cart.php'); }

$subtotal = cart_subtotal($items);
$shipping = $subtotal < 80 ? 8.00 : 0.00;
$total    = $subtotal + $shipping;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $addr  = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone']   ?? '');
    $notes = trim($_POST['notes']   ?? '');
    if (strlen($addr) < 5)  $errors[] = 'Please enter a valid shipping address.';
    if (strlen($phone) < 5) $errors[] = 'Please enter a valid phone number.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            // Re-check stock at order time
            foreach ($items as $it) {
                $st = $pdo->prepare('SELECT stock FROM products WHERE id = ? FOR UPDATE');
                $st->execute([$it['product_id']]);
                $stock = (int)$st->fetchColumn();
                if ($stock < (int)$it['quantity']) {
                    throw new RuntimeException('Not enough stock for ' . $it['name'] . '.');
                }
            }

            $pdo->prepare(
                'INSERT INTO orders (user_id, total, status, shipping_addr, phone, notes)
                 VALUES (?,?,?,?,?,?)'
            )->execute([$user['id'], $total, 'paid', $addr, $phone, $notes]);
            $orderId = (int)$pdo->lastInsertId();

            $insItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, name, price, quantity, size)
                 VALUES (?,?,?,?,?,?)'
            );
            $decStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

            foreach ($items as $it) {
                $insItem->execute([$orderId, $it['product_id'], $it['name'],
                                   $it['price'], $it['quantity'], $it['size']]);
                $decStock->execute([(int)$it['quantity'], (int)$it['product_id']]);
            }

            // Save address & phone to user profile if not already set
            if (empty($user['address']) || empty($user['phone'])) {
                $pdo->prepare('UPDATE users SET address = ?, phone = ? WHERE id = ?')
                    ->execute([$addr, $phone, $user['id']]);
            }

            // Empty the cart
            $pdo->prepare(
                'DELETE ci FROM cart_items ci JOIN cart c ON c.id = ci.cart_id WHERE c.user_id = ?'
            )->execute([$user['id']]);

            $pdo->commit();
            log_activity('order_placed', 'Order #' . $orderId . ' total $' . $total);

            flash_set('Order placed — thank you! Order #' . $orderId, 'success');
            redirect('account.php?placed=' . $orderId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Checkout — Vitage';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="section-title"><h2>Checkout</h2></div>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:2rem" class="checkout-grid">
        <form method="post" class="form-card wide" style="margin:0">
            <h3>Shipping details</h3>
            <div class="field">
                <label>Full name</label>
                <input value="<?= e($user['full_name']) ?>" disabled>
            </div>
            <?= csrf_field() ?>
            <div class="field">
                <label>Address</label>
                <textarea name="address" required><?= e($user['address'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label>Phone</label>
                <input name="phone" value="<?= e($user['phone'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label>Order notes (optional)</label>
                <textarea name="notes" placeholder="Gift wrap, delivery preferences…"></textarea>
            </div>

            <p style="font-family:var(--type);color:var(--muted);font-size:.85rem">
                This is a demo store — no real payment is processed.
                Pressing "Place order" will mark the order as paid.
            </p>

            <button class="btn" type="submit">Place order →</button>
        </form>

        <aside class="form-card" style="margin:0;align-self:start">
            <h3 style="margin-top:0">Your order</h3>
            <table style="width:100%;font-family:var(--type);font-size:.9rem;border-collapse:collapse">
                <?php foreach ($items as $it): ?>
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:.5rem 0"><?= e($it['name']) ?>
                            <small style="color:var(--muted)">×<?= (int)$it['quantity'] ?>
                                <?= $it['size'] ? '· ' . e($it['size']) : '' ?></small>
                        </td>
                        <td style="text-align:right"><?= money((float)$it['price'] * (int)$it['quantity']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div class="row" style="display:flex;justify-content:space-between;padding-top:.8rem"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
            <div class="row" style="display:flex;justify-content:space-between"><span>Shipping</span><span><?= $shipping ? money($shipping) : 'Free' ?></span></div>
            <div class="row" style="display:flex;justify-content:space-between;border-top:1px solid var(--border);margin-top:.5rem;padding-top:.5rem;font-weight:700"><span>Total</span><span><?= money($total) ?></span></div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
