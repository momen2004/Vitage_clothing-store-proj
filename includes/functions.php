<?php
/**
 * Shared helpers: session bootstrap, auth checks, CSRF, sanitisation,
 * cart helpers, asset URL resolution.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// ---------------------------------------------------------------
// Path helpers — works whether served at / or /Vitage_clothing-store-proj/
// ---------------------------------------------------------------
function base_url(): string {
    static $base = null;
    if ($base !== null) return $base;
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // strip /admin or /api suffix so links from those subdirs resolve to root
    $script = preg_replace('#/(admin|api)(/.*)?$#', '', $script);
    $base = rtrim($script, '/');
    return $base;
}

function url(string $path = ''): string {
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

// ---------------------------------------------------------------
// Output safety
// ---------------------------------------------------------------
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float $n): string {
    return '$' . number_format($n, 2);
}

// ---------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void {
    $token = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        die('CSRF validation failed.');
    }
}

// ---------------------------------------------------------------
// Auth
// ---------------------------------------------------------------
function current_user(): ?array {
    global $pdo;
    static $cached = false;
    static $user = null;
    if ($cached) return $user;
    $cached = true;
    if (empty($_SESSION['user_id'])) return $user = null;
    $stmt = $pdo->prepare('SELECT id, full_name, email, role, address, phone FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function is_logged_in(): bool   { return current_user() !== null; }
function is_admin(): bool       { $u = current_user(); return $u && $u['role'] === 'admin'; }

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash'] = 'Please log in to continue.';
        redirect('login.php');
    }
}

function require_admin(): void {
    if (!is_admin()) {
        http_response_code(403);
        die('Admins only.');
    }
}

// ---------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------
function flash_set(string $msg, string $type = 'info'): void {
    $_SESSION['flash'] = $msg;
    $_SESSION['flash_type'] = $type;
}

function flash_pop(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = ['msg' => $_SESSION['flash'], 'type' => $_SESSION['flash_type'] ?? 'info'];
    unset($_SESSION['flash'], $_SESSION['flash_type']);
    return $f;
}

// ---------------------------------------------------------------
// Activity logging
// ---------------------------------------------------------------
function log_activity(string $action, ?string $detail = null): void {
    global $pdo;
    try {
        $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, action, detail) VALUES (?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'] ?? null, $action, $detail]);
    } catch (PDOException $e) { /* non-critical */ }
}

// ---------------------------------------------------------------
// Cart helpers
// ---------------------------------------------------------------
function get_or_create_cart_id(int $userId): int {
    global $pdo;
    $stmt = $pdo->prepare('SELECT id FROM cart WHERE user_id = ?');
    $stmt->execute([$userId]);
    $cartId = $stmt->fetchColumn();
    if ($cartId) return (int)$cartId;
    $pdo->prepare('INSERT INTO cart (user_id) VALUES (?)')->execute([$userId]);
    return (int)$pdo->lastInsertId();
}

function cart_count(): int {
    global $pdo;
    if (!is_logged_in()) return 0;
    $u = current_user();
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(ci.quantity), 0)
         FROM cart c
         LEFT JOIN cart_items ci ON ci.cart_id = c.id
         WHERE c.user_id = ?'
    );
    $stmt->execute([$u['id']]);
    return (int)$stmt->fetchColumn();
}

function cart_items_for_current_user(): array {
    global $pdo;
    if (!is_logged_in()) return [];
    $u = current_user();
    $stmt = $pdo->prepare(
        'SELECT ci.id, ci.product_id, ci.quantity, ci.size,
                p.name, p.price, p.stock, p.image_1
         FROM cart_items ci
         JOIN cart c     ON c.id = ci.cart_id
         JOIN products p ON p.id = ci.product_id
         WHERE c.user_id = ?
         ORDER BY ci.id DESC'
    );
    $stmt->execute([$u['id']]);
    return $stmt->fetchAll();
}

function cart_subtotal(array $items): float {
    $sum = 0.0;
    foreach ($items as $it) {
        $sum += (float)$it['price'] * (int)$it['quantity'];
    }
    return round($sum, 2);
}

// ---------------------------------------------------------------
// Misc
// ---------------------------------------------------------------
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text);
    $text = strtolower(trim($text, '-'));
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    return $text ?: 'item';
}
