<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $reading = $pdo->query('SELECT temperature, humidity, recorded_at FROM sensor_logs ORDER BY recorded_at DESC LIMIT 1')->fetch();
    if (!$reading) { echo json_encode(['temperature'=>null,'humidity'=>null,'recorded_at'=>null,'alert'=>false,'humidity_alert'=>false,'humidity_status'=>'unknown']); exit; }
    $reading['humidity_status'] = (float) $reading['humidity'] < 60 ? 'Low humidity' : ((float) $reading['humidity'] > 80 ? 'High humidity' : 'Comfortable');
    $reading['humidity_alert'] = $reading['humidity_status'] !== 'Comfortable';
    $reading['alert'] = (float)$reading['temperature'] > 32 || $reading['humidity_alert'];
    echo json_encode($reading); exit;
}
if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$pdo->exec("CREATE TABLE IF NOT EXISTS device_settings (
    setting_id TINYINT UNSIGNED NOT NULL,
    device_name VARCHAR(100) NOT NULL DEFAULT 'ESP32-01',
    device_token VARCHAR(255) NOT NULL,
    PRIMARY KEY (setting_id)
) ENGINE=InnoDB");
$deviceSettings = $pdo->query('SELECT device_token FROM device_settings WHERE setting_id = 1')->fetch();
if (!$deviceSettings || trim((string) $deviceSettings['device_token']) === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Device is not paired. Pair it from Settings before sending readings.']);
    exit;
}
if (!hash_equals((string) $deviceSettings['device_token'], $_SERVER['HTTP_X_DEVICE_TOKEN'] ?? '')) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
$payload = json_decode(file_get_contents('php://input'), true);
$temperature = $payload['temperature'] ?? null; $humidity = $payload['humidity'] ?? null;
if (!is_numeric($temperature) || !is_numeric($humidity) || (float)$temperature < -10 || (float)$temperature > 60 || (float)$humidity < 0 || (float)$humidity > 100) { http_response_code(422); echo json_encode(['error'=>'Invalid reading']); exit; }
$statement = $pdo->prepare('INSERT INTO sensor_logs (device_id,temperature,humidity) VALUES (:device,:temperature,:humidity)');
$statement->execute(['device' => $deviceSettings['device_name'], 'temperature' => (float) $temperature, 'humidity' => (float) $humidity]);
echo json_encode(['status'=>'ok']);
