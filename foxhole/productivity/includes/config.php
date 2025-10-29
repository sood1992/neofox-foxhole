<?php
// Database configuration for Foxhole productivity management platform

$host = getenv('FOXHOLE_DB_HOST') ?: 'localhost';
$db   = getenv('FOXHOLE_DB_NAME') ?: 'foxhole_productivity';
$user = getenv('FOXHOLE_DB_USER') ?: 'foxhole_user';
$pass = getenv('FOXHOLE_DB_PASS') ?: 'secret_password';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database connection failed. Please verify your credentials in productivity/includes/config.php';
    exit;
}
