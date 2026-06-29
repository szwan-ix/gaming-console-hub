<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Console Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== Project Fjord Design System ===== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #02040A;
            --secondary: #FFFFFF;
            --accent: #02040A;
            --bg: #02040A;
            --surface: #18181B;
            --surface-lg: #1a1a1e;
            --border: #27272A;
            --border-light: rgba(255,255,255,0.06);
            --text: #FFFFFF;
            --text-secondary: #A1A1AA;
            --text-muted: #71717A;
            --green: #22C55E;
            --green-soft: rgba(34,197,94,0.12);
            --orange: #F97316;
            --orange-soft: rgba(249,115,22,0.12);
            --pink: #EC4899;
            --pink-soft: rgba(236,72,153,0.12);
            --gold: #F59E0B;
            --gold-soft: rgba(245,158,11,0.12);
            --blue-soft: rgba(255,255,255,0.06);
            --sidebar-w: 0px;
            --sidebar-we: 0px;
            --radius: 24px;
            --radius-sm: 23px;
            --radius-pill: 9999px;
            --font-display: 'Inter', sans-serif;
            --font-body: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --shadow: 0 4px 24px rgba(0,0,0,0.4);
        }

        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        ::selection { background: rgba(255,255,255,0.1); color: var(--text); }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        /* ===== SIDEBAR (Hover Expand) ===== */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: 64px;
            height: 100vh; z-index: 100;
            background: var(--surface);
            border-right: 1px solid rgba(255,255,255,0.04);
            display: flex; flex-direction: column;
            transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }

        .sidebar:hover { width: 220px; }

        .sidebar-brand {
            display: flex; align-items: center; gap: 12px;
            padding: 20px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            white-space: nowrap; overflow: hidden;
        }

        .sidebar-brand-icon { font-size: 20px; width: 36px; flex-shrink: 0; text-align: center; }

        .sidebar-brand-name {
            font-size: 14px; font-weight: 600;
            color: var(--text); letter-spacing: -0.2px;
        }

        .sidebar-nav {
            flex: 1; overflow-y: auto; overflow-x: hidden;
            padding: 8px; display: flex; flex-direction: column; gap: 2px;
        }

        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px; border-radius: 8px;
            text-decoration: none; color: var(--text-muted);
            font-size: 13px; font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap; overflow: hidden;
            min-height: 40px; position: relative;
        }

        .nav-item:hover { color: var(--text-secondary); background: rgba(255,255,255,0.04); }

        .nav-item.active {
            color: #fff;
            background: rgba(139,92,246,0.15);
        }

        .nav-item.active::before {
            content: '';
            position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 20px; border-radius: 3px;
            background: #8B5CF6;
        }

        .nav-item .nav-icon { font-size: 16px; width: 24px; text-align: center; flex-shrink: 0; }

        .nav-item .nav-label { opacity: 0; transition: opacity 0.2s ease; }
        .sidebar:hover .nav-item .nav-label { opacity: 1; transition: opacity 0.2s ease 0.1s; }

        .sidebar-footer {
            padding: 10px; border-top: 1px solid rgba(255,255,255,0.04);
            flex-shrink: 0; overflow: hidden;
        }

        .sidebar-user {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 8px; margin-bottom: 6px;
            border-radius: 8px; overflow: hidden; white-space: nowrap;
        }

        .sidebar-user-avatar {
            width: 32px; height: 32px; border-radius: 8px;
            background: rgba(139,92,246,0.15);
            border: 1px solid rgba(139,92,246,0.25);
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 13px; color: #fff; flex-shrink: 0;
        }

        .sidebar-user-info {
            display: flex; flex-direction: column; gap: 1px;
            min-width: 0; opacity: 0; transition: opacity 0.2s ease;
        }
        .sidebar:hover .sidebar-user-info { opacity: 1; transition: opacity 0.2s ease 0.1s; }

        .sidebar-user-name {
            font-size: 12px; font-weight: 500; color: var(--text);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .sidebar-user-role {
            font-size: 9px; font-weight: 500; text-transform: uppercase;
            letter-spacing: 0.4px; padding: 1px 6px; border-radius: 3px;
            display: inline-block; width: fit-content;
        }

        .sidebar-user-role.admin { background: rgba(139,92,246,0.15); color: #A78BFA; }
        .sidebar-user-role.technician { background: rgba(34,197,94,0.1); color: #22C55E; }
        .sidebar-user-role.attendant { background: rgba(249,115,22,0.1); color: #F97316; }

        .sidebar-logout {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 12px; border-radius: 8px;
            text-decoration: none; font-size: 11px; font-weight: 500;
            color: var(--text-muted);
            transition: all 0.2s ease;
            white-space: nowrap; overflow: hidden;
        }

        .sidebar-logout:hover { color: #F87171; background: rgba(239,68,68,0.08); }

        .sidebar-logout .nav-label { opacity: 0; transition: opacity 0.2s ease; }
        .sidebar:hover .sidebar-logout .nav-label { opacity: 1; transition: opacity 0.2s ease 0.1s; }

        /* ===== MAIN CONTENT ===== */
        .container {
            margin-left: 64px;
            padding: 28px 32px 60px;
            min-height: 100vh;
            position: relative; z-index: 1;
        }

        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 32px; gap: 16px;
        }

        .page-header h1 {
            font-size: 28px; font-weight: 600;
            font-family: var(--font-display);
            color: var(--text); letter-spacing: -0.3px;
        }

        /* ===== BUTTONS (Fjord: 23px radius) ===== */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 6px; padding: 10px 24px;
            border-radius: var(--radius-sm);
            font-family: var(--font-body);
            font-size: 12px; font-weight: 500;
            text-decoration: none; cursor: pointer; border: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: rgba(255,255,255,0.08);
            color: var(--text);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-primary:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.2);
        }

        .btn-secondary {
            background: rgba(59,130,246,0.1);
            color: #60A5FA;
            border: 1px solid rgba(59,130,246,0.2);
        }
        .btn-secondary:hover { background: rgba(59,130,246,0.18); color: #93C5FD; }

        .btn-danger {
            background: rgba(239,68,68,0.1);
            color: #F87171;
            border: 1px solid rgba(239,68,68,0.2);
        }
        .btn-danger:hover { background: rgba(239,68,68,0.18); color: #FCA5A5; }

        .btn-sm { padding: 6px 16px; font-size: 11px; border-radius: 20px; }

        /* ===== CARDS (Fjord: 24px radius) ===== */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        /* ===== TABLES (Fjord clean) ===== */
        table { width: 100%; border-collapse: collapse; font-size: 13px; }

        th {
            text-align: left; padding: 14px 18px;
            font-weight: 500; color: var(--text-muted);
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
            font-family: var(--font-mono);
            border-bottom: 1px solid var(--border);
        }

        td { padding: 14px 18px; border-bottom: 1px solid var(--border); color: var(--text); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.015); }

        /* ===== BADGES (Fjord: 23px pill radius) ===== */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 12px; border-radius: var(--radius-sm);
            font-size: 10px; font-weight: 500;
            font-family: var(--font-mono);
        }

        .badge-active, .badge-available, .badge-confirmed, .badge-paid, .badge-completed { background: var(--green-soft); color: var(--green); }
        .badge-inactive, .badge-unavailable, .badge-cancelled, .badge-unpaid, .badge-scheduled, .badge-expired { background: var(--orange-soft); color: var(--orange); }
        .badge-in-progress { background: rgba(255,255,255,0.06); color: var(--text-secondary); }
        .badge-refunded { background: var(--pink-soft); color: var(--pink); }
        .badge-premium { background: var(--gold-soft); color: var(--gold); }
        .badge-standard { background: rgba(255,255,255,0.06); color: var(--text-secondary); }
        .badge-casual { background: var(--green-soft); color: var(--green); }
        .badge-admin { background: rgba(255,255,255,0.06); color: var(--text-secondary); }
        .badge-technician { background: var(--green-soft); color: var(--green); }
        .badge-attendant { background: var(--orange-soft); color: var(--orange); }

        .action-group { display: flex; gap: 6px; flex-wrap: wrap; }

        .empty-state { text-align: center; padding: 60px 30px; color: var(--text-muted); }
        .empty-state p { font-size: 14px; font-weight: 400; }

        /* ===== FORMS (Fjord) ===== */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 32px;
            max-width: 680px;
            margin: 0 auto;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block; font-size: 11px; font-weight: 500;
            color: var(--text-muted); margin-bottom: 6px;
            font-family: var(--font-mono);
            text-transform: uppercase; letter-spacing: 0.4px;
        }

        .form-control {
            width: 100%; padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: var(--font-body);
            font-size: 13px;
            background: var(--bg);
            color: var(--text);
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.1);
        }

        .form-control::placeholder { color: var(--text-muted); }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%2371717A' d='M5 7L1 3h8z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }

        .form-actions { display: flex; gap: 10px; margin-top: 24px; flex-wrap: wrap; }

        .alert {
            padding: 12px 18px; border-radius: 6px;
            margin-bottom: 20px; font-size: 12px; font-weight: 400;
            animation: alertIn 0.25s ease-out;
        }

        @keyframes alertIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

        .alert-success { background: var(--green-soft); color: var(--green); }
        .alert-error { background: var(--pink-soft); color: var(--pink); }

        /* ===== DASHBOARD (Fjord Enhanced) ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px; margin-bottom: 32px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 2px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:nth-child(1)::before { background: linear-gradient(90deg, #60A5FA, #3B82F6); }
        .stat-card:nth-child(2)::before { background: linear-gradient(90deg, #34D399, #10B981); }
        .stat-card:nth-child(3)::before { background: linear-gradient(90deg, #FBBF24, #F59E0B); }
        .stat-card:nth-child(4)::before { background: linear-gradient(90deg, #F472B6, #EC4899); }
        .stat-card:nth-child(5)::before { background: linear-gradient(90deg, #60A5FA, #3B82F6); }
        .stat-card:nth-child(6)::before { background: linear-gradient(90deg, #A78BFA, #8B5CF6); }
        .stat-card:nth-child(7)::before { background: linear-gradient(90deg, #FB923C, #F97316); }
        .stat-card:nth-child(8)::before { background: linear-gradient(90deg, #34D399, #10B981); }

        .stat-card:hover {
            border-color: rgba(255,255,255,0.15);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.05);
            transform: translateY(-2px);
        }

        .stat-card:hover::before { opacity: 1; }

        .stat-card-content { display: flex; align-items: center; gap: 16px; }

        .stat-card .stat-icon {
            font-size: 22px; flex-shrink: 0;
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s ease;
        }

        .stat-card:nth-child(1) .stat-icon { background: rgba(96,165,250,0.12); }
        .stat-card:nth-child(2) .stat-icon { background: rgba(52,211,153,0.12); }
        .stat-card:nth-child(3) .stat-icon { background: rgba(251,191,36,0.12); }
        .stat-card:nth-child(4) .stat-icon { background: rgba(244,114,182,0.12); }
        .stat-card:nth-child(5) .stat-icon { background: rgba(96,165,250,0.12); }
        .stat-card:nth-child(6) .stat-icon { background: rgba(167,139,250,0.12); }
        .stat-card:nth-child(7) .stat-icon { background: rgba(251,146,60,0.12); }
        .stat-card:nth-child(8) .stat-icon { background: rgba(52,211,153,0.12); }

        .stat-card:hover .stat-icon {
            transform: scale(1.05);
        }

        .stat-card .stat-number {
            font-size: 26px; font-weight: 500; color: var(--text);
            margin-bottom: 2px; line-height: 1.2;
        }

        .stat-card .stat-label {
            font-size: 11px; color: var(--text-muted);
            font-weight: 400;
        }

        .section-title {
            font-size: 18px; font-weight: 500;
            font-family: var(--font-display);
            color: var(--text);
            margin: 28px 0 16px;
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .container { margin-left: 0; padding: 20px 16px 60px; padding-top: 76px; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 22px; }
            .page-header { flex-direction: column; gap: 10px; align-items: flex-start; }
            .form-card { padding: 20px; }
            .form-row { grid-template-columns: 1fr; }
            table { font-size: 11px; }
            th, td { padding: 10px 10px; }
            .stat-card { padding: 18px 16px; }
        }
    </style>
</head>
<body>
    <?php
    // Determine current page for active nav highlighting
    $currentPage = basename($_SERVER['PHP_SELF']);
    $navItems = [
        'Dashboard'   => ['file' => 'index.php',           'icon' => '📊'],
        'Members'     => ['file' => 'member.php',          'icon' => '👤'],
        'Consoles'    => ['file' => 'console_type.php',    'icon' => '🎮'],
        'Stations'    => ['file' => 'station.php',         'icon' => '🖥️'],
        'Rates'       => ['file' => 'console_rate.php',    'icon' => '💵'],
        'Bookings'    => ['file' => 'booking.php',         'icon' => '📅'],
        'Staff'       => ['file' => 'staff.php',           'icon' => '👨‍💼'],
        'Maintenance' => ['file' => 'maintenance.php',     'icon' => '🔧'],
    ];
    $roleKey = strtolower(htmlspecialchars($_SESSION['role'] ?? ''));
    $userName = htmlspecialchars($_SESSION['username'] ?? 'User');
    $userInitial = strtoupper(substr($userName, 0, 1));
    ?>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="sidebar-brand-icon">🎮</span>
            <span class="sidebar-brand-name">Gaming Console Hub</span>
        </div>

        <nav class="sidebar-nav">
            <?php foreach ($navItems as $label => $item):
                $active = ($currentPage === $item['file']) ? ' active' : '';
            ?>
            <a href="<?= $item['file'] ?>" class="nav-item<?= $active ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <span class="nav-label"><?= $label ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?= $userInitial ?></div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?= $userName ?></span>
                    <span class="sidebar-user-role <?= $roleKey ?>"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
                </div>
            </div>
            <a href="header.php?action=logout" class="sidebar-logout">
                <span class="nav-icon" style="font-size:16px;">🚪</span>
                <span class="nav-label">Logout</span>
            </a>
        </div>
    </aside>

    <div class="container">
