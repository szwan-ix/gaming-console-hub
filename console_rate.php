<?php
require_once 'config.php';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

if ($action === 'delete') {
    $stmt = $pdo->prepare("SELECT station_id FROM console_rate WHERE rate_id=?");
    $stmt->execute([$id]);
    $stationId = $stmt->fetchColumn();
    $ctStmt = $pdo->prepare("SELECT console_type_id FROM station WHERE station_id=?");
    $ctStmt->execute([$stationId]);
    $consoleTypeId = $ctStmt->fetchColumn();
    $pdo->prepare("DELETE FROM console_rate WHERE rate_id=?")->execute([$id]);
    header("Location: console_rate.php?msg=Deleted");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consoleTypeId = $_POST['console_type_id'];
    $dayType = $_POST['day_type'];
    $hourlyRate = $_POST['hourly_rate'];
    $effectiveFrom = $_POST['effective_from'];

    $stations = $pdo->prepare("SELECT station_id FROM station WHERE console_type_id=?");
    $stations->execute([$consoleTypeId]);
    $allStationIds = $stations->fetchAll(PDO::FETCH_COLUMN);

    if ($action === 'create') {
        foreach ($allStationIds as $sid) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM console_rate WHERE station_id=? AND day_type=?");
            $check->execute([$sid, $dayType]);
            if ($check->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO console_rate (station_id, day_type, hourly_rate, effective_from) VALUES (?,?,?,?)");
                $stmt->execute([$sid, $dayType, $hourlyRate, $effectiveFrom]);
            }
        }
        header("Location: console_rate.php?msg=Created");
        exit;
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("SELECT station_id FROM console_rate WHERE rate_id=?");
        $stmt->execute([$id]);
        $origStationId = $stmt->fetchColumn();
        $ctStmt = $pdo->prepare("SELECT console_type_id FROM station WHERE station_id=?");
        $ctStmt->execute([$origStationId]);
        $origConsoleTypeId = $ctStmt->fetchColumn();

        if ($origConsoleTypeId != $consoleTypeId) {
            $newStations = $pdo->prepare("SELECT station_id FROM station WHERE console_type_id=?");
            $newStations->execute([$consoleTypeId]);
            $newIds = $newStations->fetchAll(PDO::FETCH_COLUMN);
            $allStationIds = array_unique(array_merge($allStationIds, $newIds));
        }

        foreach ($allStationIds as $sid) {
            $existing = $pdo->prepare("SELECT rate_id FROM console_rate WHERE station_id=? AND day_type=?");
            $existing->execute([$sid, $dayType]);
            $existingId = $existing->fetchColumn();
            if ($existingId) {
                $upd = $pdo->prepare("UPDATE console_rate SET hourly_rate=?, effective_from=? WHERE rate_id=?");
                $upd->execute([$hourlyRate, $effectiveFrom, $existingId]);
            } else {
                $ins = $pdo->prepare("INSERT INTO console_rate (station_id, day_type, hourly_rate, effective_from) VALUES (?,?,?,?)");
                $ins->execute([$sid, $dayType, $hourlyRate, $effectiveFrom]);
            }
        }
        header("Location: console_rate.php?msg=Updated");
        exit;
    }
}

