<?php
require_once __DIR__ . '/../src/config.php';
include(__DIR__ . '/partials/header.php');

$post_id = intval($_GET['id'] ?? 0);
if ($post_id <= 0) {
    echo "<div class='alert alert-warning'>Invalid post.</div>";
    include __DIR__ . '/partials/footer.php';
    exit;
}

// Load post with user name
$stmt = $pdo->prepare("SELECT cp.post_id, cp.title, cp.body, cp.created_at, u.name AS user_name
                       FROM community_posts cp
                       JOIN users u ON cp.user_id = u.user_id
                       WHERE cp.post_id = ? LIMIT 1");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    echo "<div class='alert alert-warning'>Post not found.</div>";
    include __DIR__ . '/partials/footer.php';
    exit;
}

// Load comments
$cstmt = $pdo->prepare("SELECT cc.comment_id, cc.body, cc.created_at, u.name AS user_name
                        FROM community_comments cc
                        JOIN users u ON cc.user_id = u.user_id
                        WHERE cc.post_id = ?
                        ORDER BY cc.created_at ASC");
$cstmt->execute([$post_id]);
$comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="container my-5">
    <div class="card p-4 shadow-sm">
        <h2 class="text-success"><?php echo h($post['title']); ?></h2>
        <p><?php echo nl2br(h($post['body'])); ?></p>
        <small class="text-muted">By <?php echo h($post['user_name']); ?> • <?php echo h($post['created_at']); ?></small>
    </div>

    <div class="comments-section mt-5">
        <h4 class="text-success">Comments</h4>
        <?php if(isset($_SESSION['user_id'])): ?>
            <form id="commentForm" class="mb-4" method="POST" action="<?=APP_URL?>/src/api/api_comment.php">
                <textarea name="body" class="form-control mb-2" placeholder="Add a comment..." required></textarea>
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <input type="hidden" name="csrf_token" value="<?=h(generate_csrf_token())?>">
                <button type="submit" class="btn btn-success">Post Comment</button>
            </form>
        <?php else: ?>
            <p><a href="<?=APP_URL?>/login.php">Login</a> to comment.</p>
        <?php endif; ?>

        <?php foreach ($comments as $row): ?>
            <div class="border rounded p-3 mb-2">
                <strong><?php echo h($row['user_name']); ?>:</strong>
                <p><?php echo h($row['body']); ?></p>
                <small class="text-muted"><?php echo h($row['created_at']); ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php include(__DIR__ . '/partials/footer.php'); ?>
