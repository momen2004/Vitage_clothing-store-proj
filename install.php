<?php
/**
 * One-time installer: creates the database & tables, seeds users.
 * Visit http://localhost/Vitage_clothing-store-proj/install.php once.
 * DELETE this file after install on a real deployment.
 */

require_once __DIR__ . '/config/db.php'; // also tests connection

$log = [];
$ok  = true;

try {
    // Drop & rebuild schema by executing schema.sql statement-by-statement
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    if ($sql === false) throw new RuntimeException('schema.sql not found');

    // We connected to a specific DB in db.php; the schema file recreates it.
    $rootPdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $rootPdo->exec($sql);
    $log[] = '✔ Schema created and seeded with categories + products.';

    // Re-connect to the new DB to seed users
    $pdo2 = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo2->prepare(
        'INSERT INTO users (full_name, email, password_hash, role) VALUES (?,?,?,?)'
    );
    $stmt->execute(['Site Admin', 'admin@vitage.local',
        password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
    $stmt->execute(['Jane Doe',   'jane@vitage.local',
        password_hash('user123',  PASSWORD_BCRYPT), 'user']);
    $log[] = '✔ Seeded admin (admin@vitage.local / admin123) and user (jane@vitage.local / user123).';

} catch (Throwable $e) {
    $ok = false;
    $log[] = '✘ ' . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><title>Install — Vitage</title>
<link rel="stylesheet" href="assets/css/style.css">
</head><body>
<div class="container">
<div class="form-card wide" style="margin-top:3rem">
    <h2>Vitage Installer</h2>
    <?php foreach ($log as $line): ?>
        <p style="font-family:'Special Elite',monospace"><?= htmlspecialchars($line) ?></p>
    <?php endforeach; ?>

    <?php if ($ok): ?>
        <p>All set. Now <a href="index.php">visit the store</a> or
           <a href="login.php">log in as admin</a>.</p>
        <p style="color:#8a3b1f"><strong>Important:</strong> delete <code>install.php</code> before deploying.</p>
    <?php else: ?>
        <p>Installation failed. Check <code>config/db.php</code> credentials and that MySQL is running in XAMPP.</p>
    <?php endif; ?>
</div></div>
</body></html>
