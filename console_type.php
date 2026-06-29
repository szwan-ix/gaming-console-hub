<?php
require_once 'config.php';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

if ($action === 'delete') {
    // Check if console type has associated stations
    $check = $pdo->prepare("SELECT COUNT(*) FROM station WHERE console_type_id=?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        die("Error: Cannot delete console type. Please delete all associated stations first.");
    }
    $pdo->prepare("DELETE FROM console_type WHERE console_type_id=?")->execute([$id]);
    header("Location: console_type.php?msg=Deleted");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['console_type_name'];
    $exists = $pdo->prepare("SELECT COUNT(*) FROM console_type WHERE console_type_name=? AND console_type_id!=?");
    $exists->execute([$name, $id]);
    if ($exists->fetchColumn() > 0) {
        $dupError = "Console type '$name' already exists!";
    } else {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO console_type (console_type_name) VALUES (?)");
            $stmt->execute([$name]);
            header("Location: console_type.php?msg=Created");
            exit;
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE console_type SET console_type_name=? WHERE console_type_id=?");
            $stmt->execute([$name, $id]);
            header("Location: console_type.php?msg=Updated");
            exit;
        }
    }
}

require_once 'header.php';
$msg = $_GET['msg'] ?? '';
?>
<div class="page-header">
    <h1>🎮 Console Types</h1>
    <a href="console_type.php?action=create" class="btn btn-primary">+ Add Console Type</a>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?> successfully!</div><?php endif; ?>
<?php if (isset($dupError)): ?><div class="alert alert-error"><?= htmlspecialchars($dupError) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<?php
$consoleBranding = [
    'Gaming PC' => ['icon' => '🖥️', 'color' => '#3B82F6', 'gradient' => 'rgba(59,130,246,']
];

