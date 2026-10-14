<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin']);
include(__DIR__ . '/admin_header.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $points = intval($_POST['points_required'] ?? 0);
    $type = $_POST['reward_type'] ?? 'discount';
    $value = (float)($_POST['value'] ?? 0);

    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . '/../public/uploads/rewards/';
        if (!is_dir($targetDir)) { @mkdir($targetDir, 0777, true); }
        $basename = preg_replace('/[^A-Za-z0-9_\.-]/','_', basename($_FILES["image"]["name"]));
        $image = time() . '_' . $basename;
        @move_uploaded_file($_FILES["image"]["tmp_name"], $targetDir . $image);
    }

    $stmt = $pdo->prepare("INSERT INTO rewards (name, description, points_required, reward_type, value, image)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $points, $type, $value, $image]);

    header("Location: manage_rewards.php");
    exit;
}
?>

<main class="container my-5">
    <h2 class="text-success mb-4">Add New Reward</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Reward Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Points Required</label>
            <input type="number" name="points_required" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Reward Type</label>
            <select name="reward_type" class="form-control">
                <option value="discount">Discount</option>
                <option value="voucher">Voucher</option>
                <option value="gift">Gift</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Value (e.g. 10 = 10% off)</label>
            <input type="number" step="0.01" name="value" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control">
        </div>
        <button type="submit" class="btn btn-success w-100">Add Reward</button>
    </form>
</main>

<?php include 'admin_footer.php'; ?>
