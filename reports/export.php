<?php
require __DIR__ . '/../includes/auth.php';
require_login();
require __DIR__ . '/../includes/db.php';

$type = $_GET['type'] ?? 'sales';
$reports = [
    'herd' => [
        'filename' => 'herd-inventory',
        'headers' => ['Tag number', 'Category', 'Weight (kg)', 'Pen', 'Status', 'Date added'],
        'query' => "SELECT tag_number, category, weight, COALESCE(pen_number, ''), status, date_added FROM pigs ORDER BY category, tag_number",
    ],
    'sales' => [
        'filename' => 'sales-summary',
        'headers' => ['Sale ID', 'Buyer', 'Sale date', 'Total amount'],
        'query' => 'SELECT sale_id, buyer_name, sale_date, total_amount FROM sales ORDER BY sale_date DESC, sale_id DESC',
    ],
    'breeding' => [
        'filename' => 'breeding-performance',
        'headers' => ['Tag number', 'Mating date', 'Expected farrowing', 'Status', 'Piglets born'],
        'query' => "SELECT p.tag_number, s.mating_date, s.expected_farrowing_date, s.status, s.piglets_born FROM sow_records s JOIN pigs p ON p.pig_id = s.pig_id ORDER BY s.mating_date DESC, s.sow_record_id DESC",
    ],
    'environment' => [
        'filename' => 'environment-readings',
        'headers' => ['Recorded at', 'Device', 'Temperature (°C)', 'Humidity (%)'],
        'query' => 'SELECT recorded_at, device_id, temperature, humidity FROM sensor_logs ORDER BY recorded_at ASC, log_id ASC',
    ],
];

if ($type === 'all') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="swinotech-reports-' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $output = fopen('php://output', 'w');
    if ($output === false) {
        http_response_code(500);
        exit('Unable to create the combined report export.');
    }
    foreach ($reports as $report) {
        fputcsv($output, []);
        fputcsv($output, [$report['filename']]);
        fputcsv($output, $report['headers']);
        foreach ($pdo->query($report['query'])->fetchAll(PDO::FETCH_NUM) as $row) {
            fputcsv($output, array_map(static function ($value): string {
                $value = (string) $value;
                return preg_match('/^[=+\-@]/', $value) === 1 ? "'" . $value : $value;
            }, $row));
        }
    }
    fclose($output);
    exit;
}

if (!isset($reports[$type])) {
    http_response_code(400);
    exit('Unknown report type.');
}

$report = $reports[$type];
$rows = $pdo->query($report['query'])->fetchAll(PDO::FETCH_NUM);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $report['filename'] . '-' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate');
$output = fopen('php://output', 'w');
if ($output === false) {
    http_response_code(500);
    exit('Unable to create the CSV export.');
}
fputcsv($output, $report['headers']);
foreach ($rows as $row) {
    fputcsv($output, array_map(static function ($value): string {
        $value = (string) $value;
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'" . $value : $value;
    }, $row));
}
fclose($output);
