<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('index.php');

ensure_password_reset_table();

$token  = trim($_REQUEST['token'] ?? '');
$errors = [];
$valid  = false;
$userId = null;

if ($token !== '') {
    $stmt = $pdo->prepare(
        'SELECT pr.id, pr.user_id, pr.used_at, pr.expires_at
           FROM password_resets pr
          WHERE pr.token = ?'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        $errors[] = 'This reset link is not recognised.';
    } elseif ($row['used_at'] !== null) {
        $errors[] = 'This reset link has already been used.';
    } elseif (strtotime($row['expires_at']) < time()) {
        $errors[] = 'This reset link has expired — please request a new one.';
    } else {
        $valid  = true;
        $userId = (int)$row['user_id'];
        $resetId = (int)$row['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    csrf_verify();
    $pass  = $_POST['password']         ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if (strlen($pass) < 6)  $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $pass2)   $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($pass, PASSWORD_BCRYPT), $userId]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
                ->execute([$resetId]);
            // Invalidate any other outstanding tokens for this user.
            $pdo->prepare(
                'UPDATE password_resets SET used_at = NOW()
                  WHERE user_id = ? AND used_at IS NULL'
            )->execute([$userId]);
            $pdo->commit();

            log_activity('password_reset_complete', 'User #' . $userId);
            flash_set('Password updated — please sign in with your new password.', 'success');
            redirect('login.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Could not update password: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Choose a new password — Vitage';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-card">
        <h2>Choose a new password</h2>

        <?php foreach ($errors as $err): ?>
            <div class="flash flash-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <?php if (!$valid): ?>
            <p style="font-family:var(--type);font-size:.9rem">
                <a href="<?= url('forgot_password.php') ?>">Request a new reset link →</a>
            </p>
        <?php else: ?>
            <form method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="field">
                    <label>New password</label>
                    <input name="password" type="password" required minlength="6" autocomplete="new-password">
                </div>
                <div class="field">
                    <label>Confirm new password</label>
                    <input name="password_confirm" type="password" required minlength="6" autocomplete="new-password">
                </div>

                <button class="btn" type="submit">Update password</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