require_once 'header.php';
$msg = $_GET['msg'] ?? '';
$consoleTypes = $pdo->query("SELECT * FROM console_type ORDER BY console_type_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="page-header">
    <h1>💵 Console Type Rates</h1>
    <a href="console_rate.php?action=create" class="btn btn-primary">+ Add Rate</a>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?> successfully!</div><?php endif; ?>

<?php if ($action === 'list'): ?>
<?php
$consoleBranding = [
    'Gaming PC' => ['icon' => '🖥️', 'color' => '#4f7cff'],
    'Playstation 5 (PS5)' => ['icon' => '🎮', 'color' => '#3dd68c'],
    'Xbox Series X' => ['icon' => '🎯', 'color' => '#f59e5c'],
    'Nintendo Switch' => ['icon' => '🕹️', 'color' => '#ff5c7c'],
    'Virtual Reality (VR) Pod' => ['icon' => '🥽', 'color' => '#f0c94b'],
];

$rateData = $pdo->query("
    SELECT ct.console_type_id, ct.console_type_name, cr.day_type, cr.hourly_rate, cr.rate_id,
        (SELECT COUNT(*) FROM station s2 WHERE s2.console_type_id = ct.console_type_id) AS station_count
    FROM console_rate cr
    JOIN station s ON cr.station_id = s.station_id
    JOIN console_type ct ON s.console_type_id = ct.console_type_id
    GROUP BY ct.console_type_id, ct.console_type_name, cr.day_type
    ORDER BY ct.console_type_name, FIELD(cr.day_type, 'Weekday','Weekend','Public Holiday')
")->fetchAll(PDO::FETCH_ASSOC);

$rateGroups = [];
$rateIds = [];
foreach ($rateData as $r) {
    $rateGroups[$r['console_type_name']][] = $r;
    if (!isset($rateIds[$r['console_type_name']])) {
        $rateIds[$r['console_type_name']] = [];
    }
    $rateIds[$r['console_type_name']][$r['day_type']] = $r['rate_id'];
}
?>
<div class="rate-showcase">
    <?php foreach ($rateGroups as $typeName => $rates):
        $brand = $consoleBranding[$typeName] ?? ['icon' => '📦', 'color' => '#a855f7'];
        $first = $rates[0];
    ?>
    <div class="rate-section" style="--accent: <?= $brand['color'] ?>;">
        <div class="rate-section-header">
            <div class="rate-brand">
                <div class="rate-logo" style="background: linear-gradient(135deg, <?= $brand['color'] ?>22, <?= $brand['color'] ?>44); border-color: <?= $brand['color'] ?>66;">
                    <span><?= $brand['icon'] ?></span>
                </div>
                <div>
                    <h2 class="rate-title"><?= htmlspecialchars($typeName) ?></h2>
                    <span class="meta-item">🖥️ <?= $first['station_count'] ?> stations</span>
                </div>
            </div>
        </div>
        <div class="rate-grid">
            <?php foreach ($rates as $r):
                $dayType = $r['day_type'];
                $dayIcon = ['Weekday' => '💼', 'Weekend' => '🎉', 'Public Holiday' => '🎊'][$dayType] ?? '📅';
                $dayClass = strtolower(str_replace(' ', '-', $dayType));
            ?>
            <div class="rate-card rate-<?= $dayClass ?>">
                <div class="rate-card-icon"><?= $dayIcon ?></div>
                <div class="rate-card-info">
                    <span class="rate-card-day"><?= $dayType ?></span>
                    <span class="rate-card-price">RM<?= number_format($r['hourly_rate'], 2) ?><small>/hr</small></span>
                </div>
                <div class="rate-card-actions">
                    <a href="console_rate.php?action=edit&id=<?= $r['rate_id'] ?>" class="rate-action rate-action-edit">Edit</a>
                    <a href="console_rate.php?action=delete&id=<?= $r['rate_id'] ?>" class="rate-action rate-action-delete" onclick="return confirm('Delete this rate?')">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.rate-showcase {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.rate-section {
    background: var(--surface, #18181B);
    border: 1px solid var(--border, #27272A);
    border-radius: 20px;
    overflow: hidden;
}
.rate-section-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.rate-brand {
    display: flex;
    align-items: center;
    gap: 14px;
}
.rate-logo {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    border: 1.5px solid;
}
.rate-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--text, #d4d8e6);
    margin: 0;
}
.rate-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 18px 24px;
}
.rate-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 10px;
    transition: all 0.25s ease;
}
.rate-card:hover {
    transform: translateY(-2px);
}
.rate-card-icon { font-size: 24px; }
.rate-card-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.rate-card-day {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.rate-card-price {
    font-size: 20px;
    font-weight: 900;
}
.rate-card-price small {
    font-size: 11px;
    font-weight: 600;
}

.rate-weekday {
    background: rgba(0,240,255,0.04);
    border: 1px solid rgba(0,240,255,0.12);
}
.rate-weekday .rate-card-day { color: var(--accent, #4f7cff); }
.rate-weekday .rate-card-price { color: var(--accent, #4f7cff); }

.rate-weekend {
    background: rgba(255,149,0,0.04);
    border: 1px solid rgba(255,149,0,0.12);
}
.rate-weekend .rate-card-day { color: var(--orange, #f59e5c); }
.rate-weekend .rate-card-price { color: var(--orange, #f59e5c); }

.rate-public-holiday {
    background: rgba(168,85,247,0.04);
    border: 1px solid rgba(168,85,247,0.12);
}
.rate-public-holiday .rate-card-day { color: var(--green, #3dd68c); }
.rate-public-holiday .rate-card-price { color: var(--green, #3dd68c); }

.rate-card-actions {
    display: flex; gap: 4px; margin-left: auto; flex-shrink: 0;
}
.rate-action {
    padding: 4px 10px; border-radius: 6px;
    font-size: 10px; font-weight: 500; text-decoration: none;
    transition: all 0.2s ease;
}
.rate-action-edit { color: var(--text-muted); background: rgba(255,255,255,0.03); }
.rate-action-edit:hover { color: #60A5FA; background: rgba(96,165,250,0.1); }
.rate-action-delete { color: var(--text-muted); background: rgba(255,255,255,0.03); }
.rate-action-delete:hover { color: #F87171; background: rgba(239,68,68,0.1); }

@media (max-width: 768px) {
    .rate-grid {
        grid-template-columns: 1fr;
        padding: 14px 16px;
    }
}
</style>
<?php elseif ($action === 'create' || $action === 'edit'):
    $r = ['rate_id'=>'','console_type_id'=>'','day_type'=>'','hourly_rate'=>'','effective_from'=>''];
    if ($action === 'edit') {
        $stmt = $pdo->prepare("SELECT sr.*, s.console_type_id FROM console_rate sr LEFT JOIN station s ON sr.station_id = s.station_id WHERE sr.rate_id=?");
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
    }
?>
<div class="form-card">
    <form method="post">
        <div class="form-group">
            <label for="console_type_id">Console Type</label>
            <select name="console_type_id" id="console_type_id" class="form-control" required>
                <option value="">Select Console Type</option>
                <?php foreach ($consoleTypes as $ct): ?>
                <option value="<?= $ct['console_type_id'] ?>" <?= ($r['console_type_id']??'')==$ct['console_type_id']?'selected':'' ?>><?= htmlspecialchars($ct['console_type_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="day_type">Day Type</label>
            <select name="day_type" id="day_type" class="form-control">
                <option value="Weekday" <?= $r['day_type']==='Weekday'?'selected':'' ?>>Weekday</option>
                <option value="Weekend" <?= $r['day_type']==='Weekend'?'selected':'' ?>>Weekend</option>
                <option value="Public Holiday" <?= $r['day_type']==='Public Holiday'?'selected':'' ?>>Public Holiday</option>
            </select>
        </div>
        <div class="form-group">
            <label for="hourly_rate">Hourly Rate (RM)</label>
            <input type="number" step="0.01" name="hourly_rate" id="hourly_rate" class="form-control" value="<?= $r['hourly_rate'] ?>" required>
        </div>
        <div class="form-group">
            <label for="effective_from">Effective From</label>
            <input type="date" name="effective_from" id="effective_from" class="form-control" value="<?= $r['effective_from'] ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $action === 'create' ? 'Create Rate' : 'Update Rate' ?></button>
            <a href="console_rate.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; require_once 'footer.php'; ?>
