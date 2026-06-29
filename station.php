<?php
require_once 'config.php';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

if ($action === 'delete') {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM console_rate WHERE station_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM maintenance WHERE station_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM payment WHERE booking_id IN (SELECT booking_id FROM booking WHERE station_id=?)")->execute([$id]);
        $pdo->prepare("DELETE FROM booking WHERE station_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM station WHERE station_id=?")->execute([$id]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Deletion failed: " . $e->getMessage());
    }
    header("Location: station.php?msg=Deleted");
    exit;
}

$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stationName = trim($_POST['station_name'] ?? '');
    $consoleTypeId = $_POST['console_type_id'] ?? 0;
    $hourlyRate = $_POST['hourly_rate'] ?? 0;

    // Check for duplicate station name
    if ($action === 'create') {
        $dupCheck = $pdo->prepare("SELECT COUNT(*) FROM station WHERE station_name=?");
        $dupCheck->execute([$stationName]);
    } else {
        $dupCheck = $pdo->prepare("SELECT COUNT(*) FROM station WHERE station_name=? AND station_id!=?");
        $dupCheck->execute([$stationName, $id]);
    }
    if ($dupCheck->fetchColumn() > 0) {
        $formError = 'Station name already exists. Please use a unique name.';
    }

    // Check max 5 stations per console type
    if (!$formError) {
        if ($action === 'create') {
            $check = $pdo->prepare("SELECT COUNT(*) FROM station WHERE console_type_id=?");
            $check->execute([$consoleTypeId]);
        } else {
            // On edit, count other stations of this type (exclude current)
            $check = $pdo->prepare("SELECT COUNT(*) FROM station WHERE console_type_id=? AND station_id!=?");
            $check->execute([$consoleTypeId, $id]);
        }
        if ($check->fetchColumn() >= 5) {
            $formError = 'Limit reached - maximum 5 stations per console type.';
        }
    }

    if (!$formError) {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO station (console_type_id, station_name, hourly_rate, status) VALUES (?,?,?,?)");
            $stmt->execute([$consoleTypeId, $stationName, $hourlyRate, 'Available']);
            header("Location: station.php?msg=Created");
            exit;
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE station SET console_type_id=?, station_name=?, hourly_rate=? WHERE station_id=?");
            $stmt->execute([$consoleTypeId, $stationName, $hourlyRate, $id]);
            header("Location: station.php?msg=Updated");
            exit;
        }
    }
}

