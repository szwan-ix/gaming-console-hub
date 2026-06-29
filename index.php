<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';
require_once 'header.php';

$stats = [];
$tables = ['member', 'console_type', 'station', 'console_rate', 'booking', 'payment', 'staff', 'maintenance'];
foreach ($tables as $t) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM " . $t);
    $stats[$t] = $stmt->fetchColumn();
}
$bookingsToday = $pdo->query("SELECT COUNT(*) FROM booking WHERE booking_date = CURDATE()")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payment WHERE payment_status = 'Paid'")->fetchColumn();
$totalBookings = $stats['booking'];
$totalMembers = $stats['member'];
$totalStations = $stats['station'];
$activeBookings = $pdo->query("SELECT COUNT(*) FROM booking WHERE status='Confirmed'")->fetchColumn();
$completedBookings = $pdo->query("SELECT COUNT(*) FROM booking WHERE status='Completed'")->fetchColumn();
$cancelledBookings = $pdo->query("SELECT COUNT(*) FROM booking WHERE status='Cancelled'")->fetchColumn();
$totalBookingsForChart = max(1, $activeBookings + $completedBookings + $cancelledBookings);
$activePct = round($activeBookings / $totalBookingsForChart * 100);
$completedPct = round($completedBookings / $totalBookingsForChart * 100);
$cancelledPct = 100 - $activePct - $completedPct;
?>
<div class="page-header">
    <h1>Dashboard</h1>
    <div class="page-header-actions">
        <a href="member.php?action=create" class="btn btn-primary">+ Add Member</a>
        <a href="booking.php?action=create" class="btn btn-primary">+ Add Booking</a>
    </div>
</div>

<!-- Hero stat row -->
<div class="dash-hero">
    <div class="dash-hero-card">
        <div class="dash-hero-info">
            <span class="dash-hero-label">Total Revenue</span>
            <span class="dash-hero-value">RM<?= number_format($totalRevenue, 2) ?></span>
            <span class="dash-hero-sub">Lifetime earnings from all bookings</span>
        </div>
        <div class="dash-hero-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>
    <div class="dash-hero-card dash-hero-card-green">
        <div class="dash-hero-info">
            <span class="dash-hero-label">Bookings Today</span>
            <span class="dash-hero-value"><?= $bookingsToday ?></span>
            <span class="dash-hero-sub">Sessions scheduled for today</span>
        </div>
        <div class="dash-hero-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
    </div>
    <div class="dash-hero-card dash-hero-card-orange">
        <div class="dash-hero-info">
            <span class="dash-hero-label">Active Members</span>
            <span class="dash-hero-value"><?= $totalMembers ?></span>
            <span class="dash-hero-sub">Registered members across all tiers</span>
        </div>
        <div class="dash-hero-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
    </div>
    <div class="dash-hero-card dash-hero-card-pink">
        <div class="dash-hero-info">
            <span class="dash-hero-label">Total Stations</span>
            <span class="dash-hero-value"><?= $totalStations ?></span>
            <span class="dash-hero-sub">Gaming stations across all types</span>
        </div>
        <div class="dash-hero-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
    </div>
</div>

<!-- Secondary stats + chart row -->
<div class="dash-row">
    <div class="dash-stats-grid">
        <div class="dash-stat">
            <span class="dash-stat-icon">📊</span>
            <div>
                <span class="dash-stat-value"><?= $totalBookings ?></span>
                <span class="dash-stat-label">Total Bookings</span>
            </div>
        </div>
        <div class="dash-stat">
            <span class="dash-stat-icon">🎮</span>
            <div>
                <span class="dash-stat-value"><?= $stats['console_type'] ?></span>
                <span class="dash-stat-label">Console Types</span>
            </div>
        </div>
        <div class="dash-stat">
            <span class="dash-stat-icon">👨‍💼</span>
            <div>
                <span class="dash-stat-value"><?= $stats['staff'] ?></span>
                <span class="dash-stat-label">Staff Members</span>
            </div>
        </div>
        <div class="dash-stat">
            <span class="dash-stat-icon">🔧</span>
            <div>
                <span class="dash-stat-value"><?= $stats['maintenance'] ?></span>
                <span class="dash-stat-label">Maintenance</span>
            </div>
        </div>
    </div>

    <!-- Donut chart -->
    <div class="dash-chart-card">
        <div class="dash-chart-header">
            <span class="dash-chart-title">Booking Status</span>
            <span class="dash-chart-sub">Distribution overview</span>
        </div>
        <div class="dash-chart-body">
            <div class="dash-donut" style="background: conic-gradient(#22C55E 0% <?= $completedPct ?>%, #60A5FA <?= $completedPct ?>% <?= $completedPct+$activePct ?>%, #F97316 <?= $completedPct+$activePct ?>% 100%);">
                <div class="dash-donut-hole">
                    <span class="dash-donut-value"><?= $totalBookings ?></span>
                    <span class="dash-donut-label">Total</span>
                </div>
            </div>
            <div class="dash-chart-legend">
                <div class="dash-legend-item">
                    <span class="dash-legend-dot" style="background:#22C55E;"></span>
                    <span class="dash-legend-label">Completed</span>
                    <span class="dash-legend-value"><?= $completedBookings ?></span>
                </div>
                <div class="dash-legend-item">
                    <span class="dash-legend-dot" style="background:#60A5FA;"></span>
                    <span class="dash-legend-label">Active</span>
                    <span class="dash-legend-value"><?= $activeBookings ?></span>
                </div>
                <div class="dash-legend-item">
                    <span class="dash-legend-dot" style="background:#F97316;"></span>
                    <span class="dash-legend-label">Cancelled</span>
                    <span class="dash-legend-value"><?= $cancelledBookings ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="dash-section-header">
    <h2 class="section-title">Recent Bookings</h2>
    <a href="booking.php" class="btn btn-primary btn-sm">View All</a>
