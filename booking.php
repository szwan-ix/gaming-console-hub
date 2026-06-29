<?php
require_once 'config.php';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$payAction = $_GET['pay_action'] ?? '';

if ($action === 'delete') {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM payment WHERE booking_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM booking WHERE booking_id=?")->execute([$id]);
    $pdo->commit();
    header("Location: booking.php?msg=Deleted");
    exit;
}
if ($payAction === 'delete_payment') {
    $pdo->prepare("DELETE FROM payment WHERE payment_id=?")->execute([$id]);
    header("Location: booking.php?msg=Payment deleted");
    exit;
}

$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $errors = [];
    if (empty($_POST['member_id'])) $errors[] = 'Member is required.';
    if (empty($_POST['station_id'])) $errors[] = 'Station is required.';
    if (empty($_POST['staff_id'])) $errors[] = 'Staff is required.';
    if (empty($_POST['booking_date'])) $errors[] = 'Booking date is required.';

    if (!empty($errors)) {
        $formError = implode(' ', $errors);
    }

    if (!$formError) {
        if ($action === 'create') {
            $start = $_POST['start_time'] ?? '';
            $end = $_POST['end_time'] ?? '';
        $totalHours = (strtotime($end) - strtotime($start)) / 3600;
        if ($totalHours <= 0) $totalHours = 0;

        $rateStmt = $pdo->prepare("SELECT sr.hourly_rate FROM console_rate sr JOIN station s ON sr.station_id=s.station_id WHERE s.station_id=? ORDER BY sr.effective_from DESC LIMIT 1");
        $rateStmt->execute([$_POST['station_id']]);
        $hourlyRate = (float)($rateStmt->fetchColumn() ?: 0);
        $amount = round($totalHours * $hourlyRate, 2);

        // Prevent creating payment with zero amount
        if ($totalHours <= 0 && $_POST['payment_method']) {
            die("Error: Cannot create booking with zero hours and a payment method. Please set valid start and end times.");
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO booking (member_id, station_id, staff_id, booking_date, start_time, end_time, total_hours, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['member_id'], $_POST['station_id'], $_POST['staff_id'], $_POST['booking_date'], $start, $end, $totalHours, $_POST['status']]);
        $bookingId = $pdo->lastInsertId();

        if ($_POST['payment_method']) {
            $pStmt = $pdo->prepare("INSERT INTO payment (booking_id, payment_date, payment_method, amount, payment_status) VALUES (?,?,?,?,?)");
            $pStmt->execute([$bookingId, $_POST['payment_date'] ?: $_POST['booking_date'], $_POST['payment_method'], $amount, $_POST['payment_status']]);
        }
        $pdo->commit();
        header("Location: booking.php?msg=Created");
        exit;
    } elseif ($action === 'edit') {
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $totalHours = (strtotime($end) - strtotime($start)) / 3600;
        if ($totalHours < 0) $totalHours = 0;

        $rateStmt = $pdo->prepare("SELECT sr.hourly_rate FROM console_rate sr JOIN station s ON sr.station_id=s.station_id WHERE s.station_id=? ORDER BY sr.effective_from DESC LIMIT 1");
        $rateStmt->execute([$_POST['station_id']]);
        $hourlyRate = (float)($rateStmt->fetchColumn() ?: 0);
        $amount = round($totalHours * $hourlyRate, 2);

        // Validate payment method
        $validMethods = ['', 'Cash', 'Card', 'E-Wallet'];
        if (!in_array($_POST['payment_method'], $validMethods)) {
            die("Error: Invalid payment method.");
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE booking SET member_id=?, station_id=?, staff_id=?, booking_date=?, start_time=?, end_time=?, total_hours=?, status=? WHERE booking_id=?");
        $stmt->execute([$_POST['member_id'], $_POST['station_id'], $_POST['staff_id'], $_POST['booking_date'], $start, $end, $totalHours, $_POST['status'], $id]);

        $exists = $pdo->prepare("SELECT COUNT(*) FROM payment WHERE booking_id=?");
        $exists->execute([$id]);
        if ($exists->fetchColumn() > 0) {
            $pStmt = $pdo->prepare("UPDATE payment SET payment_date=?, payment_method=?, amount=?, payment_status=? WHERE booking_id=?");
            $pStmt->execute([$_POST['payment_date'] ?: $_POST['booking_date'], $_POST['payment_method'], $amount, $_POST['payment_status'], $id]);
        } elseif ($_POST['payment_method']) {
            $pStmt = $pdo->prepare("INSERT INTO payment (booking_id, payment_date, payment_method, amount, payment_status) VALUES (?,?,?,?,?)");
            $pStmt->execute([$id, $_POST['payment_date'] ?: $_POST['booking_date'], $_POST['payment_method'], $amount, $_POST['payment_status']]);
        }
        $pdo->commit();
        header("Location: booking.php?msg=Updated");
            exit;
        }
    }
}

