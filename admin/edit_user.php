<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin']);
include __DIR__ . '/admin_header.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<main class='container my-5'><div class='alert alert-warning'>Invalid user ID.</div></main>";
    include __DIR__ . '/admin_footer.php';
    exit();
}

// Load user by current schema
$stmt = $pdo->prepare("SELECT user_id, name, email, role_key, eco_points FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "<main class='container my-5'><div class='alert alert-warning'>User not found.</div></main>";
    include __DIR__ . '/admin_footer.php';
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = trim($_POST['role_key'] ?? 'customer');
        $eco   = max(0, intval($_POST['eco_points'] ?? 0));
        $newPw = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '') {
            $error = 'Name and Email are required.';
        } else {
            // ensure email is unique for other users
            $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id <> ?");
            $chk->execute([$email, $id]);
            if ((int)$chk->fetchColumn() > 0) {
                $error = 'Another account already uses this email.';
            } else {
                if ($newPw !== '') {
                    $hash = password_hash($newPw, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE users SET name = ?, email = ?, role_key = ?, eco_points = ?, password_hash = ? WHERE user_id = ?");
                    $upd->execute([$name, $email, $role, $eco, $hash, $id]);
                } else {
                    $upd = $pdo->prepare("UPDATE users SET name = ?, email = ?, role_key = ?, eco_points = ? WHERE user_id = ?");
                    $upd->execute([$name, $email, $role, $eco, $id]);
                }
                $message = 'User updated successfully.';
                // refresh
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
}
?>
<main class="container my-5">
    <h2 class="text-success mb-3">Edit User</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-warning"><?= h($error) ?></div><?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input class="form-control" type="text" name="name" value="<?= h($user['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="<?= h($user['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <?php $roles = ['admin'=>'Admin','staff'=>'Staff','customer'=>'Customer']; ?>
            <select class="form-control" name="role_key">
                <?php foreach ($roles as $k=>$label): $sel = ($user['role_key'] === $k) ? 'selected' : ''; ?>
                    <option value="<?= h($k) ?>" <?= $sel ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Eco Points</label>
            <input class="form-control" type="number" name="eco_points" value="<?= intval($user['eco_points']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">New Password (leave blank to keep current)</label>
            <input class="form-control" type="password" name="password" autocomplete="new-password">
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="manage_users.php" class="btn btn-outline-success">Back to Users</a>
        </div>
    </form>
</main>
<?php include __DIR__ . '/admin_footer.php'; ?>
