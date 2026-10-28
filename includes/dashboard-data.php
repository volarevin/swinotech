<?php
declare(strict_types=1);

function dashboard_profile(PDO $pdo): array
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS farm_profile (
        profile_id TINYINT UNSIGNED NOT NULL,
        farm_name VARCHAR(150) NOT NULL,
        owner_name VARCHAR(150) NOT NULL,
        location VARCHAR(255) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'PHP',
        heat_threshold DECIMAL(5,2) NOT NULL DEFAULT 32,
        due_soon_days TINYINT UNSIGNED NOT NULL DEFAULT 7,
        PRIMARY KEY (profile_id)
    ) ENGINE=InnoDB");
    $profile = $pdo->query('SELECT * FROM farm_profile WHERE profile_id = 1')->fetch();
    return $profile ?: [
        'farm_name' => 'Villa Verde Piggery', 'owner_name' => 'Maria Villanueva',
        'location' => 'Batangas, Philippines', 'currency' => 'PHP',
        'heat_threshold' => 32, 'due_soon_days' => 7,
    ];
}

function dashboard_notifications(PDO $pdo, array $profile): array
{
    $days = max(1, (int) $profile['due_soon_days']);
    $notifications = [];
    $sows = $pdo->query("SELECT s.sow_record_id, p.tag_number, s.expected_farrowing_date
        FROM sow_records s JOIN pigs p ON p.pig_id = s.pig_id
        WHERE s.status = 'pregnant' ORDER BY s.expected_farrowing_date")->fetchAll();
    foreach ($sows as $sow) {
        $daysAway = (int) $pdo->query("SELECT DATEDIFF(" . $pdo->quote($sow['expected_farrowing_date']) . ", CURDATE())")->fetchColumn();
        if ($daysAway < 0 || $daysAway <= $days) {
            $notifications[] = [
                'id' => ($daysAway < 0 ? 'overdue-' : 'due-') . $sow['sow_record_id'],
                'tone' => $daysAway < 0 ? 'danger' : 'warning',
                'icon' => $daysAway < 0 ? 'triangle-alert' : 'calendar-days',
                'title' => 'Sow ' . $sow['tag_number'] . ($daysAway < 0 ? ' is overdue' : ' due ' . date('M d', strtotime($sow['expected_farrowing_date']))),
                'meta' => 'Expected ' . date('M d, Y', strtotime($sow['expected_farrowing_date'])) . ($daysAway < 0 ? ' · ' . abs($daysAway) . ' days late' : ' · Farrowing in ' . $daysAway . ' days'),
                'href' => 'sows/index.php',
            ];
        }
    }
    $reading = $pdo->query('SELECT temperature, humidity, recorded_at FROM sensor_logs ORDER BY recorded_at DESC LIMIT 1')->fetch();
    if ($reading && (float) $reading['temperature'] > (float) $profile['heat_threshold']) {
        $notifications[] = ['id' => 'heat-stress', 'tone' => 'danger', 'icon' => 'thermometer', 'title' => 'Heat stress — ' . $reading['temperature'] . '°C', 'meta' => 'Above the ' . $profile['heat_threshold'] . '°C threshold', 'href' => 'monitoring/index.php'];
    }
    if ($reading && (float) $reading['humidity'] < 60) {
        $notifications[] = ['id' => 'low-humidity', 'tone' => 'warning', 'icon' => 'droplets', 'title' => 'Low humidity — ' . $reading['humidity'] . '%', 'meta' => 'Below the comfortable 60–80% range', 'href' => 'monitoring/index.php'];
    } elseif ($reading && (float) $reading['humidity'] > 80) {
        $notifications[] = ['id' => 'high-humidity', 'tone' => 'warning', 'icon' => 'droplets', 'title' => 'High humidity — ' . $reading['humidity'] . '%', 'meta' => 'Above the comfortable 60–80% range', 'href' => 'monitoring/index.php'];
    }
    if ($reading && strtotime((string) $reading['recorded_at']) < time() - 7200) {
        $notifications[] = ['id' => 'stale-sensor', 'tone' => 'warning', 'icon' => 'wifi-off', 'title' => 'ESP32 has no new readings', 'meta' => 'Last reading ' . date('M d, H:i', strtotime($reading['recorded_at'])), 'href' => 'monitoring/index.php'];
    }
    $sale = $pdo->query("SELECT sale_id, buyer_name, total_amount, sale_date FROM sales ORDER BY sale_id DESC LIMIT 1")->fetch();
    if ($sale) {
        $notifications[] = ['id' => 'sale-' . $sale['sale_id'], 'tone' => 'success', 'icon' => 'receipt-text', 'title' => 'Sale #' . sale_reference((int) $sale['sale_id']) . ' recorded', 'meta' => $sale['buyer_name'] . ' · ₱' . number_format((float) $sale['total_amount'], 2), 'href' => 'sales/view.php?id=' . (int) $sale['sale_id']];
    }
    return $notifications;
}

function dashboard_stats(PDO $pdo, array $profile): array
{
    $stats = ['total_active' => 0, 'boar' => 0, 'fattener' => 0, 'sow' => 0, 'sales_month' => 0.0, 'sales_previous' => 0.0, 'due_soon' => 0, 'overdue' => 0, 'pregnant' => 0];
    $stats['total_active'] = (int) $pdo->query("SELECT COUNT(*) FROM pigs WHERE status = 'active'")->fetchColumn();
    foreach ($pdo->query("SELECT category, COUNT(*) total FROM pigs WHERE status = 'active' GROUP BY category") as $row) $stats[$row['category']] = (int) $row['total'];
    $stats['sales_month'] = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE YEAR(sale_date)=YEAR(CURDATE()) AND MONTH(sale_date)=MONTH(CURDATE())")->fetchColumn();
    $stats['sales_previous'] = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE YEAR(sale_date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(sale_date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
    $stats['pregnant'] = (int) $pdo->query("SELECT COUNT(*) FROM sow_records WHERE status='pregnant'")->fetchColumn();
    $dueDays = (int) $profile['due_soon_days'];
    $stats['due_soon'] = (int) $pdo->query("SELECT COUNT(*) FROM sow_records WHERE status='pregnant' AND expected_farrowing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$dueDays} DAY)")->fetchColumn();
    $stats['overdue'] = (int) $pdo->query("SELECT COUNT(*) FROM sow_records WHERE status='pregnant' AND expected_farrowing_date < CURDATE()")->fetchColumn();
    return $stats;
}
