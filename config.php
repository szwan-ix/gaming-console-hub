<?php
// Auto-detect environment: Railway, Render, Aiven, or local
$host     = getenv('MYSQL_HOST') ?: getenv('HOST') ?: 'localhost';
$dbname   = getenv('MYSQL_DATABASE') ?: getenv('DATABASE') ?: 'gaming_console_hub';
$username = getenv('MYSQL_USER') ?: getenv('MYSQL_USERNAME') ?: getenv('USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: getenv('PASSWORD') ?: '';

// JAWSDB / Railway connection string
$url = getenv('JAWSDB_URL') ?: getenv('MYSQL_URL') ?: '';
if ($url) {
    $p = parse_url($url);
    $host     = $p['host'] ?? $host;
    $username = $p['user'] ?? $username;
    $password = $p['pass'] ?? $password;
    $dbname   = ltrim($p['path'] ?? '', '/') ?: $dbname;
}

// Aiven returns host:port in one string
$port = '3306';
if (strpos($host, ':') !== false) {
    $parts = explode(':', $host);
    $host = $parts[0];
    $port = $parts[1] ?? '3306';
}

// SSL CA cert path (for Aiven)
$sslCa = getenv('MYSQL_SSL_CA') ?: '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    if ($sslCa) {
        $opts[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    $pdo = new PDO($dsn, $username, $password, $opts);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>