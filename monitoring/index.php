<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';

$pageTitle = 'Environmental Monitoring';
$readings = $pdo->query('SELECT * FROM sensor_logs ORDER BY recorded_at DESC, log_id DESC LIMIT 50')->fetchAll();
$chartReadings = array_reverse($readings);
$latest = $readings[0] ?? null;
$chartLabels = array_map(static fn (array $reading): string => (string) $reading['recorded_at'], $chartReadings);
$temperatures = array_map(static fn (array $reading): float => (float) $reading['temperature'], $chartReadings);
$humidities = array_map(static fn (array $reading): float => (float) $reading['humidity'], $chartReadings);
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading">
    <div><p class="eyebrow">IoT environmental monitoring</p><h2>Barn climate</h2><p>ESP32 + DHT22 readings. Temperatures above 32°C raise a heat-stress warning.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="export.php"><i data-lucide="download" aria-hidden="true"></i> Export CSV</a><button class="btn btn-outline-secondary" type="button" onclick="location.reload()">↻ Refresh</button></div>
</div>
<div class="row g-3 mb-3 monitoring-summary">
    <div class="col-md-4"><div class="card stat-card dashboard-stat"><div class="stat-top"><span class="metric-icon metric-warning"><i data-lucide="thermometer" aria-hidden="true"></i></span><span class="stat-label">Temperature</span></div><div class="stat-value" id="monitor-temp"><?= e($latest['temperature'] ?? '—') ?><?= $latest ? ' °C' : '' ?></div><div class="environment-track mt-3"><span class="environment-fill temperature" id="monitor-temp-bar" style="width: <?= $latest ? min(100, max(0, ((float) $latest['temperature'] / 60) * 100)) : 0 ?>%"></span></div><div class="stat-note">Heat-stress threshold: above 32°C</div></div></div>
    <div class="col-md-4"><div class="card stat-card dashboard-stat"><div class="stat-top"><span class="metric-icon metric-brand"><i data-lucide="droplets" aria-hidden="true"></i></span><span class="stat-label">Air humidity</span></div><div class="stat-value" id="monitor-humidity"><?= e($latest['humidity'] ?? '—') ?><?= $latest ? ' %' : '' ?></div><div class="environment-track mt-3"><span class="environment-fill humidity" id="monitor-humid-bar" style="width: <?= $latest ? min(100, max(0, (float) $latest['humidity'])) : 0 ?>%"></span></div><div class="stat-note">Comfortable range 60–80%</div></div></div>
    <div class="col-md-4"><div class="card stat-card dashboard-stat"><div class="stat-top"><span class="metric-icon metric-success"><i data-lucide="server" aria-hidden="true"></i></span><span class="stat-label">Device</span></div><div class="stat-value fs-3"><?= e($latest['device_id'] ?? 'ESP32-01') ?></div><div class="stat-note" id="monitor-status"><?= $latest ? 'Live feed connected' : 'Waiting for reading' ?></div></div></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-xl-6"><div class="card monitoring-chart-card"><div class="d-flex justify-content-between align-items-start"><div><h3 class="section-title">Temperature history</h3><div class="section-note">Last <?= count($chartReadings) ?> readings</div></div><span class="chip chip-warning">°C</span></div><div class="monitoring-chart-wrap"><canvas id="temperature-history" aria-label="Temperature history chart"></canvas></div></div></div>
    <div class="col-xl-6"><div class="card monitoring-chart-card"><div class="d-flex justify-content-between align-items-start"><div><h3 class="section-title">Humidity history</h3><div class="section-note">Last <?= count($chartReadings) ?> readings</div></div><span class="chip chip-brand">%</span></div><div class="monitoring-chart-wrap"><canvas id="humidity-history" aria-label="Humidity history chart"></canvas></div></div></div>
</div>
<div class="row g-3">
    <div class="col-xl-8"><div class="card"><h3 class="section-title">Recent sensor readings</h3><div class="section-note mb-3">Latest 50 entries from sensor_logs</div><div class="table-responsive"><table class="table"><thead><tr><th>Recorded at</th><th>Device</th><th>Temperature</th><th>Humidity</th><th>Status</th></tr></thead><tbody><?php foreach ($readings as $reading): $alert = (float) $reading['temperature'] > 32; ?><tr><td><?= e($reading['recorded_at']) ?></td><td><?= e($reading['device_id']) ?></td><td><?= e($reading['temperature']) ?>°C</td><td><?= e($reading['humidity']) ?>%</td><td><span class="chip <?= $alert ? 'chip-danger' : 'chip-success' ?>"><?= $alert ? 'Heat stress' : 'Safe' ?></span></td></tr><?php endforeach; ?><?php if (!$readings): ?><tr><td colspan="5" class="text-center text-muted py-5">No sensor readings yet.</td></tr><?php endif; ?></tbody></table></div></div></div>
    <div class="col-xl-4"><div class="card"><h3 class="section-title">Integration notes</h3><p class="section-note">POST readings to <code>/api/sensor-data.php</code> with the <code>X-Device-Token</code> header. Dashboard polling is read-only and does not expose the device token.</p></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const chartLabels = <?= json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const temperatureData = <?= json_encode($temperatures) ?>;
const humidityData = <?= json_encode($humidities) ?>;
const chartColors = { grid: getComputedStyle(document.documentElement).getPropertyValue('--chart-grid').trim() || '#e9ebef', ink: getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() || '#737982' };
const chartOptions = (label, color, min, max) => ({ responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => `${label}: ${context.parsed.y}` } } }, scales: { x: { grid: { display: false }, ticks: { color: chartColors.ink, maxTicksLimit: 6 } }, y: { min, max, grid: { color: chartColors.grid }, ticks: { color: chartColors.ink } } } });
new Chart(document.getElementById('temperature-history'), { type: 'line', data: { labels: chartLabels, datasets: [{ data: temperatureData, borderColor: '#c63a48', backgroundColor: '#c63a4826', fill: true, tension: .35, pointRadius: 2, pointHoverRadius: 4 }] }, options: chartOptions('Temperature', '#c63a48', 0, 60) });
new Chart(document.getElementById('humidity-history'), { type: 'line', data: { labels: chartLabels, datasets: [{ data: humidityData, borderColor: '#3276c4', backgroundColor: '#3276c426', fill: true, tension: .35, pointRadius: 2, pointHoverRadius: 4 }] }, options: chartOptions('Humidity', '#3276c4', 0, 100) });
async function refresh() { const response = await fetch('/SwinoTech/api/sensor-data.php'); if (!response.ok) return; const reading = await response.json(); if (reading.temperature === null) return; document.getElementById('monitor-temp').textContent = `${reading.temperature} °C`; document.getElementById('monitor-humidity').textContent = `${reading.humidity} %`; document.getElementById('monitor-temp-bar').style.width = `${Math.min(100, Math.max(0, (Number(reading.temperature) / 60) * 100))}%`; document.getElementById('monitor-humid-bar').style.width = `${Math.min(100, Math.max(0, Number(reading.humidity)))}%`; document.getElementById('monitor-status').textContent = reading.alert ? (Number(reading.temperature) > 32 ? 'Heat stress' : reading.humidity_status) : 'Safe'; }
setInterval(refresh, 10000);
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
