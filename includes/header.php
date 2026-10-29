<?php
require_once __DIR__ . '/auth.php';
require_login();
$pageTitle = $pageTitle ?? 'SwinoTech';
$flashMessage = take_flash();
$role = $_SESSION['role'] ?? 'staff';
$nav = [
    ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'layout-dashboard', 'section' => 'dashboard'],
    ['label' => 'Pig Inventory', 'href' => 'pigs/index.php', 'icon' => 'piggy-bank', 'section' => 'pigs'],
    ['label' => 'Sow Tracker', 'href' => 'sows/index.php', 'icon' => 'heart-pulse', 'section' => 'sows'],
    ['label' => 'Sales', 'href' => 'sales/index.php', 'icon' => 'receipt-text', 'section' => 'sales'],
    ['label' => 'Monitoring', 'href' => 'monitoring/index.php', 'icon' => 'activity', 'section' => 'monitoring'],
    ['label' => 'Reports', 'href' => 'reports/index.php', 'icon' => 'chart-no-axes-combined', 'section' => 'reports'],
];
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/');
$basePath = trim(BASE_URL, '/');
if ($basePath !== '' && str_starts_with($currentPath, $basePath . '/')) {
    $currentPath = substr($currentPath, strlen($basePath) + 1);
}
$currentSection = str_contains($currentPath, '/') ? strstr($currentPath, '/', true) : $currentPath;
$isDashboard = basename($currentPath) === 'dashboard.php';
$headerAlerts = [];
if (isset($pdo)) {
    require_once __DIR__ . '/dashboard-data.php';
    $headerAlerts = dashboard_notifications($pdo, dashboard_profile($pdo));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · SwinoTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <script>(function(){try{var t=localStorage.getItem('swinotech-theme');if(t==='light'||t==='dark')document.documentElement.dataset.theme=t;else if(window.matchMedia('(prefers-color-scheme: dark)').matches)document.documentElement.dataset.theme='dark';}catch(e){}})();</script>
    <script>(function(){try{if(localStorage.getItem('swinotech-sidebar-collapsed')==='true')document.documentElement.classList.add('sidebar-collapsed');}catch(e){}})();</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="sidebar-head">
            <a class="brand" href="<?= BASE_URL ?>/dashboard.php">
                <span class="brand-mark"><img src="<?= BASE_URL ?>/icons/logo.png" alt=""></span>
                <span><strong>Swino<span>Tech</span></strong><small>Piggery Management System</small></span>
            </a>
            <button class="sidebar-toggle icon-button" type="button" aria-label="Collapse sidebar" aria-expanded="true" aria-controls="app-sidebar-nav" data-sidebar-toggle>
                <i data-lucide="panel-left-close" aria-hidden="true"></i>
            </button>
        </div>
        <div class="sidebar-label">Farm modules</div>
        <nav class="sidebar-nav" id="app-sidebar-nav">
            <?php foreach ($nav as $item): ?>
                <?php $active = $item['section'] === 'dashboard' ? $isDashboard : $currentSection === $item['section']; ?>
                <a class="<?= $active ? 'active' : '' ?>" href="<?= BASE_URL . '/' . $item['href'] ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                    <i class="nav-icon" data-lucide="<?= e($item['icon']) ?>" aria-hidden="true"></i><span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ($role === 'admin'): ?>
                <?php $settingsActive = $currentSection === 'settings'; ?>
                <a class="<?= $settingsActive ? 'active' : '' ?>" href="<?= BASE_URL ?>/settings/index.php" <?= $settingsActive ? 'aria-current="page"' : '' ?>><i class="nav-icon" data-lucide="settings" aria-hidden="true"></i><span>Settings</span></a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-user">
            <div class="sidebar-user-identity">
                <div class="avatar"><?= e(strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))) ?></div>
                <div class="text-truncate"><strong><?= e($_SESSION['username'] ?? 'User') ?></strong><small><i data-lucide="shield-check" aria-hidden="true"></i><?= e(ucfirst($role)) ?></small></div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="logout-link"><i data-lucide="log-out" aria-hidden="true"></i><span>Logout</span></a>
        </div>
    </aside>
    <div class="app-main">
        <header class="topbar">
            <button class="mobile-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Open navigation">☰</button>
            <div class="flex-grow-1"><div class="eyebrow">SwinoTech farm operations</div><h1><?= e($pageTitle) ?></h1></div>
            <div class="topbar-date"><?= date('M d, Y') ?></div>
            <div class="alerts-control"><button class="icon-button" type="button" aria-label="Open alerts" aria-expanded="false" data-alert-toggle><i data-lucide="bell" aria-hidden="true"></i><?php if ($headerAlerts): ?><span class="alert-count"><?= count($headerAlerts) ?></span><?php endif; ?></button><div class="alerts-dropdown" data-alert-menu hidden><div class="alerts-dropdown-head"><strong>Alerts</strong><span><?= count($headerAlerts) ?> active</span></div><?php if (!$headerAlerts): ?><p class="empty-state">Everything is under control.</p><?php else: foreach (array_slice($headerAlerts, 0, 5) as $alert): ?><a href="<?= BASE_URL . '/' . e($alert['href']) ?>" class="alert-menu-item"><i data-lucide="<?= e($alert['icon']) ?>" aria-hidden="true"></i><span><strong><?= e($alert['title']) ?></strong><small><?= e($alert['meta']) ?></small></span></a><?php endforeach; endif; ?></div></div>
            <button class="theme-toggle icon-button" type="button" aria-label="Toggle dark mode" aria-pressed="false" data-theme-toggle><i data-lucide="moon" aria-hidden="true"></i><span class="visually-hidden" data-theme-label>Enable dark mode</span></button>
            <a class="icon-button" href="<?= BASE_URL ?>/logout.php" aria-label="Logout"><i data-lucide="log-out" aria-hidden="true"></i></a>
        </header>
        <main class="content">
            <?php if ($flashMessage): ?><div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : 'success' ?>"><?= e($flashMessage['message']) ?></div><?php endif; ?>
