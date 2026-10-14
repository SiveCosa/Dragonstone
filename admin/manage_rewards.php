<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin']);
include(__DIR__ . '/admin_header.php');

$stmt = $pdo->query("SELECT * FROM rewards ORDER BY created_at DESC");
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container my-5">
    <h2 class="text-success mb-4">Manage EcoRewards</h2>
    <a href="add_reward.php" class="btn btn-success mb-3">Add New Reward</a>
    <table class="table table-bordered table-striped">
        <thead class="table-success">
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Points</th>
            <th>Type</th>
            <th>Value</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rewards as $reward): ?>
            <tr>
                <td><?=intval($reward['id'])?></td>
                <td><img src="../uploads/rewards/<?=h($reward['image'])?>" width="80" alt=""></td>
                <td><?=h($reward['name'])?></td>
                <td><?=intval($reward['points_required'])?></td>
                <td><?=h($reward['reward_type'])?></td>
                <td><?=number_format((float)$reward['value'], 2)?></td>
                <td>
                    <a href="edit_reward.php?id=<?=intval($reward['id'])?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_reward.php?id=<?=intval($reward['id'])?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this reward?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php include 'admin_footer.php'; ?>
