<?php
require_once 'config.php';

echo "Creating database tables...\n";

$tables = [
    "CREATE TABLE IF NOT EXISTS staff (
        staff_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL DEFAULT '',
        role ENUM('Admin', 'Technician', 'Attendant') NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(150),
        status ENUM('Active', 'Inactive') DEFAULT 'Active'
    )",
    "CREATE TABLE IF NOT EXISTS member (
        member_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150),
        phone VARCHAR(20),
        membership_type ENUM('Casual', 'Standard', 'Premium'),
        join_date DATE,
        status ENUM('Active', 'Inactive') DEFAULT 'Active'
    )",
    "CREATE TABLE IF NOT EXISTS console_type (
        console_type_id INT AUTO_INCREMENT PRIMARY KEY,
        console_type_name VARCHAR(100) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS station (
        station_id INT AUTO_INCREMENT PRIMARY KEY,
        console_type_id INT,
        station_name VARCHAR(100),
        hourly_rate DECIMAL(8,2),
        status ENUM('Available', 'Unavailable') DEFAULT 'Available'
    )",
    "CREATE TABLE IF NOT EXISTS booking (
        booking_id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT,
        station_id INT,
        staff_id INT,
        booking_date DATE,
        start_time TIME,
        end_time TIME,
        total_hours DECIMAL(5,2),
        status ENUM('Confirmed', 'Completed', 'Cancelled') DEFAULT 'Confirmed'
    )",
    "CREATE TABLE IF NOT EXISTS payment (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT,
        payment_date DATE,
        payment_method ENUM('Cash', 'Card', 'E-Wallet'),
        amount DECIMAL(10,2),
        payment_status ENUM('Paid', 'Unpaid', 'Refunded') DEFAULT 'Unpaid'
    )",
    "CREATE TABLE IF NOT EXISTS console_rate (
        rate_id INT AUTO_INCREMENT PRIMARY KEY,
        station_id INT,
        day_type ENUM('Weekday', 'Weekend', 'Public Holiday'),
        hourly_rate DECIMAL(8,2),
        effective_from DATE
    )",
    "CREATE TABLE IF NOT EXISTS maintenance (
        maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
        station_id INT,
        staff_id INT,
        maintenance_date DATE,
        description TEXT,
        status ENUM('Scheduled', 'In Progress', 'Completed') DEFAULT 'Scheduled'
    )"
];

foreach ($tables as $sql) {
    try {
        $pdo->exec($sql);
        $tableName = explode('IF NOT EXISTS ', $sql)[1];
        $tableName = explode(' (', $tableName)[0];
        echo "✓ Created table: $tableName\n";
    } catch (PDOException $e) {
        echo "⚠️ Table might already exist: " . $e->getMessage() . "\n";
    }
}

echo "\nAll tables created successfully!\n";
echo "Now run: php seeder.php\n";
?>
