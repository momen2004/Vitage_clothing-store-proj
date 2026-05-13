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

$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['quantity'] ?? 1));
$size      = trim($_POST['size'] ?? '') ?: null;

$stmt = $pdo->prepare('SELECT id, stock, name FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['ok' => false, 'error' => 'Product not found']);
    exit;
}
if ((int)$product['stock'] < $qty) {
    echo json_encode(['ok' => false, 'error' => 'Not enough stock available.']);
    exit;
}

$uid    = current_user()['id'];
$cartId = get_or_create_cart_id($uid);

// Insert or merge quantity for same product+size
$stmt = $pdo->prepare(
    'SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND
       ((size IS NULL AND ? IS NULL) OR size = ?)'
);
$stmt->execute([$cartId, $productId, $size, $size]);
$existing = $stmt->fetch();

if ($existing) {
    $newQty = (int)$existing['quantity'] + $qty;
    if ($newQty > (int)$product['stock']) $newQty = (int)$product['stock'];
    $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')
        ->execute([$newQty, $existing['id']]);
} else {
    $pdo->prepare(
        'INSERT INTO cart_items (cart_id, product_id, quantity, size) VALUES (?,?,?,?)'
    )->execute([$cartId, $productId, $qty, $size]);
}

log_activity('cart_add', $product['name'] . ' (x' . $qty . ')');

echo json_encode(['ok' => true, 'cart_count' => cart_count()]);
