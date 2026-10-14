<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin','staff']);
include __DIR__ . '/admin_header.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<main class='container my-5'><div class='alert alert-warning'>Invalid product ID.</div></main>";
    include __DIR__ . '/admin_footer.php';
    exit();
}

// Load product with current schema
$stmt = $pdo->prepare("SELECT product_id, sku, name, description, category_id, price, currency, stock, image_filename, points_on_purchase FROM products WHERE product_id = ? LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    echo "<main class='container my-5'><div class='alert alert-warning'>Product not found.</div></main>";
    include __DIR__ . '/admin_footer.php';
    exit();
}

// Load categories for dropdown
$cats = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $message = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $currency = substr(trim($_POST['currency'] ?? (CURRENCY ?? 'ZAR')), 0, 3);
        $stock = max(0, intval($_POST['stock'] ?? 0));
        $points = max(0, intval($_POST['points_on_purchase'] ?? 0));

        // Handle optional image upload to products uploads dir
        $image_filename = $product['image_filename'];
        if (!empty($_FILES['image']['name'])) {
            $safeBase = preg_replace('/[^A-Za-z0-9_\.-]/','_', basename($_FILES['image']['name']));
            $newName = time() . '_' . $safeBase;
            $target = UPLOAD_PATH . $newName;
            if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0777, true); }
            if (@move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_filename = $newName;
            }
        }

        $upd = $pdo->prepare("UPDATE products SET name = ?, description = ?, category_id = ?, price = ?, currency = ?, stock = ?, image_filename = ?, points_on_purchase = ? WHERE product_id = ?");
        $upd->execute([$name, $description, $category_id ?: null, $price, $currency, $stock, $image_filename, $points, $id]);
        $message = 'Product updated successfully.';
        // Refresh product
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<main class="container my-5">
    <h2 class="text-success mb-3">Edit Product</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="card p-4 shadow-sm">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input class="form-control" type="text" name="name" value="<?= h($product['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-control" name="category_id">
                <option value="">-- None --</option>
                <?php foreach ($cats as $c): $sel = ($product['category_id'] == $c['category_id']) ? 'selected' : ''; ?>
                    <option value="<?= intval($c['category_id']) ?>" <?= $sel ?>><?= h($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Price (ZAR)</label>
                <input class="form-control" type="number" step="0.01" name="price" value="<?= h($product['price']) ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Currency</label>
                <input class="form-control" type="text" name="currency" maxlength="3" value="<?= h($product['currency'] ?? CURRENCY) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Stock</label>
                <input class="form-control" type="number" name="stock" value="<?= intval($product['stock']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Eco Points on Purchase</label>
                <input class="form-control" type="number" name="points_on_purchase" value="<?= intval($product['points_on_purchase']) ?>">
            </div>
        </div>
        <div class="mb-3 mt-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4"><?= h($product['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Product Image</label>
            <input class="form-control" type="file" name="image" accept="image/*">
            <?php
                $currentImg = '';
                if (!empty($product['image_filename']) && is_file(UPLOAD_PATH . basename($product['image_filename']))) {
                    $currentImg = ds_upload_url(basename($product['image_filename']));
                }
            ?>
            <?php if ($currentImg): ?>
                <p class="mt-2">Current Image:<br><img src="<?= h($currentImg) ?>" style="max-height:100px;border-radius:8px"></p>
            <?php endif; ?>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="manage_products.php" class="btn btn-outline-success">Back to Products</a>
        </div>
    </form>
</main>
<?php include __DIR__ . '/admin_footer.php'; ?>
