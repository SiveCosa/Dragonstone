<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin']);
include __DIR__ . '/admin_header.php';

// Handle updates (status + tracking) using PDO and current schema
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $tracking = trim($_POST['tracking_number'] ?? '');

    $allowed = ['Pending','Processing','Shipped','Delivered','Cancelled'];
    if (!in_array($status, $allowed, true)) { $status = 'Pending'; }

    if ($orderId > 0) {
       $upd = $pdo->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE order_id = ?");
       $upd->execute([$status, $tracking, $orderId]);
       header("Location: manage_orders.php?updated=1");
       exit;
    }
}

// Load orders (most recent first)
$stmt = $pdo->query("SELECT order_id, user_id, total_amount, status, tracking_number, created_at FROM orders ORDER BY created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container my-5">
    <h2 class="text-success mb-4">Manage Orders</h2>

    <table class="table table-striped table-bordered">
        <thead class="table-success">
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Total</th>
            <th>Status</th>
            <th>Tracking No.</th>
            <th>Update</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?php echo intval($o['order_id']); ?></td>
                <td><?php echo intval($o['user_id']); ?></td>
                <td>R<?php echo number_format((float)$o['total_amount'], 2); ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?php echo intval($o['order_id']); ?>">
                        <select name="status" class="form-select">
                            <?php
                            $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
                            foreach ($statuses as $s) {
                                $sel = ($o['status'] === $s) ? ' selected' : '';
                                echo "<option value=\"".htmlspecialchars($s)."\"$sel>".htmlspecialchars($s)."</option>";
                            }
                            ?>
                        </select>
                </td>
                <td>
                    <input type="text" name="tracking_number" class="form-control" value="<?php echo htmlspecialchars($o['tracking_number'] ?? ''); ?>">
                </td>
                <td>
                    <button type="submit" class="btn btn-success btn-sm">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php include __DIR__ . '/admin_footer.php'; ?>
