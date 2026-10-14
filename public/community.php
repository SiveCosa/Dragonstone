<?php
require_once __DIR__ . '/../src/config.php';
include(__DIR__ . '/partials/header.php');

// Fetch community posts with PDO and current schema
$stmt = $pdo->query("SELECT cp.post_id, cp.title, cp.body, cp.created_at, u.name AS user_name
                     FROM community_posts cp 
                     JOIN users u ON cp.user_id = u.user_id 
                     ORDER BY cp.created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="community-section container my-5">
    <h1 class="text-center mb-4 text-success">DragonStone Community Hub</h1>

    <?php if(isset($_SESSION['user_id'])): ?>
        <div class="card p-4 mb-5 shadow-sm">
            <h4 class="text-success">Share your eco-friendly idea 🌿</h4>
            <form id="postForm" enctype="multipart/form-data" method="POST" action="<?= APP_URL ?>/src/api/api_community_post.php">
                <input type="text" name="title" placeholder="Post title" class="form-control mb-2" required>
                <textarea name="body" placeholder="Share your tips, DIY projects, or ideas..."
                          class="form-control mb-2" rows="4" required></textarea>
                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                <button type="submit" class="btn btn-success">Post</button>
            </form>
        </div>
    <?php else: ?>
        <p class="text-center">Please <a href="<?= APP_URL ?>/login.php">login</a> to post or comment.</p>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach($posts as $row): ?>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-success"><?= h($row['title']) ?></h5>
                        <p class="card-text"><?= h(mb_strimwidth($row['body'], 0, 120, '…')) ?></p>
                        <small class="text-muted">By <?= h($row['user_name']) ?> • <?= h($row['created_at']) ?></small>
                        <a href="post_detail.php?id=<?= intval($row['post_id']) ?>" class="btn btn-outline-success btn-sm mt-2">Read More</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<script src="<?= APP_URL ?>/public/assets/js/community.js"></script>
<?php include(__DIR__ . '/partials/footer.php'); ?>
