<?php
require_once 'config.php';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

function calcExpiry($type, $joinDate) {
    switch ($type) {
        case 'Casual': return date('Y-m-d', strtotime($joinDate . ' +1 month'));
        case 'Standard': return date('Y-m-d', strtotime($joinDate . ' +3 months'));
        case 'Premium': return date('Y-m-d', strtotime($joinDate . ' +1 year'));
        default: return $joinDate;
    }
}

if ($action === 'delete') {
    // Check if member has bookings
    $check = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE member_id=?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        die("Error: Cannot delete member. Please delete or reassign all associated bookings first.");
    }
    $pdo->prepare("DELETE FROM member WHERE member_id=?")->execute([$id]);
    header("Location: member.php?msg=Deleted");
    exit;
}

$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $errors = [];
    if (empty($_POST['name'])) $errors[] = 'Name is required.';

    // Validate email format
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate phone number
    $phone = trim($_POST['phone'] ?? '');
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9]+$/', $phone)) {
        $errors[] = 'Phone number must contain only digits.';
    } elseif (strlen($phone) < 11 || strlen($phone) > 12) {
        $errors[] = 'Phone number must be 11 or 12 digits.';
    }

    if (!empty($errors)) {
        $formError = implode(' ', $errors);
    }

    if (!$formError) {
        if ($action === 'create') {
            $joinDate = date('Y-m-d');
            $stmt = $pdo->prepare("INSERT INTO member (name, email, phone, membership_type, join_date, status) VALUES (?,?,?,?,?,'Active')");
            $stmt->execute([$_POST['name'], $email, $phone, $_POST['membership_type'], $joinDate]);
            header("Location: member.php?msg=Created");
            exit;
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE member SET name=?, email=?, phone=?, membership_type=?, join_date=?, status=? WHERE member_id=?");
            $stmt->execute([$_POST['name'], $email, $phone, $_POST['membership_type'], $_POST['join_date'], $_POST['status'], $id]);
            header("Location: member.php?msg=Updated");
            exit;
        }
    }
}

require_once 'header.php';
$msg = $_GET['msg'] ?? '';
?>
<div class="page-header">
    <h1>👥 Members</h1>
    <a href="member.php?action=create" class="btn btn-primary">+ Add Member</a>
</div>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?> successfully!</div><?php endif; ?>
<?php if ($formError): ?><div class="alert alert-error"><?= htmlspecialchars($formError) ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Membership</th>
                <th>Join Date</th>
                <th>Expiry</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pdo->query("SELECT * FROM member ORDER BY member_id DESC") as $r):
                $expiry = calcExpiry($r['membership_type'], $r['join_date']);
                $isExpired = $expiry < date('Y-m-d');
                $displayStatus = $isExpired ? 'Expired' : $r['status'];
                $badgeClass = $isExpired ? 'expired' : strtolower($r['status']);
            ?>
            <tr>
                <td><?= $r['member_id'] ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['phone']) ?></td>
                <td><span class="badge badge-<?= strtolower($r['membership_type']) ?>"><?= $r['membership_type'] ?></span></td>
                <td><?= $r['join_date'] ?></td>
                <td><?= $expiry ?></td>
                <td><span class="badge badge-<?= $badgeClass ?>"><?= $displayStatus ?></span></td>
                <td class="action-group">
                    <a href="member.php?action=edit&id=<?= $r['member_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="member.php?action=delete&id=<?= $r['member_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this member?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif ($action === 'create'):
    $today = date('Y-m-d');
?>
<div class="form-card">
    <form method="post">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control">
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control">
        </div>
        <div class="form-group">
            <label for="membership_type">Membership Type</label>
            <select name="membership_type" id="membership_type" class="form-control" onchange="updateExpiry()">
                <option value="Casual">Casual (1 Month)</option>
                <option value="Standard">Standard (3 Months)</option>
                <option value="Premium">Premium (1 Year)</option>
            </select>
            <small style="color:#86868b;font-size:12px;display:block;margin-top:4px;">Join date: <?= $today ?> | Expiry: <span id="expiryPreview"><?= date('Y-m-d', strtotime('+1 month')) ?></span></small>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Member</button>
            <a href="member.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<script>
function updateExpiry() {
    var type = document.getElementById('membership_type').value;
    var today = '<?= $today ?>';
    var expiry = new Date(today + 'T00:00:00Z');
    if (type === 'Casual') expiry.setMonth(expiry.getUTCMonth() + 1);
    else if (type === 'Standard') expiry.setMonth(expiry.getUTCMonth() + 3);
    else if (type === 'Premium') expiry.setUTCFullYear(expiry.getUTCFullYear() + 1);
    var y = expiry.getUTCFullYear();
    var m = String(expiry.getUTCMonth() + 1).padStart(2, '0');
    var d = String(expiry.getUTCDate()).padStart(2, '0');
    document.getElementById('expiryPreview').textContent = y + '-' + m + '-' + d;
}
</script>
<?php elseif ($action === 'edit'):
    $r = $pdo->prepare("SELECT * FROM member WHERE member_id=?");
    $r->execute([$id]);
    $r = $r->fetch(PDO::FETCH_ASSOC);
?>
<div class="form-card">
    <form method="post">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($r['name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($r['email']) ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($r['phone']) ?>">
        </div>
        <div class="form-group">
            <label for="membership_type">Membership Type</label>
            <select name="membership_type" id="membership_type" class="form-control">
                <option value="Casual" <?= $r['membership_type']==='Casual'?'selected':'' ?>>Casual (1 Month)</option>
                <option value="Standard" <?= $r['membership_type']==='Standard'?'selected':'' ?>>Standard (3 Months)</option>
                <option value="Premium" <?= $r['membership_type']==='Premium'?'selected':'' ?>>Premium (1 Year)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="join_date">Join Date</label>
            <input type="date" name="join_date" id="join_date" class="form-control" value="<?= $r['join_date'] ?>">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="Active" <?= $r['status']==='Active'?'selected':'' ?>>Active</option>
                <option value="Inactive" <?= $r['status']==='Inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Member</button>
            <a href="member.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; require_once 'footer.php'; ?>
