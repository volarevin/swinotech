<?php
declare(strict_types=1);

$dbHost = getenv('SWINOTECH_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('SWINOTECH_DB_PORT') ?: '3306';
$dbName = getenv('SWINOTECH_DB_NAME') ?: 'swinotech';
$dbUser = getenv('SWINOTECH_DB_USER') ?: 'root';
$dbPassword = getenv('SWINOTECH_DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $exception) {
    error_log('SwinoTech database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('Database connection unavailable.');
}