$rows = $pdo->query("
    SELECT ct.*,
        (SELECT COUNT(*) FROM station s WHERE s.console_type_id = ct.console_type_id) AS station_count,
        (SELECT MIN(cr.hourly_rate) FROM console_rate cr JOIN station s ON cr.station_id = s.station_id WHERE s.console_type_id = ct.console_type_id) AS min_rate,
        (SELECT MAX(cr.hourly_rate) FROM console_rate cr JOIN station s ON cr.station_id = s.station_id WHERE s.console_type_id = ct.console_type_id) AS max_rate,
        (SELECT COUNT(*) FROM station s JOIN maintenance m ON s.station_id = m.station_id WHERE s.console_type_id = ct.console_type_id) AS maint_count
    FROM console_type ct ORDER BY ct.console_type_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$totalTypes = count($rows);
$totalStationsFromTypes = array_sum(array_column($rows, 'station_count'));
$totalMaintFromTypes = array_sum(array_column($rows, 'maint_count'));

$consoleBranding = [
    'Gaming PC' => ['icon' => '🖥️', 'color' => '#4f7cff'],
    'Playstation 5 (PS5)' => ['icon' => '🎮', 'color' => '#3dd68c'],
    'Xbox Series X' => ['icon' => '🎯', 'color' => '#f59e5c'],
    'Nintendo Switch' => ['icon' => '🕹️', 'color' => '#ff5c7c'],
    'Virtual Reality (VR) Pod' => ['icon' => '🥽', 'color' => '#f0c94b'],
];
?>
<div class="ct-hero">
    <div class="ct-hero-card" style="background:linear-gradient(135deg,rgba(59,130,246,0.15),rgba(59,130,246,0.05));border-color:rgba(59,130,246,0.2);">
        <div class="ct-hero-info">
            <span class="ct-hero-label">Console Types</span>
            <span class="ct-hero-value"><?= $totalTypes ?></span>
            <span class="ct-hero-sub">Available platform categories</span>
        </div>
        <div class="ct-hero-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="4"/><path d="M12 18V6"/><path d="M6 12h12"/></svg>
        </div>
    </div>
    <div class="ct-hero-card" style="background:linear-gradient(135deg,rgba(34,197,94,0.15),rgba(34,197,94,0.05));border-color:rgba(34,197,94,0.2);">
        <div class="ct-hero-info">
            <span class="ct-hero-label">Total Stations</span>
            <span class="ct-hero-value"><?= $totalStationsFromTypes ?></span>
            <span class="ct-hero-sub">Across all console types</span>
        </div>
        <div class="ct-hero-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
    </div>
    <div class="ct-hero-card" style="background:linear-gradient(135deg,rgba(249,115,22,0.15),rgba(249,115,22,0.05));border-color:rgba(249,115,22,0.2);">
        <div class="ct-hero-info">
            <span class="ct-hero-label">Maintenance</span>
            <span class="ct-hero-value"><?= $totalMaintFromTypes ?></span>
            <span class="ct-hero-sub">Total maintenance records</span>
        </div>
        <div class="ct-hero-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </div>
    </div>
    <div class="ct-hero-card" style="background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(139,92,246,0.05));border-color:rgba(139,92,246,0.2);">
        <div class="ct-hero-info">
            <span class="ct-hero-label">Max Stations</span>
            <span class="ct-hero-value">5</span>
            <span class="ct-hero-sub">Per console type limit</span>
        </div>
        <div class="ct-hero-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
    </div>
</div>

<div class="ct-grid">
    <?php foreach ($rows as $i => $r):
        $brand = $consoleBranding[$r['console_type_name']] ?? ['icon' => '🖥️', 'color' => '#4f7cff'];
        $c = $brand['color'];
        $stationPct = $r['station_count'] > 0 ? round($r['station_count'] / 5 * 100) : 0;
    ?>
    <div class="ct-card" style="border-color: rgba(255,255,255,0.08);">
        <div class="ct-card-top" style="background:linear-gradient(135deg, <?= $c ?>15, <?= $c ?>08);">
            <span class="ct-card-icon" style="background:<?= $c ?>20; color:<?= $c ?>;"><?= $brand['icon'] ?></span>
            <div>
                <span class="ct-card-title"><?= htmlspecialchars($r['console_type_name']) ?></span>
                <span class="ct-card-sub"><?= $r['station_count'] ?> station<?= $r['station_count'] !== 1 ? 's' : '' ?> configured</span>
            </div>
            <div class="ct-card-actions">
                <a href="console_type.php?action=edit&id=<?= $r['console_type_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                <a href="console_type.php?action=delete&id=<?= $r['console_type_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this console type?')">Delete</a>
            </div>
        </div>
        <div class="ct-card-body">
            <div class="ct-metric">
                <span class="ct-metric-label">Stations</span>
                <span class="ct-metric-value"><?= $r['station_count'] ?>/5</span>
                <div class="ct-bar-bg">
                    <div class="ct-bar-fill" style="width:<?= $stationPct ?>%; background:<?= $c ?>;"></div>
                </div>
            </div>
            <div class="ct-metric">
                <span class="ct-metric-label">Hourly Rate</span>
                <span class="ct-metric-value"><?= $r['min_rate'] ? 'RM'.number_format($r['min_rate'],2).' - RM'.number_format($r['max_rate'],2) : 'Not set' ?></span>
            </div>
            <div class="ct-metric">
                <span class="ct-metric-label">Maintenance</span>
                <span class="ct-metric-value"><?= $r['maint_count'] ?> record<?= $r['maint_count'] !== 1 ? 's' : '' ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.ct-hero {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
.ct-hero-card {
    border-radius: 24px;
    padding: 22px 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    transition: all 0.3s ease;
    border: 1px solid rgba(255,255,255,0.06);
    background: var(--surface);
}
.ct-hero-card:hover { transform: translateY(-2px); }
.ct-hero-info { display: flex; flex-direction: column; gap: 4px; }
.ct-hero-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; font-weight: 500; }
.ct-hero-value { font-size: 28px; font-weight: 600; color: var(--text); line-height: 1.2; }
.ct-hero-sub { font-size: 11px; color: var(--text-muted); }
.ct-hero-icon { opacity: 0.5; flex-shrink: 0; }
.ct-hero-icon svg { display: block; }

.ct-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
.ct-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    overflow: hidden;
    transition: all 0.2s ease;
}
.ct-card:hover { border-color: rgba(255,255,255,0.12); }
.ct-card-top {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.ct-card-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.ct-card-title { font-size: 16px; font-weight: 600; color: var(--text); display: block; }
.ct-card-sub { font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px; }
.ct-card-actions { margin-left: auto; display: flex; gap: 6px; flex-shrink: 0; }
.ct-card-body { padding: 0 24px 20px; display: flex; flex-direction: column; gap: 14px; }
.ct-metric { display: flex; align-items: center; gap: 12px; }
.ct-metric-label { font-size: 11px; color: var(--text-muted); flex: 1; }
.ct-metric-value { font-size: 13px; font-weight: 500; color: var(--text); }
.ct-bar-bg { width: 80px; height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; overflow: hidden; }
.ct-bar-fill { height: 100%; border-radius: 2px; transition: width 0.5s ease; }

@media (max-width: 1024px) {
    .ct-hero { grid-template-columns: repeat(2, 1fr); }
    .ct-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .ct-hero { grid-template-columns: 1fr; }
    .ct-card-top { flex-wrap: wrap; }
    .ct-card-actions { margin-left: 0; width: 100%; }
}
</style>
<?php elseif ($action === 'create' || $action === 'edit'):
    $r = ['console_type_id'=>'','console_type_name'=>''];
    if ($action === 'edit') {
        $r = $pdo->prepare("SELECT * FROM console_type WHERE console_type_id=?");
        $r->execute([$id]);
        $r = $r->fetch(PDO::FETCH_ASSOC);
    }
?>
<div class="form-card">
    <form method="post">
        <div class="form-group">
            <label for="console_type_name">Console Type Name</label>
            <select name="console_type_name" id="console_type_name" class="form-control" required>
                <option value="">Select Console Type</option>
                <option value="Gaming PC" <?= $r['console_type_name']==='Gaming PC'?'selected':'' ?>>Gaming PC</option>
                <option value="Playstation 5 (PS5)" <?= $r['console_type_name']==='Playstation 5 (PS5)'?'selected':'' ?>>Playstation 5 (PS5)</option>
                <option value="Nintendo Switch" <?= $r['console_type_name']==='Nintendo Switch'?'selected':'' ?>>Nintendo Switch</option>
                <option value="Xbox Series X" <?= $r['console_type_name']==='Xbox Series X'?'selected':'' ?>>Xbox Series X</option>
                <option value="Virtual Reality (VR) Pod" <?= $r['console_type_name']==='Virtual Reality (VR) Pod'?'selected':'' ?>>Virtual Reality (VR) Pod</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $action === 'create' ? 'Create Console Type' : 'Update Console Type' ?></button>
            <a href="console_type.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; require_once 'footer.php'; ?>
