<?php
require_once __DIR__ . '/includes/functions.php';
log_activity('logout');
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
session_start();
flash_set('You have been logged out.', 'success');
redirect('index.php');
