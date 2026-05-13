<?php
/**
 * Database connection (PDO + MySQL) for the Vitage store.
 * Adjust credentials to match your XAMPP setup.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'vitage_store');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'Vitage');
define('SITE_TAGLINE', 'Authentic Vintage. Modern Soul.');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Run /install.php first or check config/db.php. Error: ' . htmlspecialchars($e->getMessage()));
}
