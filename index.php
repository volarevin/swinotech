<?php
require __DIR__ . '/includes/auth.php';
if (empty($_SESSION['user_id'])) {
    redirect('login.php');
}
redirect('dashboard.php');
