<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin','staff']);
include(__DIR__ . '/admin_header.php');
require_once __DIR__ . '/../src/helpers.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $category = sanitize($_POST['category']);
    $price = floatval($_POST['price']);
    $description = sanitize($_POST['description']);
    $eco_points = intval($_POST['eco_points']);

    // Handle file upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/products/";
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, description, eco_points, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $price, $description, $eco_points, $fileName]);
            $message = "Product added successfully.";
        } else {
            $message = "Error uploading image.";
        }
    } else {
        $message = "Please select a product image.";
    }
}
?>
<section class="admin-section">
    <h2>Add New Product</h2>
    <?php if ($message): ?><p class="msg"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <label>Name</label>
        <input type="text" name="name" required>
        <label>Category</label>
        <input type="text" name="category" required>
        <label>Price (ZAR)</label>
        <input type="number" name="price" step="0.01" required>
        <label>Description</label>
        <textarea name="description" required></textarea>
        <label>EcoPoints</label>
        <input type="number" name="eco_points" required>
        <label>Product Image</label>
        <input type="file" name="image" accept="image/*" required>
        <button type="submit" class="btn">Add Product</button>
    </form>
</section>
<?php include 'admin_footer.php'; ?>
