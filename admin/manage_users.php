<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin']);

// Handle delete (POST + CSRF) and prevent self-delete
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $notice = 'Invalid CSRF token.';
    } else {
        $delId = (int)$_POST['delete_user_id'];
        $selfId = (int)($_SESSION['user_id'] ?? 0);
        if ($delId === $selfId) {
            $notice = 'You cannot delete your own account while logged in.';
        } elseif ($delId > 0) {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmtDel->execute([$delId]);
                $notice = 'User deleted.';
            } catch (Throwable $e) {
                $notice = 'Unable to delete this user. They may have related orders or activity.';
            }
        }
    }
}

include __DIR__ . '/admin_header.php';

// Load users with current schema
$stmt = $pdo->query("SELECT user_id, name, email, eco_points, role_key, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container my-5">
    <div class="page-header">
        <h2 class="title">Manage Users</h2>
        <a href="add_user.php" class="btn btn-success">Add New User</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-warning"><?= h($notice) ?></div>
    <?php endif; ?>

    <table class="table table-striped table-bordered">
        <thead class="table-success">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>EcoPoints</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= intval($user['user_id']) ?></td>
                <td><?= h($user['name']) ?></td>
                <td><?= h($user['email']) ?></td>
                <td><?= intval($user['eco_points']) ?></td>
                <td><?= h($user['role_key']) ?></td>
                <td class="d-flex gap-1">
                    <a class="btn btn-sm btn-warning" href="edit_user.php?id=<?= intval($user['user_id']) ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this user? This may fail if they have related orders or posts.');">
                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                        <input type="hidden" name="delete_user_id" value="<?= intval($user['user_id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php include __DIR__ . '/admin_footer.php'; ?>
`