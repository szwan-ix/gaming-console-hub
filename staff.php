<?php
require_once 'config.php';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

if ($action === 'delete') {
    $pdo->prepare("DELETE FROM staff WHERE staff_id=?")->execute([$id]);
    header("Location: staff.php?msg=Deleted");
    exit;
}

$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $errors = [];
    if (empty($_POST['name'])) $errors[] = 'Name is required.';

    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

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
            $stmt = $pdo->prepare("INSERT INTO staff (name, role, phone, email, status) VALUES (?,?,?,?,?)");
            $stmt->execute([$_POST['name'], $_POST['role'], $phone, $email, $_POST['status']]);
            header("Location: staff.php?msg=Created");
            exit;
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE staff SET name=?, role=?, phone=?, email=?, status=? WHERE staff_id=?");
            $stmt->execute([$_POST['name'], $_POST['role'], $phone, $email, $_POST['status'], $id]);
            header("Location: staff.php?msg=Updated");
            exit;
        }
    }
}

require_once 'header.php';
$msg = $_GET['msg'] ?? '';
?>
<div class="page-header">
    <h1>👨‍💼 Staff</h1>
    <a href="staff.php?action=create" class="btn btn-primary">+ Add Staff</a>
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
                <th>Role</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pdo->query("SELECT * FROM staff ORDER BY staff_id DESC") as $r): ?>
            <tr>
                <td><?= $r['staff_id'] ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><span class="badge badge-<?= strtolower($r['role']) ?>"><?= $r['role'] ?></span></td>
                <td><?= htmlspecialchars($r['phone']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                <td class="action-group">
                    <a href="staff.php?action=edit&id=<?= $r['staff_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <a href="staff.php?action=delete&id=<?= $r['staff_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this staff member?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif ($action === 'create' || $action === 'edit'):
    $r = ['staff_id'=>'','name'=>'','role'=>'','phone'=>'','email'=>'','status'=>'Active'];
    if ($action === 'edit') {
        $r = $pdo->prepare("SELECT * FROM staff WHERE staff_id=?");
        $r->execute([$id]);
        $r = $r->fetch(PDO::FETCH_ASSOC);
    }
?>
<div class="form-card">
    <form method="post">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($r['name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="role">Role</label>
            <select name="role" id="role" class="form-control">
                <option value="Admin" <?= $r['role']==='Admin'?'selected':'' ?>>Admin</option>
                <option value="Technician" <?= $r['role']==='Technician'?'selected':'' ?>>Technician</option>
                <option value="Attendant" <?= $r['role']==='Attendant'?'selected':'' ?>>Attendant</option>
            </select>
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($r['phone']) ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($r['email']) ?>">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="Active" <?= $r['status']==='Active'?'selected':'' ?>>Active</option>
                <option value="Inactive" <?= $r['status']==='Inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $action === 'create' ? 'Create Staff' : 'Update Staff' ?></button>
            <a href="staff.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; require_once 'footer.php'; ?>
