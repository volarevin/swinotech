<?php
require __DIR__ . '/includes/auth.php';
require_login();
redirect('dashboard.php');
