<?php
require_once __DIR__ . '/../src/config.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    if (function_exists('flash_info')) { flash_info('Please log in to view your account.'); }
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Fetch user profile (current schema)
$uStmt = $pdo->prepare('SELECT user_id, name, email, eco_points, role_key, created_at FROM users WHERE user_id = ? LIMIT 1');
$uStmt->execute([$userId]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC);
$ecoPoints = (int)($user['eco_points'] ?? 0);

// Fetch rewards
$rStmt = $pdo->query('SELECT id, name, description, points_required, image FROM rewards ORDER BY points_required ASC');
$rewards = $rStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Your Account';
include(__DIR__ . '/partials/header.php');
?>

<main class="container my-5">
    <h2 class="text-success mb-4">🌿 Your Account</h2>

    <div class="card mb-4 shadow-sm p-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <h4 class="mb-1">Welcome, <?= h($user['name'] ?? '') ?></h4>
                <div class="text-muted">Email: <?= h($user['email'] ?? '') ?></div>
                <div class="text-muted">Member since: <?= h(isset($user['created_at']) ? date('Y-m-d', strtotime($user['created_at'])) : '') ?></div>
            </div>
            <div class="col-md-4 text-end">
                <div class="h5 mb-0">EcoPoints Balance</div>
                <div class="h3 text-success fw-bold"><?= (int)$ecoPoints ?> pts</div>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <a class="btn btn-outline-success" href="<?= APP_URL ?>/public/order_tracking.php">View Orders</a>
            <a class="btn btn-outline-success" href="<?= APP_URL ?>/public/cart.php">Go to Cart</a>
        </div>
    </div>

    <h3 class="text-dark mb-3">Available Rewards</h3>
    <div class="row g-3">
        <?php if ($rewards): foreach ($rewards as $reward): ?>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <?php $img = !empty($reward['image']) ? (APP_URL . '/public/uploads/rewards/' . h($reward['image'])) : (APP_URL . '/public/assets/images/hero.jpg'); ?>
                    <img src="<?= $img ?>" class="card-img-top" alt="<?= h($reward['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title text-success mb-1"><?= h($reward['name']) ?></h5>
                        <p class="card-text small text-muted"><?= h($reward['description'] ?? '') ?></p>
                        <p class="mb-2"><strong>Requires:</strong> <?= (int)$reward['points_required'] ?> Points</p>
                        <form action="<?= APP_URL ?>/public/redeem_reward.php" method="POST">
                            <input type="hidden" name="reward_id" value="<?= intval($reward['id']) ?>">
                            <button type="submit" class="btn btn-success w-100" <?= ($ecoPoints < (int)$reward['points_required']) ? 'disabled' : '' ?>>
                                Redeem
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; else: ?>
            <div class="col-12"><div class="alert alert-info">No rewards available yet.</div></div>
        <?php endif; ?>
    </div>
</main>

<?php include(__DIR__ . '/partials/footer.php'); ?>
