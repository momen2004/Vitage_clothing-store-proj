<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('index.php');

$errors = [];
$full = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $full     = trim($_POST['full_name'] ?? '');
    $email    = trim(strtolower($_POST['email'] ?? ''));
    $pass     = $_POST['password']         ?? '';
    $pass2    = $_POST['password_confirm'] ?? '';

    if (strlen($full)  < 2)   $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if (strlen($pass)  < 6)   $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $pass2)     $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $errors[] = 'That email is already registered.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role) VALUES (?,?,?,?)'
            );
            $stmt->execute([$full, $email, password_hash($pass, PASSWORD_BCRYPT), 'user']);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            log_activity('register', 'New user: ' . $email);
            flash_set('Welcome to Vitage, ' . $full . '!', 'success');
            redirect('index.php');
        }
    }
}

$pageTitle = 'Create account — Vitage';
include __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="form-card">
        <h2>Join the archive</h2>
        <p style="color:var(--muted);font-family:var(--type);font-size:.85rem">
            Already have an account? <a href="<?= url('login.php') ?>">Sign in.</a>
        </p>

        <?php foreach ($errors as $e): ?>
            <div class="flash flash-error"><?= e($e) ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label>Full name</label>
                <input name="full_name" value="<?= e($full) ?>" required>
            </div>
            <div class="field">
                <label>Email</label>
                <input name="email" type="email" value="<?= e($email) ?>" required>
            </div>
            <div class="row-2">
                <div class="field">
                    <label>Password</label>
                    <input name="password" type="password" required minlength="6">
                </div>
                <div class="field">
                    <label>Confirm password</label>
                    <input name="password_confirm" type="password" required minlength="6">
                </div>
            </div>
            <button class="btn" type="submit">Create account</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
