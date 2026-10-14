<?php
require_once __DIR__ . '/../src/config.php';
include(__DIR__ . '/partials/header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "/login.php");
    exit;
}

$userId = intval($_SESSION['user_id']);
$stmt = $pdo->prepare("SELECT order_id, created_at, total_amount AS total, status, tracking_number, discount_applied, eco_reward_used
                       FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container my-5">
    <h2 class="text-success mb-4">🌍 Your Orders</h2>

    <?php if (!empty($orders)): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-success">
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Total (ZAR)</th>
                <th>Status</th>
                <th>Tracking No.</th>
                <th>Discount</th>
                <th>Eco Reward</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= intval($order['order_id']) ?></td>
                    <td><?= h(date("Y-m-d", strtotime($order['created_at']))) ?></td>
                    <td>R<?= number_format((float)$order['total'], 2) ?></td>
                    <td>
                        <?php
                        $badge = 'bg-secondary';
                        if ($order['status'] === 'Delivered') $badge = 'bg-success';
                        elseif ($order['status'] === 'Shipped') $badge = 'bg-success';
                        elseif ($order['status'] === 'Processing') $badge = 'bg-warning';
                        elseif ($order['status'] === 'Cancelled') $badge = 'bg-danger';
                        ?>
                        <span class="badge <?= $badge ?>"><?= h($order['status']) ?></span>
                    </td>
                    <td><?= h($order['tracking_number'] ?: '-') ?></td>
                    <td><?= $order['discount_applied'] ? ('R' . number_format((float)$order['discount_applied'], 2)) : '-' ?></td>
                    <td>
                        <?php if (!empty($order['eco_reward_used'])): 
                            $rstmt = $pdo->prepare("SELECT name FROM rewards WHERE id = ?");
                            $rstmt->execute([intval($order['eco_reward_used'])]);
                            $rname = $rstmt->fetchColumn();
                            echo h($rname ?: '-');
                        else:
                            echo '-';
                        endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have no recent orders.</p>
    <?php endif; ?>
</main>

<?php include(__DIR__ . '/partials/footer.php'); ?>
