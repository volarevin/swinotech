<?php
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/dashboard-data.php';

$pageTitle = 'Dashboard';
$pageScript = 'dashboard.js';
$role = $_SESSION['role'] ?? 'staff';
$profile = dashboard_profile($pdo);
$stats = dashboard_stats($pdo, $profile);
$notifications = dashboard_notifications($pdo, $profile, $role);
$latest = $pdo->query('SELECT temperature, humidity, recorded_at FROM sensor_logs ORDER BY recorded_at DESC LIMIT 1')->fetch() ?: ['temperature' => '—', 'humidity' => '—', 'recorded_at' => null];
$recentSales = $role === 'admin'
    ? $pdo->query("SELECT sale_id, buyer_name, sale_date, total_amount FROM sales ORDER BY sale_date DESC, sale_id DESC LIMIT 5")->fetchAll()
    : [];
$farrowing = $pdo->query("SELECT p.tag_number, s.expected_farrowing_date, s.status FROM sow_records s JOIN pigs p ON p.pig_id=s.pig_id WHERE s.status='pregnant' ORDER BY s.expected_farrowing_date LIMIT 5")->fetchAll();
$revenueChange = $role === 'admin' && $stats['sales_previous'] > 0 ? (($stats['sales_month'] - $stats['sales_previous']) / $stats['sales_previous']) * 100 : 0;
$localNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$hour = (int) $localNow->format('G');
$greeting = $hour < 5 ? 'Good night' : ($hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'));
$sowLabel = $stats['due_soon'] === 1 ? 'sow' : 'sows';
$hasHeatStress = $latest['temperature'] !== '—' && (float) $latest['temperature'] > (float) $profile['heat_threshold'];
$heatStressMessage = $hasHeatStress ? '1 heat-stress reading recorded ' . ($hour < 12 ? 'this morning' : 'today') : 'No heat-stress readings recorded today';
require __DIR__ . '/includes/header.php';
?>
<section class="welcome-banner card">
    <div class="welcome-copy">
        <p class="eyebrow"><?= e($profile['farm_name']) ?> · <?= $localNow->format('M d, Y') ?></p>
        <h2><?= e($greeting) ?>, <?= e($_SESSION['username'] ?? $profile['owner_name']) ?></h2>
        <p class="welcome-summary"><?= number_format($stats['total_active']) ?> active pigs across <?= e($profile['location']) ?> · <?= $stats['due_soon'] ?> <?= $sowLabel ?> due within <?= (int) $profile['due_soon_days'] ?> days · <?= e($heatStressMessage) ?>.</p>
    </div>
    <?php if ($role === 'admin'): ?><div class="welcome-revenue"><span class="metric-icon"><i data-lucide="trending-up" aria-hidden="true"></i></span><strong><?= $revenueChange >= 0 ? '+' : '' ?><?= number_format($revenueChange, 0) ?>%</strong><small>Revenue vs last month</small></div><?php endif; ?>
    <div class="welcome-actions">
        <a class="btn btn-danger primary-action" href="<?= BASE_URL ?>/pigs/add.php"><i data-lucide="plus" aria-hidden="true"></i> Add pig</a>
        <a class="btn btn-outline-light primary-action" href="<?= BASE_URL ?>/sows/add.php"><i data-lucide="heart-handshake" aria-hidden="true"></i> Record mating</a>
        <?php if ($role === 'admin'): ?><a class="btn btn-outline-light primary-action" href="<?= BASE_URL ?>/sales/add.php"><i data-lucide="receipt-text" aria-hidden="true"></i> Create sale</a><?php endif; ?>
    </div>
</section>

<div class="dashboard-section-heading"><div><h3>Today at a glance</h3><span class="section-note">Herd and climate summary</span></div></div>
<div class="dashboard-stat-grid dashboard-stat-grid-<?= e($role) ?>">
<?php foreach ([['Active pigs','total_active','All active animals','piggy-bank','brand'],['Fatteners','fattener','Market-ready herd','scale','olive'],['Sows','sow','Adult female pigs','venus','clay'],['Boars','boar','Breeding stock','shield-check','success'],['Sows due soon','due_soon','Next 7 days','calendar-heart','warning'], ...($role === 'admin' ? [['Revenue this month','sales_month','PHP revenue','banknote','brand']] : [])] as [$label,$key,$note,$icon,$tone]): ?>
    <div class="card stat-card dashboard-stat"><div class="stat-top"><span class="metric-icon metric-<?= $tone ?>"><i data-lucide="<?= $icon ?>" aria-hidden="true"></i></span><span class="stat-label"><?= $label ?></span></div><div class="stat-value"><?= $key === 'sales_month' ? '₱'.number_format($stats[$key], 0) : number_format($stats[$key]) ?></div><div class="stat-note"><?= $note ?></div></div>
<?php endforeach; ?>
</div>

<div class="row g-3 mt-1">
    <div class="<?= $role === 'admin' ? 'col-xl-7' : 'col-xl-12' ?>"><div class="card h-100"><div class="d-flex justify-content-between align-items-start"><div><h3 class="section-title">Farrowing alerts</h3><div class="section-note">Upcoming and overdue sow records</div></div><a class="small text-danger" href="<?= BASE_URL ?>/sows/index.php">View tracker</a></div>
        <div class="dashboard-list mt-3"><?php if (!$farrowing): ?><div class="empty-state">No pregnant sows require attention.</div><?php else: foreach ($farrowing as $sow): $late = strtotime($sow['expected_farrowing_date']) < strtotime('today'); ?><a class="dashboard-list-item" href="<?= BASE_URL ?>/sows/index.php"><span class="metric-icon <?= $late ? 'metric-danger' : 'metric-warning' ?>"><i data-lucide="<?= $late ? 'triangle-alert' : 'calendar-days' ?>" aria-hidden="true"></i></span><span><strong><?= e($sow['tag_number']) ?> <?= $late ? 'is overdue' : 'due ' . date('M d', strtotime($sow['expected_farrowing_date'])) ?></strong><small>Expected <?= date('M d, Y', strtotime($sow['expected_farrowing_date'])) ?></small></span><i data-lucide="chevron-right" aria-hidden="true"></i></a><?php endforeach; endif; ?></div>
    </div></div>
    <?php if ($role === 'admin'): ?><div class="col-xl-5"><div class="card h-100"><div class="d-flex justify-content-between align-items-start"><div><h3 class="section-title">Recent sales</h3><div class="section-note">Latest recorded transactions</div></div><a class="small text-danger" href="<?= BASE_URL ?>/sales/index.php">View sales</a></div>
        <div class="dashboard-list mt-3"><?php if (!$recentSales): ?><div class="empty-state">No sales recorded yet.</div><?php else: foreach ($recentSales as $sale): ?><a class="dashboard-list-item" href="<?= BASE_URL ?>/sales/index.php"><span class="metric-icon metric-success"><i data-lucide="receipt-text" aria-hidden="true"></i></span><span><strong>Sale #<?= e(sale_reference((int) $sale['sale_id'])) ?></strong><small><?= e($sale['buyer_name']) ?> · <?= date('M d, Y', strtotime($sale['sale_date'])) ?></small></span><strong class="ms-auto">₱<?= number_format((float) $sale['total_amount'], 0) ?></strong></a><?php endforeach; endif; ?></div>
    </div></div><?php endif; ?>
</div>
<div class="row g-3 mt-1">
    <div class="col-xl-4"><div class="card h-100"><div class="d-flex justify-content-between align-items-start"><div><h3 class="section-title">Barn environment</h3><div class="section-note">Latest ESP32 + DHT22 reading</div></div><span id="sensor-status" class="chip chip-success">Connected</span></div><div class="environment-bars mt-3"><div class="environment-bar"><div class="environment-bar-head"><strong>Temperature</strong><span class="environment-bar-value" id="sensor-temp"><?= e($latest['temperature']) ?><?= is_numeric($latest['temperature']) ? ' °C' : '' ?></span></div><div class="environment-track"><span class="environment-fill temperature" id="sensor-temp-bar" style="width: <?= is_numeric($latest['temperature']) ? min(100, max(0, ((float) $latest['temperature'] / 60) * 100)) : 0 ?>%"></span></div><div class="environment-scale"><span>0°C</span><span>60°C</span></div><div class="environment-guidance">Heat-stress threshold: above <?= e($profile['heat_threshold']) ?>°C</div></div><div class="environment-bar"><div class="environment-bar-head"><strong>Air humidity</strong><span class="environment-bar-value" id="sensor-humid"><?= e($latest['humidity']) ?><?= is_numeric($latest['humidity']) ? ' %' : '' ?></span></div><div class="environment-track"><span class="environment-fill humidity" id="sensor-humid-bar" style="width: <?= is_numeric($latest['humidity']) ? min(100, max(0, (float) $latest['humidity'])) : 0 ?>%"></span></div><div class="environment-scale"><span>0%</span><span>100%</span></div><div class="environment-guidance">Comfortable range 60–80%</div></div></div><div id="heat-alert" class="alert alert-danger mt-3 d-none">Heat-stress threshold exceeded. Check the affected pen.</div><div class="section-note mt-3">Updated <span id="sensor-time"><?= e($latest['recorded_at'] ?? 'No readings yet') ?></span></div></div></div>
    <div class="col-xl-4"><div class="card h-100 population-card"><div class="d-flex justify-content-between align-items-start gap-2"><div><h3 class="section-title">Herd population</h3><div class="section-note">Active pigs by category</div></div><button class="btn btn-sm btn-outline-secondary chart-switch" type="button" data-chart-switch aria-pressed="true">Bar view</button></div><?php $populationMax = max(1, (int) max($stats['boar'], $stats['fattener'], $stats['sow'])); $populationTotal = max(1, $stats['boar'] + $stats['fattener'] + $stats['sow']); ?><div class="population-bars" data-population-bars hidden><div class="population-bar"><div class="population-bar-head"><span>Fatteners</span><strong><?= number_format($stats['fattener']) ?></strong></div><div class="population-track"><span class="population-fill fattener" style="width: <?= ($stats['fattener'] / $populationMax) * 100 ?>%"></span></div></div><div class="population-bar"><div class="population-bar-head"><span>Sows</span><strong><?= number_format($stats['sow']) ?></strong></div><div class="population-track"><span class="population-fill sow" style="width: <?= ($stats['sow'] / $populationMax) * 100 ?>%"></span></div></div><div class="population-bar"><div class="population-bar-head"><span>Boars</span><strong><?= number_format($stats['boar']) ?></strong></div><div class="population-track"><span class="population-fill boar" style="width: <?= ($stats['boar'] / $populationMax) * 100 ?>%"></span></div></div></div><div class="population-donut-wrap" data-population-donut><div class="population-donut" style="--fattener: <?= ($stats['fattener'] / $populationTotal) * 100 ?>%; --sow: <?= ($stats['sow'] / $populationTotal) * 100 ?>%; --boar: <?= ($stats['boar'] / $populationTotal) * 100 ?>%"></div><div class="population-legend"><span><i class="legend-dot fattener"></i>Fatteners <?= number_format($stats['fattener']) ?></span><span><i class="legend-dot sow"></i>Sows <?= number_format($stats['sow']) ?></span><span><i class="legend-dot boar"></i>Boars <?= number_format($stats['boar']) ?></span></div></div></div></div>
    <div class="col-xl-4"><div class="card h-100"><h3 class="section-title">Alerts</h3><div class="section-note mb-3"><?= count($notifications) ?> items need attention</div><div class="dashboard-list"><?php if (!$notifications): ?><div class="empty-state">Everything is under control.</div><?php else: foreach (array_slice($notifications, 0, 4) as $alert): ?><a class="dashboard-list-item" href="<?= BASE_URL . '/' . e($alert['href']) ?>"><span class="metric-icon metric-<?= e($alert['tone']) ?>"><i data-lucide="<?= e($alert['icon']) ?>" aria-hidden="true"></i></span><span><strong><?= e($alert['title']) ?></strong><small><?= e($alert['meta']) ?></small></span></a><?php endforeach; endif; ?></div></div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
