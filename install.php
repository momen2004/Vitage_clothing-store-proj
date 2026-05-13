<?php
/**
 * One-time installer: creates the database & tables, seeds users.
 * Visit http://localhost/Vitage_clothing-store-proj/install.php once.
 * DELETE this file after install on a real deployment.
 *
 * NOTE: we deliberately do NOT include config/db.php here, because that file
 * connects to the target database — which won't exist on a fresh install.
 * We only pull the constants in and open our own server-level connection.
 */

// Pull DB_HOST / DB_NAME / DB_USER / DB_PASS without triggering the connection.
$dbConfig = file_get_contents(__DIR__ . '/config/db.php');
foreach (['DB_HOST','DB_NAME','DB_USER','DB_PASS'] as $const) {
    if (preg_match("/define\\(\\s*'$const'\\s*,\\s*'([^']*)'\\s*\\)/", $dbConfig, $m)) {
        if (!defined($const)) define($const, $m[1]);
    }
}

$log = [];
$ok  = true;

try {
    // Read schema.sql, but override its hard-coded database name to match config/db.php
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    if ($sql === false) throw new RuntimeException('schema.sql not found');

    $sql = preg_replace(
        '/(DROP\s+DATABASE\s+IF\s+EXISTS\s+)\w+/i',     '$1`' . DB_NAME . '`', $sql);
    $sql = preg_replace(
        '/(CREATE\s+DATABASE\s+)\w+/i',                 '$1`' . DB_NAME . '`', $sql);
    $sql = preg_replace(
        '/(USE\s+)\w+\s*;/i',                           '$1`' . DB_NAME . '`;', $sql);

    // Open a server-level connection (no dbname) so we can CREATE DATABASE.
    $rootPdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $rootPdo->exec($sql);
    $log[] = '✔ Database `' . DB_NAME . '` created and seeded with categories + products.';

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
