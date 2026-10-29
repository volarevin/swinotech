<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/audit.php';
audit_log($pdo, 'Logged out');
$_SESSION = [];
session_destroy();
redirect('login.php');
