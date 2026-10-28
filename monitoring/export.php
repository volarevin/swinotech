<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';

$readings = $pdo->query('SELECT recorded_at, device_id, temperature, humidity FROM sensor_logs ORDER BY recorded_at ASC, log_id ASC')->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sensor-readings-' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$output = fopen('php://output', 'w');
if ($output === false) {
    http_response_code(500);
    exit('Unable to create the CSV export.');
}

fputcsv($output, ['Recorded at', 'Device', 'Temperature (°C)', 'Humidity (%)']);
foreach ($readings as $reading) {
    fputcsv($output, [
        $reading['recorded_at'],
        $reading['device_id'],
        $reading['temperature'],
        $reading['humidity'],
    ]);
}
fclose($output);
