<?php
include(__DIR__ . '/admin_header.php');
$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<p>Order not found.</p>";
    include 'admin_footer.php';
    exit();
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$id]);
?>
<section class="admin-section">
    <h2>Order #<?= $order['id'] ?></h2>
    <p><strong>User:</strong> <?= htmlspecialchars($order['user_email']) ?></p>
    <p><strong>Total:</strong> ZAR <?= number_format($order['total'], 2) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
    <h3>Items</h3>
    <table>
        <tr><th>Product</th><th>Qty</th><th>Price</th></tr>
        <?php while ($item = $items->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>ZAR <?= number_format($item['price'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</section>
<?php include 'admin_footer.php'; ?>
