<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';

$pageTitle = 'Reports';
$activeHerd = $pdo->query("SELECT COUNT(*) FROM pigs WHERE status = 'active'")->fetchColumn();
$totalHerd = (int) $pdo->query('SELECT COUNT(*) FROM pigs')->fetchColumn();
$totalWeight = (float) $pdo->query("SELECT COALESCE(SUM(weight), 0) FROM pigs WHERE status = 'active'")->fetchColumn();
$averageWeight = (float) $pdo->query("SELECT COALESCE(AVG(weight), 0) FROM pigs WHERE status = 'active'")->fetchColumn();
$deadHerd = (int) $pdo->query("SELECT COUNT(*) FROM pigs WHERE status = 'dead'")->fetchColumn();
$sales30 = $pdo->query("SELECT COUNT(*), COALESCE(SUM(total_amount), 0) FROM sales WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch(PDO::FETCH_NUM);
$breeding = $pdo->query("SELECT status, COUNT(*) total, COALESCE(SUM(piglets_born), 0) piglets FROM sow_records GROUP BY status ORDER BY FIELD(status, 'pregnant', 'farrowed', 'failed')")->fetchAll();
$population = $pdo->query("SELECT category, COUNT(*) total FROM pigs WHERE status = 'active' GROUP BY category ORDER BY FIELD(category, 'sow', 'fattener', 'boar')")->fetchAll();
$monthly = $pdo->query("SELECT DATE_FORMAT(sale_date, '%Y-%m') month, COUNT(*) sales_count, COALESCE(SUM(total_amount), 0) revenue FROM sales GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll();
$dailyEnvironment = $pdo->query("SELECT DATE(recorded_at) reading_date, ROUND(AVG(temperature), 1) temperature, ROUND(AVG(humidity), 1) humidity FROM sensor_logs WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY reading_date ORDER BY reading_date ASC")->fetchAll();
$dueSoon = (int) $pdo->query("SELECT COUNT(*) FROM sow_records WHERE status = 'pregnant' AND expected_farrowing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$piglets = (int) $pdo->query("SELECT COALESCE(SUM(piglets_born), 0) FROM sow_records WHERE status = 'farrowed'")->fetchColumn();
$populationLabels = array_map(static fn (array $row): string => ucfirst($row['category']), $population);
$populationValues = array_map(static fn (array $row): int => (int) $row['total'], $population);
$monthlyLabels = array_reverse(array_map(static fn (array $row): string => $row['month'], $monthly));
$monthlyValues = array_reverse(array_map(static fn (array $row): float => (float) $row['revenue'], $monthly));
$environmentLabels = array_map(static fn (array $row): string => $row['reading_date'], $dailyEnvironment);
$temperatureValues = array_map(static fn (array $row): float => (float) $row['temperature'], $dailyEnvironment);
$humidityValues = array_map(static fn (array $row): float => (float) $row['humidity'], $dailyEnvironment);
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div><p class="eyebrow">Farm intelligence</p><h2>Reports & analytics</h2><p>Make decisions from herd, breeding, sales, and barn-environment trends.</p></div>
    <a class="btn btn-danger primary-action" href="export.php?type=all"><i data-lucide="archive" aria-hidden="true"></i> Export all reports</a>
</div>
<div class="row g-3 mb-3">
    <?php foreach ([['Active herd', $activeHerd, 'piggy-bank', 'metric-brand'], ['Live herd weight', number_format($totalWeight, 1) . ' kg', 'scale', 'metric-success'], ['Sales, last 30 days', '₱' . number_format((float) $sales30[1], 2), 'wallet', 'metric-warning'], ['Due within 7 days', $dueSoon, 'calendar-clock', 'metric-danger']] as $metric): ?>
    <div class="col-sm-6 col-xl-3"><div class="card stat-card dashboard-stat"><div class="stat-top"><span class="metric-icon <?= $metric[3] ?>"><i data-lucide="<?= $metric[2] ?>" aria-hidden="true"></i></span><span class="stat-label"><?= e($metric[0]) ?></span></div><div class="stat-value"><?= e($metric[1]) ?></div><div class="stat-note"><?= $metric[0] === 'Active herd' ? $totalHerd . ' total records' : ($metric[0] === 'Live herd weight' ? 'Average ' . number_format($averageWeight, 1) . ' kg per active pig' : ($metric[0] === 'Sales, last 30 days' ? $sales30[0] . ' transactions' : 'Pregnant sows approaching farrowing')) ?></div></div></div>
    <?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
    <div class="col-xl-7"><div class="card report-chart-card"><div class="report-card-heading"><div><h3 class="section-title">Revenue trend</h3><div class="section-note">Monthly sales performance</div></div><a class="btn btn-outline-secondary" href="export.php?type=sales"><i data-lucide="download" aria-hidden="true"></i> CSV</a></div><div class="report-chart-wrap"><canvas id="revenue-chart" aria-label="Monthly revenue chart"></canvas></div></div></div>
    <div class="col-xl-5"><div class="card report-chart-card"><div class="report-card-heading"><div><h3 class="section-title">Active herd mix</h3><div class="section-note">Population by category</div></div><a class="btn btn-outline-secondary" href="export.php?type=herd"><i data-lucide="download" aria-hidden="true"></i> CSV</a></div><div class="report-chart-wrap report-donut-wrap"><canvas id="population-chart" aria-label="Active herd category chart"></canvas></div></div></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-xl-7"><div class="card report-chart-card"><div class="report-card-heading"><div><h3 class="section-title">Barn environment trend</h3><div class="section-note">Daily averages for the last 30 days</div></div><a class="btn btn-outline-secondary" href="export.php?type=environment"><i data-lucide="download" aria-hidden="true"></i> CSV</a></div><div class="report-chart-wrap"><canvas id="environment-chart" aria-label="Temperature and humidity trend chart"></canvas></div></div></div>
    <div class="col-xl-5"><div class="card"><div class="report-card-heading"><div><h3 class="section-title">Breeding performance</h3><div class="section-note">Current sow-record outcomes</div></div><a class="btn btn-outline-secondary" href="export.php?type=breeding"><i data-lucide="download" aria-hidden="true"></i> CSV</a></div><?php foreach ($breeding as $row): ?><div class="report-progress-row"><div><span><?= e(ucfirst($row['status'])) ?></span><strong><?= e($row['total']) ?></strong></div><div class="population-track"><span class="population-fill <?= $row['status'] === 'pregnant' ? 'sow' : ($row['status'] === 'farrowed' ? 'fattener' : 'boar') ?>" style="width: <?= $breeding ? ((int) $row['total'] / max(1, array_sum(array_column($breeding, 'total'))) * 100) : 0 ?>%"></span></div><?php if ($row['status'] === 'farrowed'): ?><small><?= e($row['piglets']) ?> piglets recorded</small><?php endif; ?></div><?php endforeach; ?><?php if (!$breeding): ?><p class="text-muted py-4 mb-0">No breeding records yet.</p><?php endif; ?><div class="report-mini-grid"><div><strong><?= $piglets ?></strong><span>Piglets born</span></div><div><strong><?= $deadHerd ?></strong><span>Dead records</span></div></div></div></div>
</div>
<div class="card"><div class="report-card-heading"><div><h3 class="section-title">Monthly sales detail</h3><div class="section-note">Revenue and transaction counts</div></div><a class="btn btn-outline-secondary" href="export.php?type=sales"><i data-lucide="download" aria-hidden="true"></i> Download report</a></div><div class="table-responsive"><table class="table"><thead><tr><th>Month</th><th>Transactions</th><th class="text-end">Revenue</th></tr></thead><tbody><?php foreach ($monthly as $row): ?><tr><td><?= e($row['month']) ?></td><td><?= e($row['sales_count']) ?></td><td class="text-end">₱<?= number_format((float) $row['revenue'], 2) ?></td></tr><?php endforeach; ?><?php if (!$monthly): ?><tr><td colspan="3" class="text-center text-muted py-4">No sales recorded.</td></tr><?php endif; ?></tbody></table></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const reportTheme = { grid: getComputedStyle(document.documentElement).getPropertyValue('--chart-grid').trim() || '#e9ebef', muted: getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() || '#737982' };
const labels = <?= json_encode(['population' => $populationLabels, 'months' => $monthlyLabels, 'environment' => $environmentLabels], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const values = <?= json_encode(['population' => $populationValues, 'revenue' => $monthlyValues, 'temperature' => $temperatureValues, 'humidity' => $humidityValues]) ?>;
const axis = { ticks: { color: reportTheme.muted }, grid: { color: reportTheme.grid } };
new Chart(document.getElementById('revenue-chart'), { type: 'bar', data: { labels: labels.months, datasets: [{ data: values.revenue, backgroundColor: '#b93030cc', borderRadius: 7 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => `₱${Number(c.parsed.y).toLocaleString()}` } } }, scales: { x: { ...axis, grid: { display: false } }, y: axis } } });
new Chart(document.getElementById('population-chart'), { type: 'doughnut', data: { labels: labels.population, datasets: [{ data: values.population, backgroundColor: ['#ad7147', '#657b3b', '#b93030'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '66%', plugins: { legend: { position: 'bottom', labels: { color: reportTheme.muted, usePointStyle: true, padding: 16 } } } } });
new Chart(document.getElementById('environment-chart'), { type: 'line', data: { labels: labels.environment, datasets: [{ label: 'Temperature °C', data: values.temperature, borderColor: '#c63a48', backgroundColor: '#c63a4826', fill: true, tension: .35, yAxisID: 'temperature' }, { label: 'Humidity %', data: values.humidity, borderColor: '#3276c4', backgroundColor: '#3276c426', fill: true, tension: .35, yAxisID: 'humidity' }] }, options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { x: { ...axis, grid: { display: false } }, temperature: { ...axis, position: 'left', suggestedMin: 0, suggestedMax: 60 }, humidity: { ...axis, position: 'right', suggestedMin: 0, suggestedMax: 100, grid: { drawOnChartArea: false } } } } });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
