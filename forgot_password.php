<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('index.php');

ensure_password_reset_table();

$email      = '';
$message    = '';
$resetUrl   = '';   // shown on screen in demo mode

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email.';
    } else {
        $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Invalidate any previous unused tokens for this user.
            $pdo->prepare(
                'UPDATE password_resets SET used_at = NOW()
                  WHERE user_id = ? AND used_at IS NULL'
            )->execute([$user['id']]);

            $token = bin2hex(random_bytes(32));
            $pdo->prepare(
                'INSERT INTO password_resets (user_id, token, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
            )->execute([$user['id'], $token]);

            log_activity('password_reset_request', $email);

            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $resetUrl = $scheme . '://' . $_SERVER['HTTP_HOST']
                      . url('reset_password.php?token=' . $token);

            // In production you would email this link instead of showing it.
            // Example (works if XAMPP's sendmail is configured):
            // @mail($email, 'Reset your Vitage password',
            //       "Hi " . $user['full_name'] . ",\n\n"
            //     . "Click this link within the next hour to reset your password:\n"
            //     . $resetUrl . "\n\nIf you didn't request this, ignore this email.");
        }

        // Always show the same generic message to avoid leaking which emails exist.
        $message = 'If that email is registered, a password-reset link has been issued. '
                 . 'It is valid for 1 hour.';
    }
}

$pageTitle = 'Reset your password — Vitage';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-card">
        <h2>Forgot your password?</h2>
        <p style="color:var(--muted);font-family:var(--type);font-size:.85rem">
            Enter the email you registered with — we'll issue a one-hour reset link.
        </p>

        <?php if ($message): ?>
            <div class="flash flash-<?= $resetUrl ? 'success' : 'info' ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($resetUrl): ?>
            <div class="flash flash-success"
                 style="word-break:break-all;font-family:var(--type);font-size:.78rem">
                <strong>Demo mode</strong> — XAMPP has no real mail server,
                so here is the link that would normally arrive in your inbox:
                <br><br>
                <a href="<?= e($resetUrl) ?>"><?= e($resetUrl) ?></a>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label>Email</label>
                <input name="email" type="email" value="<?= e($email) ?>" required autocomplete="email">
            </div>
            <button class="btn" type="submit">Send reset link</button>
            <a class="btn btn-outline" href="<?= url('login.php') ?>">Back to login</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
