<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'Please log in first.', 'login_required' => true]);
    exit;
}

csrf_verify();

$itemId = (int)($_POST['item_id'] ?? 0);
$qty    = max(0, (int)($_POST['quantity'] ?? 0));
$userId = current_user()['id'];

// Look up the cart_item, its product, and ensure it belongs to the current user.
$stmt = $pdo->prepare(
    'SELECT ci.id, ci.product_id, p.price, p.stock, p.name
       FROM cart_items ci
       JOIN cart c     ON c.id  = ci.cart_id
       JOIN products p ON p.id  = ci.product_id
      WHERE ci.id = ? AND c.user_id = ?'
);
$stmt->execute([$itemId, $userId]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'Item not found in your cart.']);
    exit;
}

if ($qty === 0) {
    $pdo->prepare('DELETE FROM cart_items WHERE id = ?')->execute([$itemId]);
    log_activity('cart_remove', $row['name']);
    $removed   = true;
    $newQty    = 0;
    $lineTotal = 0.0;
} else {
    if ($qty > (int)$row['stock']) $qty = (int)$row['stock'];
    $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')->execute([$qty, $itemId]);
    $removed   = false;
    $newQty    = $qty;
    $lineTotal = round((float)$row['price'] * $qty, 2);
}

// Re-compute cart totals for the response so the client stays in sync.
$items    = cart_items_for_current_user();
$subtotal = cart_subtotal($items);
$shipping = ($subtotal > 0 && $subtotal < 80) ? 8.00 : 0.00;
$total    = $subtotal + $shipping;

echo json_encode([
    'ok'         => true,
    'removed'    => $removed,
    'quantity'   => $newQty,
    'line_total' => $lineTotal,
    'subtotal'   => $subtotal,
    'shipping'   => $shipping,
    'total'      => $total,
    'cart_count' => array_sum(array_column($items, 'quantity')),
]);
