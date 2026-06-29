<?php
// Supports Railway AND Render environment variable formats
$host = getenv('MYSQL_HOST') ?: getenv('MYSQL_URL') ?: getenv('HOST') ?: 'localhost';
$dbname = getenv('MYSQL_DATABASE') ?: getenv('DATABASE') ?: 'gaming_console_hub';
$username = getenv('MYSQL_USER') ?: getenv('MYSQL_USERNAME') ?: getenv('USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: getenv('PASSWORD') ?: '';

// Single connection string (JAWSDB/Railway format)
$url = getenv('JAWSDB_URL') ?: getenv('MYSQL_URL') ?: '';
if ($url) {
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? $host;
    $username = $parsed['user'] ?? $username;
    $password = $parsed['pass'] ?? $password;
    $dbname = ltrim($parsed['path'] ?? '', '/') ?: $dbname;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>