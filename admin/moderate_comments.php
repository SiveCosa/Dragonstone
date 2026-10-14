<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin','staff']);
include __DIR__ . '/admin_header.php';

$post_id = intval($_GET['post_id'] ?? 0);
if ($post_id <= 0) {
    echo "<main class='container my-5'><div class='alert alert-warning'>Invalid post ID.</div></main>";
    include __DIR__ . '/admin_footer.php';
    exit;
}

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $notice = 'Invalid CSRF token.';
    } else {
        $comment_id = (int)($_POST['delete_comment_id'] ?? 0);
        if ($comment_id > 0) {
            try {
                // Delete only comments that belong to this post for safety
                $del = $pdo->prepare("DELETE FROM community_comments WHERE comment_id = ? AND post_id = ?");
                $del->execute([$comment_id, $post_id]);
                if ($del->rowCount() > 0) {
                    $notice = 'Comment deleted.';
                } else {
                    $notice = 'Comment not found or already deleted.';
                }
            } catch (Throwable $e) {
                $notice = 'Unable to delete comment.';
            }
        }
    }
}

// Load the post header info
$pstmt = $pdo->prepare("SELECT cp.post_id, cp.title, cp.created_at, u.name AS user_name FROM community_posts cp JOIN users u ON cp.user_id = u.user_id WHERE cp.post_id = ? LIMIT 1");
$pstmt->execute([$post_id]);
$post = $pstmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    echo "<main class='container my-5'><div class='alert alert-warning'>Post not found.</div></main>";
    include __DIR__ . '/admin_footer.php';
    exit;
}

// Load comments for this post
$cstmt = $pdo->prepare("SELECT cc.comment_id, cc.body, cc.created_at, u.name AS user_name, u.email
                        FROM community_comments cc
                        JOIN users u ON u.user_id = cc.user_id
                        WHERE cc.post_id = ?
                        ORDER BY cc.created_at ASC");
$cstmt->execute([$post_id]);
$comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container my-5">
    <div class="page-header">
        <h2 class="title">Moderate Comments</h2>
        <a href="manage_community.php" class="btn btn-outline-success">Back to Posts</a>
    </div>

    <div class="card p-3 mb-4">
        <h5 class="mb-1">Post: <?= h($post['title']) ?></h5>
        <small class="text-muted">by <?= h($post['user_name']) ?> • <?= h($post['created_at']) ?></small>
        <a class="btn btn-sm btn-link" target="_blank" href="<?= APP_URL ?>/public/post_detail.php?id=<?= intval($post['post_id']) ?>">Open public view</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-warning"><?= h($notice) ?></div>
    <?php endif; ?>

    <?php if (!$comments): ?>
        <div class="alert alert-info">No comments for this post yet.</div>
    <?php else: ?>
        <table class="table table-striped table-bordered">
            <thead class="table-success">
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($comments as $c): ?>
                <tr>
                    <td><?= intval($c['comment_id']) ?></td>
                    <td><?= h($c['user_name']) ?><br><small class="text-muted"><?= h($c['email']) ?></small></td>
                    <td><?= nl2br(h($c['body'])) ?></td>
                    <td><?= h($c['created_at']) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete this comment?');">
                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                            <input type="hidden" name="delete_comment_id" value="<?= intval($c['comment_id']) ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/admin_footer.php'; ?>
