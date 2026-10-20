<?php

// Load database settings from the project's root .env file.
$envFile = dirname(__DIR__) . '/.env';

if (!file_exists($envFile)) {
    die('Database configuration file is missing.');
}

$env = parse_ini_file($envFile);

if ($env === false) {
    die('Could not read database configuration.');
}

$host = $env['DB_HOST'] ?? 'localhost';
$dbname = $env['DB_NAME'] ?? 'healthcare_app';
$username = $env['DB_USER'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    // Do not expose database credentials or internal errors to users.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed.');
}
