<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin']);
include __DIR__ . '/admin_header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'Invalid CSRF token.';
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = trim($_POST['role_key'] ?? 'customer');
        $eco   = max(0, intval($_POST['eco_points'] ?? 0));
        $pw    = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $pw === '') {
            $error = 'Name, Email, and Password are required.';
        } else {
            // Check unique email
            $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $chk->execute([$email]);
            if ((int)$chk->fetchColumn() > 0) {
                $error = 'An account with this email already exists.';
            } else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role_key, eco_points) VALUES (?,?,?,?,?)');
                $ins->execute([$name, $email, $hash, $role, $eco]);
                header('Location: manage_users.php?created=1');
                exit;
            }
        }
    }
}
?>

<main class="container my-5">
    <div class="page-header">
        <h2 class="title">Add New User</h2>
        <a href="manage_users.php" class="btn btn-outline-success">Back to Users</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-warning"><?= h($error) ?></div><?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input class="form-control" type="text" name="name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <?php $roles = ['admin'=>'Admin','staff'=>'Staff','customer'=>'Customer']; ?>
            <select class="form-control" name="role_key">
                <?php foreach ($roles as $k=>$label): ?>
                    <option value="<?= h($k) ?>"><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Eco Points (optional)</label>
            <input class="form-control" type="number" name="eco_points" value="0" min="0">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required autocomplete="new-password">
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-success">Create User</button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/admin_footer.php'; ?>
