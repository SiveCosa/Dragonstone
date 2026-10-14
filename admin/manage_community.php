<?php
require_once __DIR__ . '/../src/config.php';

// Allow admin and staff
require_roles(['admin','staff']);

$stmt = $pdo->query("SELECT cp.post_id, cp.title, cp.created_at, cp.points_awarded, u.name AS user_name,
                     (SELECT COUNT(*) FROM community_comments cc WHERE cc.post_id = cp.post_id) AS comment_count
                     FROM community_posts cp 
                     JOIN users u ON cp.user_id = u.user_id
                     ORDER BY cp.created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/admin_header.php';
?>
<main class="container my-5">
    <h1 class="text-success mb-4">Manage Community Posts</h1>
    <table class="table table-striped table-bordered">
        <thead class="table-success">
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Title</th>
            <th>Points Awarded</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($posts as $row): ?>
            <tr>
                <td><?=intval($row['post_id'])?></td>
                <td><?=h($row['user_name'])?></td>
                <td><?=h($row['title'])?></td>
                <td><?=intval($row['points_awarded'])?></td>
                <td><?=h($row['created_at'])?></td>
                <td class="d-flex gap-1">
                    <a href="../public/post_detail.php?id=<?=intval($row['post_id'])?>" target="_blank" class="btn btn-sm btn-outline-success">View</a>
                    <a href="moderate_comments.php?post_id=<?=intval($row['post_id'])?>" class="btn btn-sm btn-warning">
                        Moderate / Delete <span class="badge bg-danger"><?= intval($row['comment_count'] ?? 0) ?></span>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
<?php include __DIR__ . '/admin_footer.php'; ?>
