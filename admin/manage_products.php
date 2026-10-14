<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin','staff']);
include __DIR__ . '/admin_header.php';

// Optional delete handler (simple, no CSRF; consider adding a POST+token later)
$notice = '';
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId > 0) {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $stmtDel->execute([$delId]);
            $notice = 'Product deleted.';
        } catch (Throwable $e) {
            $notice = 'Unable to delete product (it may be referenced by carts or orders).';
        }
    }
}

// Load products with category name
$stmt = $pdo->query("SELECT p.product_id, p.name, p.price, p.currency, c.name AS category_name
                     FROM products p
                     LEFT JOIN categories c ON c.category_id = p.category_id
                     ORDER BY p.product_id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container my-5">
    <div class="page-header">
        <h2 class="title">Manage Products</h2>
        <a href="add_product.php" class="btn btn-success">Add New Product</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-warning"><?= h($notice) ?></div>
    <?php endif; ?>

    <table class="table table-striped table-bordered">
        <thead class="table-success">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price (ZAR)</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $row): ?>
            <tr>
                <td><?= intval($row['product_id']) ?></td>
                <td><?= h($row['name']) ?></td>
                <td><?= h($row['category_name'] ?? '-') ?></td>
                <td><?= number_format((float)$row['price'], 2) ?></td>
                <td>
                    <a class="btn btn-sm btn-warning" href="edit_product.php?id=<?= intval($row['product_id']) ?>">Edit</a>
                    <a class="btn btn-sm btn-danger" href="manage_products.php?delete=<?= intval($row['product_id']) ?>" onclick="return confirm('Delete this product? This may fail if it is referenced by carts or orders.');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php include __DIR__ . '/admin_footer.php'; ?>
