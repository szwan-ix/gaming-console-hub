<?php
/**
 * Database Seeder - Run this once to populate demo data
 * Access via: http://localhost/gaming_console_hub/seeder.php
 */

require_once 'config.php';

$success = [];
$errors = [];

try {
    // ===== CLEAR EXISTING DATA (Optional - comment out for production) =====
    // $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    // $pdo->exec("TRUNCATE TABLE staff");
    // $pdo->exec("TRUNCATE TABLE member");
    // $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    // ===== SEED STAFF (Admin & Technician Users) =====
    $staff = [
        ['name' => 'admin', 'password' => 'admin123', 'role' => 'Admin', 'phone' => '0123456789', 'email' => 'admin@gamingconsolehub.com', 'status' => 'Active'],
        ['name' => 'tech1', 'password' => 'admin123', 'role' => 'Technician', 'phone' => '0187654321', 'email' => 'tech@gamingconsolehub.com', 'status' => 'Active'],
        ['name' => 'attendant1', 'password' => 'admin123', 'role' => 'Attendant', 'phone' => '0198765432', 'email' => 'attendant@gamingconsolehub.com', 'status' => 'Active'],
    ];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE name = ?");
    foreach ($staff as $s) {
        $stmt->execute([$s['name']]);
        if ($stmt->fetchColumn() == 0) {
            $hashedPassword = password_hash($s['password'], PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO staff (name, password, role, phone, email, status) VALUES (?,?,?,?,?,?)");
            $insert->execute([$s['name'], $hashedPassword, $s['role'], $s['phone'], $s['email'], $s['status']]);
            $success[] = "✓ Staff user '{$s['name']}' ({$s['role']}) created";
        } else {
            $success[] = "→ Staff user '{$s['name']}' already exists";
        }
    }

    // ===== SEED CONSOLE TYPES =====
    $consoles = [
        'Gaming PC',
        'Playstation 5 (PS5)',
        'Nintendo Switch',
        'Xbox Series X',
        'Virtual Reality (VR) Pod'
    ];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM console_type WHERE console_type_name = ?");
    foreach ($consoles as $c) {
        $stmt->execute([$c]);
        if ($stmt->fetchColumn() == 0) {
            $insert = $pdo->prepare("INSERT INTO console_type (console_type_name) VALUES (?)");
            $insert->execute([$c]);
            $success[] = "✓ Console type '$c' created";
        } else {
            $success[] = "→ Console type '$c' already exists";
        }
    }

    // ===== SEED STATIONS =====
    $stationStmt = $pdo->prepare("SELECT console_type_id FROM console_type WHERE console_type_name = ?");
    $stations = [
        ['Gaming PC', 'Gaming PC - Station 1', 50],
        ['Gaming PC', 'Gaming PC - Station 2', 50],
        ['Playstation 5 (PS5)', 'PS5 - Station 1', 45],
        ['Nintendo Switch', 'Nintendo Switch - Station 1', 35],
        ['Xbox Series X', 'Xbox Series X - Station 1', 48],
    ];

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM station WHERE station_name = ?");
    foreach ($stations as $st) {
        $stmtCheck->execute([$st[1]]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stationStmt->execute([$st[0]]);
            $consoleTypeId = $stationStmt->fetchColumn();

            $insert = $pdo->prepare("INSERT INTO station (console_type_id, station_name, hourly_rate, status) VALUES (?,?,?,?)");
            $insert->execute([$consoleTypeId, $st[1], $st[2], 'Available']);
            $success[] = "✓ Station '{$st[1]}' created (RM{$st[2]}/hr)";
        } else {
            $success[] = "→ Station '{$st[1]}' already exists";
        }
    }

    // ===== SEED CONSOLE RATES (formula: Weekday = base, Weekend = base+2, PH = base-2) =====
    $consoleRates = [
        ['Gaming PC', 'Weekday', 7],
        ['Gaming PC', 'Weekend', 9],
        ['Gaming PC', 'Public Holiday', 5],
        ['Playstation 5 (PS5)', 'Weekday', 8],
        ['Playstation 5 (PS5)', 'Weekend', 10],
        ['Playstation 5 (PS5)', 'Public Holiday', 6],
        ['Nintendo Switch', 'Weekday', 8],
        ['Nintendo Switch', 'Weekend', 10],
        ['Nintendo Switch', 'Public Holiday', 6],
        ['Xbox Series X', 'Weekday', 8],
        ['Xbox Series X', 'Weekend', 10],
        ['Xbox Series X', 'Public Holiday', 6],
        ['Virtual Reality (VR) Pod', 'Weekday', 10],
        ['Virtual Reality (VR) Pod', 'Weekend', 12],
        ['Virtual Reality (VR) Pod', 'Public Holiday', 8],
    ];

    $consoleStmt = $pdo->prepare("SELECT console_type_id FROM console_type WHERE console_type_name = ?");
    $stationStmt = $pdo->prepare("SELECT station_id FROM station WHERE console_type_id = ? LIMIT 1");
    $rateCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM console_rate WHERE station_id = ? AND day_type = ?");

    foreach ($consoleRates as $rate) {
        $consoleStmt->execute([$rate[0]]);
        $consoleTypeId = $consoleStmt->fetchColumn();

        if ($consoleTypeId) {
            $stationStmt->execute([$consoleTypeId]);
            $stationId = $stationStmt->fetchColumn();

            if ($stationId) {
                $rateCheckStmt->execute([$stationId, $rate[1]]);
                if ($rateCheckStmt->fetchColumn() == 0) {
                    $insert = $pdo->prepare("INSERT INTO console_rate (station_id, day_type, hourly_rate, effective_from) VALUES (?,?,?,?)");
                    $insert->execute([$stationId, $rate[1], $rate[2], date('Y-m-d')]);
                    $success[] = "✓ Rate for '{$rate[0]}' ({$rate[1]}) set to RM{$rate[2]}/hr";
                }
            }
        }
    }

    // ===== SEED DEMO MEMBERS =====
    $members = [
        ['John Doe', 'john@example.com', '0101234567', 'Premium'],
        ['Jane Smith', 'jane@example.com', '0102345678', 'Standard'],
        ['Mike Johnson', 'mike@example.com', '0103456789', 'Casual'],
        ['Sarah Lee', 'sarah@example.com', '0104567890', 'Premium'],
        ['Alex Wong', 'alex@example.com', '0105678901', 'Standard'],
    ];

    $memberCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM member WHERE email = ?");
    foreach ($members as $m) {
        $memberCheckStmt->execute([$m[1]]);
        if ($memberCheckStmt->fetchColumn() == 0) {
            $joinDate = date('Y-m-d', strtotime('-' . rand(1, 180) . ' days'));
            $insert = $pdo->prepare("INSERT INTO member (name, email, phone, membership_type, join_date, status) VALUES (?,?,?,?,?,?)");
            $insert->execute([$m[0], $m[1], $m[2], $m[3], $joinDate, 'Active']);
            $success[] = "✓ Member '{$m[0]}' ({$m[3]}) created";
        } else {
            $success[] = "→ Member '{$m[0]}' already exists";
        }
    }

} catch (Exception $e) {
    $errors[] = "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder - Gaming Console Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            color: #e0e0e0;
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: linear-gradient(135deg, rgba(50,43,99,0.8) 0%, rgba(36,36,62,0.8) 100%);
            border: 2px solid rgba(138, 43, 226, 0.4);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
        }
        h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #00ff88 0%, #00d4ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            text-align: center;
        }
        .result {
            margin: 12px 0;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            border-left: 4px solid;
        }
        .success {
            background: rgba(0,255,136,0.1);
            border-left-color: #00ff88;
            color: #00ff88;
        }
        .error {
            background: rgba(255,0,110,0.1);
            border-left-color: #ff006e;
            color: #ff006e;
        }
        .info {
            background: rgba(0,212,255,0.1);
            border-left-color: #00d4ff;
            color: #00d4ff;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(138, 43, 226, 0.3);
            text-align: center;
            font-size: 13px;
            color: #a0a0a0;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #ff006e 0%, #8338ec 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(255, 0, 110, 0.6);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌱 Database Seeder</h1>

        <div class="result info">
            📋 Demo Data Setup for Gaming Console Hub
        </div>

        <?php foreach ($success as $msg): ?>
        <div class="result success"><?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>

        <?php foreach ($errors as $msg): ?>
        <div class="result error"><?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>

        <div class="footer">
            <p><strong>Demo Login Credentials:</strong></p>
            <p style="margin-top: 10px;">
                <strong>Admin:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">admin</code> / <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">admin123</code><br>
                <strong>Technician:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">tech1</code> / <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">admin123</code><br>
                <strong>Attendant:</strong> <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">attendant1</code> / <code style="background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">admin123</code>
            </p>
            <a href="login.php" class="btn">← Go to Login</a>
        </div>
    </div>
</body>
</html>