require_once 'header.php';
$msg = $_GET['msg'] ?? '';
$consoleTypes = $pdo->query("SELECT ct.*, (SELECT COUNT(*) FROM station s WHERE s.console_type_id = ct.console_type_id) AS station_count FROM console_type ct ORDER BY ct.console_type_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="page-header">
    <h1>🖥️ Stations</h1>
    <a href="station.php?action=create" class="btn btn-primary">+ Add Station</a>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($formError): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($formError) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<?php
$consoleBranding = [
    'Gaming PC' => ['icon' => '🖥️', 'color' => '#3B82F6'],
    'Playstation 5 (PS5)' => ['icon' => '🎮', 'color' => '#22C55E'],
    'Xbox Series X' => ['icon' => '🎯', 'color' => '#F97316'],
    'Nintendo Switch' => ['icon' => '🕹️', 'color' => '#EC4899'],
    'Virtual Reality (VR) Pod' => ['icon' => '🥽', 'color' => '#8B5CF6'],
];

$groups = $pdo->query("
    SELECT ct.console_type_id, ct.console_type_name,
        s.station_id, s.station_name, s.hourly_rate, s.status,
        (SELECT COUNT(*) FROM booking b WHERE b.station_id = s.station_id AND b.status = 'Confirmed' AND b.booking_date >= CURDATE()) AS active_bookings,
        (SELECT COUNT(*) FROM maintenance m WHERE m.station_id = s.station_id AND m.status IN ('Scheduled','In Progress')) AS active_maintenance,
        (SELECT sr.hourly_rate FROM console_rate sr WHERE sr.station_id = s.station_id AND sr.day_type = 'Weekday' LIMIT 1) AS rate_weekday,
        (SELECT sr.hourly_rate FROM console_rate sr WHERE sr.station_id = s.station_id AND sr.day_type = 'Weekend' LIMIT 1) AS rate_weekend,
        (SELECT sr.hourly_rate FROM console_rate sr WHERE sr.station_id = s.station_id AND sr.day_type = 'Public Holiday' LIMIT 1) AS rate_ph
    FROM console_type ct
    LEFT JOIN station s ON ct.console_type_id = s.console_type_id
    ORDER BY ct.console_type_name ASC, s.station_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$grouped = [];
foreach ($groups as $r) {
    $typeName = $r['console_type_name'];
    if (!isset($grouped[$typeName])) {
        $grouped[$typeName] = ['id' => $r['console_type_id'], 'stations' => []];
    }
    if ($r['station_id']) {
        $grouped[$typeName]['stations'][] = $r;
    }
}

$totalStationsAll = $pdo->query("SELECT COUNT(*) FROM station")->fetchColumn();
$totalAvailable = $pdo->query("SELECT COUNT(*) FROM station WHERE status='Available'")->fetchColumn();
$totalUnavailable = $totalStationsAll - $totalAvailable;
$totalTypes = count($grouped);
$availPct = $totalStationsAll > 0 ? round($totalAvailable / $totalStationsAll * 100) : 0;
$unavailPct = 100 - $availPct;
?>
<!-- Hero row -->
<div class="st-hero">
    <div class="st-hero-card" style="background:linear-gradient(135deg,rgba(59,130,246,0.12),rgba(59,130,246,0.04));border-color:rgba(59,130,246,0.15);">
        <div class="st-hero-info">
            <span class="st-hero-label">Total Stations</span>
            <span class="st-hero-value"><?= $totalStationsAll ?></span>
            <span class="st-hero-sub">Across all console types</span>
        </div>
        <div class="st-hero-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
    </div>
    <div class="st-hero-card" style="background:linear-gradient(135deg,rgba(34,197,94,0.12),rgba(34,197,94,0.04));border-color:rgba(34,197,94,0.15);">
        <div class="st-hero-info">
            <span class="st-hero-label">Available</span>
            <span class="st-hero-value"><?= $totalAvailable ?></span>
            <span class="st-hero-sub"><?= $availPct ?>% of total stations</span>
        </div>
        <div class="st-hero-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
    </div>
    <div class="st-hero-card" style="background:linear-gradient(135deg,rgba(249,115,22,0.12),rgba(249,115,22,0.04));border-color:rgba(249,115,22,0.15);">
        <div class="st-hero-info">
            <span class="st-hero-label">Unavailable</span>
            <span class="st-hero-value"><?= $totalUnavailable ?></span>
            <span class="st-hero-sub">In maintenance or booked</span>
        </div>
        <div class="st-hero-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
    </div>
    <div class="st-hero-card" style="background:linear-gradient(135deg,rgba(139,92,246,0.12),rgba(139,92,246,0.04));border-color:rgba(139,92,246,0.15);">
        <div class="st-hero-info">
            <span class="st-hero-label">Console Types</span>
            <span class="st-hero-value"><?= $totalTypes ?></span>
            <span class="st-hero-sub">Platform categories</span>
        </div>
        <div class="st-hero-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#A78BFA" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="4"/><path d="M12 18V6"/><path d="M6 12h12"/></svg>
        </div>
    </div>
</div>

<!-- Console type sections -->
<div class="st-sections">
    <?php foreach ($grouped as $typeName => $group):
        $brand = $consoleBranding[$typeName] ?? ['icon' => '🖥️', 'color' => '#3B82F6'];
        $c = $brand['color'];
        $stationCount = count($group['stations']);
    ?>
    <div class="st-section">
        <div class="st-section-header">
            <div class="st-section-brand">
                <div class="st-section-logo" style="background:<?= $c ?>15; border-color:<?= $c ?>30;">
                    <?= $brand['icon'] ?>
                </div>
                <div>
                    <div class="st-section-title"><?= htmlspecialchars($typeName) ?></div>
                    <div class="st-section-meta"><?= $stationCount ?> station<?= $stationCount !== 1 ? 's' : '' ?></div>
                </div>
            </div>
            <a href="station.php?action=create" class="btn btn-sm" style="background:<?= $c ?>15;color:<?= $c ?>;border:1px solid <?= $c ?>25;">+ Add</a>
        </div>

        <?php if ($stationCount > 0): ?>
        <div class="st-grid">
            <?php foreach ($group['stations'] as $r):
                $autoStatus = ($r['active_bookings'] > 0 || $r['active_maintenance'] > 0) ? 'Unavailable' : 'Available';
                $wd = $r['rate_weekday'] ?? $r['hourly_rate'];
                $we = $r['rate_weekend'] ?? ($wd + 2);
                $ph = $r['rate_ph'] ?? max(0, $wd - 2);
            ?>
            <div class="st-card">
                <div class="st-card-top">
                    <span class="st-card-name"><?= htmlspecialchars($r['station_name']) ?></span>
                    <span class="st-card-badge st-badge-<?= strtolower($autoStatus) ?>"><?= $autoStatus ?></span>
                </div>
                <div class="st-card-body">
                    <div class="st-rates">
                        <div class="st-rate">
                            <span class="st-rate-label">WD</span>
                            <span class="st-rate-val">RM<?= number_format($wd, 2) ?></span>
                        </div>
                        <div class="st-rate st-rate-we">
                            <span class="st-rate-label">WE</span>
                            <span class="st-rate-val">RM<?= number_format($we, 2) ?></span>
                        </div>
                        <div class="st-rate st-rate-ph">
                            <span class="st-rate-label">PH</span>
                            <span class="st-rate-val">RM<?= number_format($ph, 2) ?></span>
                        </div>
                    </div>
                    <div class="st-stats">
                        <span>📅 <?= (int)$r['active_bookings'] ?> bookings</span>
                        <span>🔧 <?= (int)$r['active_maintenance'] ?> maint</span>
                    </div>
                </div>
                <div class="st-card-actions">
                    <a href="station.php?action=edit&id=<?= $r['station_id'] ?>" class="st-action st-action-edit">Edit</a>
                    <a href="station.php?action=delete&id=<?= $r['station_id'] ?>" class="st-action st-action-delete" onclick="return confirm('Delete <?= htmlspecialchars($r['station_name']) ?>?')">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<style>
.st-hero { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px; }
.st-hero-card {
    background: var(--surface, #18181B); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 20px; padding: 20px 24px;
    display: flex; justify-content: space-between; align-items: flex-start;
    transition: all 0.25s ease;
}
.st-hero-card:hover { transform: translateY(-2px); }
.st-hero-info { display: flex; flex-direction: column; gap: 4px; }
.st-hero-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; font-weight: 500; }
.st-hero-value { font-size: 28px; font-weight: 600; color: var(--text); line-height: 1.2; }
.st-hero-sub { font-size: 11px; color: var(--text-muted); }
.st-hero-icon { opacity: 0.7; flex-shrink: 0; }
.st-hero-icon svg { display: block; }

.st-sections { display: flex; flex-direction: column; gap: 20px; }
.st-section {
    background: var(--surface, #18181B);
    border: 1px solid var(--border, #27272A);
    border-radius: 20px; overflow: hidden;
}
.st-section-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,0.04);
}
.st-section-brand { display: flex; align-items: center; gap: 12px; }
.st-section-logo {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid; font-size: 16px;
}
.st-section-title { font-size: 16px; font-weight: 600; color: var(--text); }
.st-section-meta { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

.st-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; padding: 16px 24px; }

.st-card {
    background: rgba(255,255,255,0.015);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 14px; overflow: hidden;
    transition: all 0.2s ease;
}
.st-card:hover { border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.025); }

.st-card-top { padding: 14px 16px 8px; display: flex; justify-content: space-between; align-items: center; }
.st-card-name { font-size: 14px; font-weight: 500; color: var(--text); }
.st-card-badge { font-size: 9px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 8px; border-radius: 4px; }
.st-badge-available { background: rgba(34,197,94,0.1); color: #22C55E; }
.st-badge-unavailable { background: rgba(249,115,22,0.1); color: #F97316; }

.st-card-body { padding: 0 16px 10px; }

.st-rates { display: flex; gap: 4px; margin-bottom: 8px; }
.st-rate { flex: 1; text-align: center; padding: 4px; border-radius: 6px; background: rgba(255,255,255,0.03); }
.st-rate-we { background: rgba(249,115,22,0.05); }
.st-rate-ph { background: rgba(59,130,246,0.05); }
.st-rate-label { display: block; font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; color: var(--text-muted); }
.st-rate-val { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-top: 1px; }
.st-rate-we .st-rate-val { color: #F97316; }
.st-rate-ph .st-rate-val { color: #60A5FA; }

.st-stats { display: flex; justify-content: space-between; font-size: 10px; color: var(--text-muted); }

.st-card-actions { display: flex; border-top: 1px solid rgba(255,255,255,0.04); }
.st-action { flex: 1; text-align: center; padding: 7px; font-size: 10px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; color: var(--text-muted); }
.st-action-edit { border-right: 1px solid rgba(255,255,255,0.04); }
.st-action-edit:hover { color: #60A5FA; background: rgba(96,165,250,0.08); }
.st-action-delete:hover { color: #F87171; background: rgba(239,68,68,0.08); }

@media (max-width: 1024px) { .st-hero { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .st-hero { grid-template-columns: 1fr; }
    .st-grid { grid-template-columns: 1fr; }
    .st-section-header { flex-direction: column; gap: 10px; align-items: flex-start; }
}
<?php elseif ($action === 'create' || $action === 'edit'):
    $r = ['station_id'=>'','console_type_id'=>'','station_name'=>'','hourly_rate'=>''];
    $rateFromSr = '';
    if ($action === 'edit') {
        $r = $pdo->prepare("SELECT * FROM station WHERE station_id=?");
        $r->execute([$id]);
        $r = $r->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT hourly_rate FROM console_rate WHERE station_id=? ORDER BY effective_from DESC LIMIT 1");
        $stmt->execute([$id]);
        $rateFromSr = $stmt->fetchColumn();
    }
    $displayRate = $rateFromSr ?: ($r['hourly_rate'] ?: 0);
?>
<div class="form-card">
    <form method="post">
        <div class="form-group">
            <label for="station_name">Station Name</label>
            <input type="text" name="station_name" id="station_name" class="form-control" value="<?= htmlspecialchars($r['station_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="console_type_id">Console Type</label>
            <select name="console_type_id" id="console_type_id" class="form-control">
                <option value="">Select Console Type</option>
                <?php foreach ($consoleTypes as $ct):
                    $currentCount = (int)$ct['station_count'];
                    // On edit, exclude this station from the count
                    if ($action === 'edit' && $r['console_type_id'] == $ct['console_type_id']) {
                        $currentCount--;
                    }
                    $atLimit = $currentCount >= 5;
                ?>
                <option value="<?= $ct['console_type_id'] ?>" <?= $r['console_type_id']==$ct['console_type_id']?'selected':'' ?> <?= $atLimit ? 'disabled style="color:#ccc;"' : '' ?>>
                    <?= htmlspecialchars($ct['console_type_name']) ?> (<?= $currentCount ?>/5 stations<?= $action==='edit'&&$r['console_type_id']==$ct['console_type_id']?', current' : '' ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="hourly_rate">Hourly Rate (RM) <small style="color:#86868b;font-weight:400;">set in Rates page</small></label>
            <input type="text" id="hourly_rate" class="form-control" value="RM<?= number_format($displayRate, 2) ?>" readonly style="background:#f5f5f7;">
            <input type="hidden" name="hourly_rate" value="<?= $displayRate ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $action === 'create' ? 'Create Station' : 'Update Station' ?></button>
            <a href="station.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php if ($action === 'create'): ?>
<script>
(function() {
    var nameInput = document.getElementById('station_name');
    var typeSelect = document.getElementById('console_type_id');
    var keywords = {
        'Gaming PC': ['pc', 'gaming'],
        'Playstation 5 (PS5)': ['ps5', 'playstation', 'ps'],
        'Nintendo Switch': ['switch', 'nintendo'],
        'Xbox Series X': ['xbox', 'series'],
        'Virtual Reality (VR) Pod': ['vr', 'virtual', 'pod']
    };
    function autoDetectConsole() {
        var name = nameInput.value.toLowerCase();
        for (var i = 0; i < typeSelect.options.length; i++) {
            var opt = typeSelect.options[i];
            if (!opt.value) continue;
            var matches = keywords[opt.text.split(' (')[0]];
            if (matches && matches.some(function(k) { return name.indexOf(k) !== -1; })) {
                typeSelect.value = opt.value;
                return;
            }
        }
    }
    nameInput.addEventListener('input', autoDetectConsole);
})();
</script>
<?php endif; ?>
<?php endif; require_once 'footer.php'; ?>