require_once 'header.php';
$msg = $_GET['msg'] ?? '';
$members = $pdo->query("SELECT * FROM member WHERE status='Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$stations = $pdo->query("SELECT s.*, 
    (SELECT sr.hourly_rate FROM console_rate sr WHERE sr.station_id = s.station_id ORDER BY sr.effective_from DESC LIMIT 1) AS hourly_rate
    FROM station s WHERE s.station_id NOT IN (
        SELECT m.station_id FROM maintenance m WHERE m.status IN ('Scheduled','In Progress')
    ) ORDER BY s.station_name")->fetchAll(PDO::FETCH_ASSOC);
$staffList = $pdo->query("SELECT * FROM staff WHERE status='Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="page-header">
    <h1>📅 Bookings & Payments</h1>
    <a href="booking.php?action=create" class="btn btn-primary">+ Add Booking</a>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?> successfully!</div><?php endif; ?>
<?php if ($formError): ?><div class="alert alert-error"><?= htmlspecialchars($formError) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Station</th>
                <th>Staff</th>
                <th>Date</th>
                <th>Time</th>
                <th>Hours</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rows = $pdo->query("
                SELECT b.*, m.name AS member_name, s.station_name, st.name AS staff_name,
                    p.payment_id, p.payment_method, p.amount AS pay_amount, p.payment_status
                FROM booking b
                LEFT JOIN member m ON b.member_id = m.member_id
                LEFT JOIN station s ON b.station_id = s.station_id
                LEFT JOIN staff st ON b.staff_id = st.staff_id
                LEFT JOIN payment p ON b.booking_id = p.booking_id
                ORDER BY b.booking_id DESC
            ");
            foreach ($rows as $r):
            ?>
            <tr>
                <td><?= $r['booking_id'] ?></td>
                <td><?= htmlspecialchars($r['member_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['station_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['staff_name'] ?? '-') ?></td>
                <td><?= $r['booking_date'] ?></td>
                <td><?= $r['start_time'] ?> - <?= $r['end_time'] ?></td>
                <td><?= $r['total_hours'] ?></td>
                <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                <td>
                    <?php if ($r['payment_id']): ?>
                        <span class="badge badge-<?= strtolower($r['payment_status']) ?>"><?= $r['payment_status'] ?></span>
                        <small style="color:#86868b;display:block;"><?= $r['payment_method'] ?></small>
                    <?php else: ?>
                        <span style="color:#86868b;">-</span>
                    <?php endif; ?>
                </td>
                <td><?= $r['pay_amount'] ? 'RM'.number_format($r['pay_amount'], 2) : '-' ?></td>
                <td class="action-group">
                    <a href="booking.php?action=edit&id=<?= $r['booking_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="booking.php?action=delete&id=<?= $r['booking_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this booking and its payment?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif ($action === 'create' || $action === 'edit'):
    $today = date('Y-m-d');
    $r = ['booking_id'=>'','member_id'=>'','station_id'=>'','staff_id'=>'','booking_date'=>$today,'start_time'=>'','end_time'=>'','total_hours'=>'','status'=>'Confirmed'];
    $pay = ['payment_id'=>'','payment_date'=>$today,'payment_method'=>'','amount'=>'','payment_status'=>'Unpaid'];
    if ($action === 'edit' && $id) {
        $r = $pdo->prepare("SELECT * FROM booking WHERE booking_id=?");
        $r->execute([$id]);
        $r = $r->fetch(PDO::FETCH_ASSOC);
        $p = $pdo->prepare("SELECT * FROM payment WHERE booking_id=?");
        $p->execute([$id]);
        $pay = $p->fetch(PDO::FETCH_ASSOC) ?: $pay;
    }
?>
<div class="form-card">
    <form method="post" onsubmit="return calcHours()">
        <h3 class="section-title">Booking Details</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="member_id">Member</label>
                <select name="member_id" id="member_id" class="form-control">
                    <option value="">Select Member</option>
                    <?php foreach ($members as $m): ?>
                    <option value="<?= $m['member_id'] ?>" <?= $r['member_id']==$m['member_id']?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="station_id">Station</label>
                <select name="station_id" id="station_id" class="form-control">
                    <option value="">Select Station</option>
                    <?php foreach ($stations as $s): ?>
                    <option value="<?= $s['station_id'] ?>" data-rate="<?= $s['hourly_rate'] ?>" <?= $r['station_id']==$s['station_id']?'selected':'' ?>><?= htmlspecialchars($s['station_name']) ?> (RM<?= $s['hourly_rate'] ?>/hr)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="staff_id">Staff</label>
                <select name="staff_id" id="staff_id" class="form-control">
                    <option value="">Select Staff</option>
                    <?php foreach ($staffList as $st): ?>
                    <option value="<?= $st['staff_id'] ?>" <?= $r['staff_id']==$st['staff_id']?'selected':'' ?>><?= htmlspecialchars($st['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="booking_date">Booking Date</label>
                <input type="date" name="booking_date" id="booking_date" class="form-control" value="<?= $r['booking_date'] ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" name="start_time" id="start_time" class="form-control" value="<?= $r['start_time'] ?>" onchange="calcHoursPreview()">
            </div>
            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="time" name="end_time" id="end_time" class="form-control" value="<?= $r['end_time'] ?>" onchange="calcHoursPreview()">
            </div>
        </div>
        <div class="form-group">
            <label>Total Hours</label>
            <input type="text" id="total_hours_display" class="form-control" value="<?= $r['total_hours'] ?>" readonly style="background:#f5f5f7;">
            <input type="hidden" name="total_hours" id="total_hours_hidden" value="<?= $r['total_hours'] ?>">
        </div>
        <div class="form-group">
            <label for="status">Booking Status</label>
            <select name="status" id="status" class="form-control">
                <option value="Confirmed" <?= $r['status']==='Confirmed'?'selected':'' ?>>Confirmed</option>
                <option value="Completed" <?= $r['status']==='Completed'?'selected':'' ?>>Completed</option>
                <option value="Cancelled" <?= $r['status']==='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
        </div>

        <h3 class="section-title">Payment Details</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select name="payment_method" id="payment_method" class="form-control">
                    <option value="">No Payment</option>
                    <option value="Cash" <?= ($pay['payment_method']??'')==='Cash'?'selected':'' ?>>Cash</option>
                    <option value="Card" <?= ($pay['payment_method']??'')==='Card'?'selected':'' ?>>Card</option>
                    <option value="E-Wallet" <?= ($pay['payment_method']??'')==='E-Wallet'?'selected':'' ?>>E-Wallet</option>
                </select>
            </div>
            <div class="form-group">
                <label for="payment_date">Payment Date</label>
                <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?= $pay['payment_date'] ?? $today ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="amount">Amount (RM) <small style="color:#86868b;font-weight:400;">auto-calculated</small></label>
                <input type="text" name="amount" id="amount" class="form-control" value="<?= $pay['amount'] ?? '' ?>" readonly style="background:#f5f5f7;">
            </div>
            <div class="form-group">
                <label for="payment_status">Payment Status</label>
                <select name="payment_status" id="payment_status" class="form-control">
                    <option value="Paid" <?= ($pay['payment_status']??'')==='Paid'?'selected':'' ?>>Paid</option>
                    <option value="Unpaid" <?= ($pay['payment_status']??'')==='Unpaid'?'selected':'' ?>>Unpaid</option>
                    <option value="Refunded" <?= ($pay['payment_status']??'')==='Refunded'?'selected':'' ?>>Refunded</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $action === 'create' ? 'Create Booking' : 'Update Booking' ?></button>
            <a href="booking.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<script>
function getStationRate() {
    var sel = document.getElementById('station_id');
    var opt = sel.options[sel.selectedIndex];
    return parseFloat(opt.getAttribute('data-rate')) || 0;
}
function calcAmount() {
    var hours = parseFloat(document.getElementById('total_hours_hidden').value) || 0;
    var rate = getStationRate();
    var total = (hours * rate).toFixed(2);
    document.getElementById('amount').value = total;
}
function calcHoursPreview() {
    var start = document.getElementById('start_time').value;
    var end = document.getElementById('end_time').value;
    if (start && end) {
        var s = new Date('2000-01-01T' + start);
        var e = new Date('2000-01-01T' + end);
        var diff = (e - s) / 3600000;
        if (diff < 0) diff = 0;
        document.getElementById('total_hours_display').value = diff.toFixed(2);
        document.getElementById('total_hours_hidden').value = diff.toFixed(2);
    }
    calcAmount();
}
function calcHours() {
    calcHoursPreview();
    return true;
}
document.getElementById('station_id').addEventListener('change', calcAmount);
</script>
<?php endif; require_once 'footer.php'; ?>
