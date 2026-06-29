<?php
require_once 'config.php';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

if ($action === 'delete') {
    $pdo->prepare("DELETE FROM maintenance WHERE maintenance_id=?")->execute([$id]);
    header("Location: maintenance.php?msg=Deleted");
    exit;
}

$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $errors = [];
    if (empty($_POST['station_id'])) $errors[] = 'Station is required.';
    if (empty($_POST['staff_id'])) $errors[] = 'Technician is required.';
    if (empty($_POST['maintenance_date'])) $errors[] = 'Maintenance date is required.';
    if (empty($_POST['description'])) $errors[] = 'Description is required.';

    if (!empty($errors)) {
        $formError = implode(' ', $errors);
    }

    if (!$formError) {
        $maintenanceId = $id;
        $stationId = $_POST['station_id'];
        $newStatus = $_POST['status'];

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO maintenance (station_id, staff_id, maintenance_date, description, status) VALUES (?,?,?,?,?)");
        $stmt->execute([$stationId, $_POST['staff_id'], $_POST['maintenance_date'], $_POST['description'], $newStatus]);
        $maintenanceId = $pdo->lastInsertId();
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE maintenance SET station_id=?, staff_id=?, maintenance_date=?, description=?, status=? WHERE maintenance_id=?");
        $stmt->execute([$stationId, $_POST['staff_id'], $_POST['maintenance_date'], $_POST['description'], $newStatus, $maintenanceId]);
    }

    if ($newStatus === 'In Progress') {
        $pdo->prepare("UPDATE station SET status='Unavailable' WHERE station_id=?")->execute([$stationId]);
    } elseif ($newStatus === 'Completed') {
        // Check if any other maintenance is still active
        $check = $pdo->prepare("SELECT COUNT(*) FROM maintenance WHERE station_id=? AND status IN ('Scheduled','In Progress') AND maintenance_id!=?");
        $check->execute([$stationId, $maintenanceId]);
        $activeCount = $check->fetchColumn();

        // Also check for active bookings
        $bookingCheck = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE station_id=? AND status='Confirmed' AND booking_date >= CURDATE()");
        $bookingCheck->execute([$stationId]);
        $activeBookings = $bookingCheck->fetchColumn();

        if ($activeCount == 0 && $activeBookings == 0) {
            $pdo->prepare("UPDATE station SET status='Available' WHERE station_id=?")->execute([$stationId]);
        }
    }

        $msg = $action === 'create' ? 'Created' : 'Updated';
        header("Location: maintenance.php?msg=$msg");
        exit;
    }
}

require_once 'header.php';
$msg = $_GET['msg'] ?? '';
$stations = $pdo->query("
    SELECT s.*, ct.console_type_name
    FROM station s
    LEFT JOIN console_type ct ON s.console_type_id = ct.console_type_id
    ORDER BY s.station_name
")->fetchAll(PDO::FETCH_ASSOC);
$staffList = $pdo->query("SELECT * FROM staff WHERE status='Active' AND role='Technician' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="page-header">
    <h1>🔧 Maintenance</h1>
    <a href="maintenance.php?action=create" class="btn btn-primary">+ Add Maintenance</a>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?> successfully!</div><?php endif; ?>
<?php if ($formError): ?><div class="alert alert-error"><?= htmlspecialchars($formError) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Station</th>
                <th>Staff</th>
                <th>Date</th>
                <th>Description</th>
                <th>Status</th>
                <th>Station Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rows = $pdo->query("
                SELECT m.*, s.station_name, s.status AS station_status, st.name AS staff_name
                FROM maintenance m
                LEFT JOIN station s ON m.station_id = s.station_id
                LEFT JOIN staff st ON m.staff_id = st.staff_id
                ORDER BY m.maintenance_id DESC
            ");
            foreach ($rows as $r):
            ?>
            <tr>
                <td><?= $r['maintenance_id'] ?></td>
                <td><?= htmlspecialchars($r['station_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['staff_name'] ?? '-') ?></td>
                <td><?= $r['maintenance_date'] ?></td>
                <td><?= htmlspecialchars($r['description']) ?></td>
                <td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $r['status'])) ?>"><?= $r['status'] ?></span></td>
                <td><span class="badge badge-<?= strtolower($r['station_status']) ?>"><?= $r['station_status'] ?></span></td>
                <td class="action-group">
                    <a href="maintenance.php?action=edit&id=<?= $r['maintenance_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="maintenance.php?action=delete&id=<?= $r['maintenance_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this maintenance record?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif ($action === 'create' || $action === 'edit'):
    $r = ['maintenance_id'=>'','station_id'=>'','staff_id'=>'','maintenance_date'=>'','description'=>'','status'=>'Scheduled'];
    if ($action === 'edit') {
        $r = $pdo->prepare("SELECT * FROM maintenance WHERE maintenance_id=?");
        $r->execute([$id]);
        $r = $r->fetch(PDO::FETCH_ASSOC);
    }
?>
<div class="form-card">
    <form method="post">
        <div class="form-group">
            <label for="station_id">Station (Available only)</label>
            <select name="station_id" id="station_id" class="form-control">
                <option value="">Select Station</option>
                <?php foreach ($stations as $s):
                    $canSelect = ($action === 'edit' && $r['station_id'] == $s['station_id']) || $s['status'] === 'Available';
                    if ($canSelect):
                ?>
                <option value="<?= $s['station_id'] ?>" <?= $r['station_id']==$s['station_id']?'selected':'' ?>>
                    <?= htmlspecialchars($s['station_name']) ?> (<?= $s['status'] ?>)
                </option>
                <?php endif; endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="staff_id">Technician</label>
            <select name="staff_id" id="staff_id" class="form-control">
                <option value="">Select Technician</option>
                <?php foreach ($staffList as $st): ?>
                <option value="<?= $st['staff_id'] ?>" <?= $r['staff_id']==$st['staff_id']?'selected':'' ?>><?= htmlspecialchars($st['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="maintenance_date">Maintenance Date</label>
            <input type="date" name="maintenance_date" id="maintenance_date" class="form-control" value="<?= $r['maintenance_date'] ?>">
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="4"><?= htmlspecialchars($r['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="Scheduled" <?= $r['status']==='Scheduled'?'selected':'' ?>>Scheduled</option>
                <option value="In Progress" <?= $r['status']==='In Progress'?'selected':'' ?>>In Progress</option>
                <option value="Completed" <?= $r['status']==='Completed'?'selected':'' ?>>Completed</option>
            </select>
            <small style="color:#86868b;font-size:12px;display:block;margin-top:4px;">
                Setting to "In Progress" will mark station as Unavailable. Setting to "Completed" will mark it Available.
            </small>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $action === 'create' ? 'Create Maintenance' : 'Update Maintenance' ?></button>
            <a href="maintenance.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; require_once 'footer.php'; ?>