</div>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Station</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recent = $pdo->query("
                SELECT b.*, m.name AS member_name, s.station_name
                FROM booking b
                LEFT JOIN member m ON b.member_id = m.member_id
                LEFT JOIN station s ON b.station_id = s.station_id
                ORDER BY b.booking_date DESC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC);
            if ($recent):
                foreach ($recent as $r):
            ?>
            <tr>
                <td><?= $r['booking_id'] ?></td>
                <td><?= htmlspecialchars($r['member_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['station_name'] ?? '-') ?></td>
                <td><?= $r['booking_date'] ?></td>
                <td><?= $r['start_time'] ?> - <?= $r['end_time'] ?></td>
                <td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $r['status'])) ?>"><?= $r['status'] ?></span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="empty-state"><p>No bookings yet.</p></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
/* ===== Dashboard Layout ===== */
.dash-hero {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}

.dash-hero-card {
    background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(59,130,246,0.05));
    border: 1px solid rgba(59,130,246,0.2);
    border-radius: 24px;
    padding: 22px 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: all 0.3s ease;
}

.dash-hero-card-green { background: linear-gradient(135deg, rgba(34,197,94,0.15), rgba(34,197,94,0.05)); border-color: rgba(34,197,94,0.2); }
.dash-hero-card-orange { background: linear-gradient(135deg, rgba(249,115,22,0.15), rgba(249,115,22,0.05)); border-color: rgba(249,115,22,0.2); }
.dash-hero-card-pink { background: linear-gradient(135deg, rgba(236,72,153,0.15), rgba(236,72,153,0.05)); border-color: rgba(236,72,153,0.2); }

.dash-hero-card:hover { transform: translateY(-2px); }

.dash-hero-info { display: flex; flex-direction: column; gap: 4px; }
.dash-hero-label { font-size: 11px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px; }
.dash-hero-value { font-size: 28px; font-weight: 600; color: var(--text); line-height: 1.2; }
.dash-hero-sub { font-size: 11px; color: var(--text-muted); }
.dash-hero-icon { opacity: 0.5; flex-shrink: 0; }
.dash-hero-icon svg { display: block; }

/* Row with mini stats + chart */
.dash-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 28px;
}

.dash-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.dash-stat {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 22px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
}

.dash-stat:hover { border-color: rgba(255,255,255,0.12); }

.dash-stat-icon { font-size: 22px; opacity: 0.6; }

.dash-stat-value { font-size: 22px; font-weight: 600; color: var(--text); display: block; line-height: 1.2; }
.dash-stat-label { font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px; }

/* Donut chart card */
.dash-chart-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 22px 24px;
}

.dash-chart-header { margin-bottom: 20px; }
.dash-chart-title { font-size: 14px; font-weight: 500; color: var(--text); display: block; }
.dash-chart-sub { font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px; }

.dash-chart-body {
    display: flex;
    align-items: center;
    gap: 28px;
}

.dash-donut {
    width: 120px; height: 120px;
    border-radius: 50%;
    flex-shrink: 0;
    position: relative;
}

.dash-donut-hole {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 72px; height: 72px;
    border-radius: 50%;
    background: var(--surface);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.dash-donut-value { font-size: 20px; font-weight: 600; color: var(--text); line-height: 1; }
.dash-donut-label { font-size: 10px; color: var(--text-muted); margin-top: 2px; }

.dash-chart-legend { display: flex; flex-direction: column; gap: 12px; flex: 1; }

.dash-legend-item {
    display: flex; align-items: center; gap: 10px;
}

.dash-legend-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}

.dash-legend-label { font-size: 12px; color: var(--text-secondary); flex: 1; }
.dash-legend-value { font-size: 13px; font-weight: 500; color: var(--text); }

.dash-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.dash-section-header .section-title { margin: 0; }

.page-header-actions { display: flex; gap: 8px; }

@media (max-width: 1024px) {
    .dash-hero { grid-template-columns: repeat(2, 1fr); }
    .dash-row { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .dash-hero { grid-template-columns: 1fr; }
    .dash-stats-grid { grid-template-columns: 1fr; }
    .dash-chart-body { flex-direction: column; align-items: center; }
}
</style>

<?php require_once 'footer.php'; ?>
