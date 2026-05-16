<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('index.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim(strtolower($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, password_hash, role, full_name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        log_activity('login', $email);
        flash_set('Welcome back, ' . explode(' ', $user['full_name'])[0] . '.', 'success');
        redirect($user['role'] === 'admin' ? 'admin/index.php' : 'index.php');
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Sign in — Vitage';
include __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="form-card">
        <h2>Welcome back</h2>
        <p style="color:var(--muted);font-family:var(--type);font-size:.85rem">
            New here? <a href="<?= url('register.php') ?>">Create an account.</a>
        </p>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label>Email</label>
                <input name="email" type="email" value="<?= e($email) ?>" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input name="password" type="password" required>
            </div>
            <button class="btn" type="submit">Sign in</button>
            <a href="<?= url('forgot_password.php') ?>"
               style="margin-left:1rem;font-family:var(--type);font-size:.85rem">Forgot password?</a>
        </form>

        <p style="margin-top:2rem;font-family:var(--type);font-size:.78rem;color:var(--muted)">
            Demo accounts (after install): admin@vitage.local / admin123 — jane@vitage.local / user123
        </p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
