<?php
require_once __DIR__ . '/../src/config.php';
include(__DIR__ . '/partials/header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "/login.php");
    exit;
}

$userId = intval($_SESSION['user_id']);

// Load cart from DB (authoritative)
$cartId = null;
$cartStmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
$cartStmt->execute([$userId]);
$cartRow = $cartStmt->fetch(PDO::FETCH_ASSOC);
if ($cartRow) { $cartId = intval($cartRow['cart_id']); }

$items = [];
if ($cartId) {
    $itStmt = $pdo->prepare("SELECT ci.id AS cart_item_id, ci.quantity, p.product_id, p.name, p.price, p.currency, p.image_filename
                              FROM cart_items ci
                              JOIN products p ON p.product_id = ci.product_id
                              WHERE ci.cart_id = ?");
    $itStmt->execute([$cartId]);
    $items = $itStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Rewards / discount
$rewardId = isset($_SESSION['reward_id']) ? intval($_SESSION['reward_id']) : null;
$discount = 0.0; // percent
if ($rewardId) {
    $stmt = $pdo->prepare("SELECT reward_type, value FROM rewards WHERE id = ? LIMIT 1");
    $stmt->execute([$rewardId]);
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($reward && $reward['reward_type'] === 'discount') {
        $discount = (float)$reward['value'];
    }
}

// Totals
$subtotal = 0.0;
foreach ($items as $i) { $subtotal += ((float)$i['price'] * (int)$i['quantity']); }
$discountValue = ($discount / 100.0) * $subtotal;
$finalTotal = max(0, $subtotal - $discountValue);
?>

<main class="container my-5">
    <h2 class="text-success mb-4">Checkout</h2>

    <?php if (!$items): ?>
        <div class="alert alert-info">Your cart is empty. <a href="<?= APP_URL ?>/public/category.php">Continue shopping</a></div>
    <?php else: ?>
    <div class="row g-3">
        <div class="col-md-7">
            <div class="card p-3 shadow-sm">
                <h4 class="mb-3">Order Summary</h4>
                <table class="table table-striped">
                    <thead class="table-success">
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it):
                            $line = (float)$it['price'] * (int)$it['quantity'];
                            // image resolve
                            $thumb='';
                            if (!empty($it['image_filename']) && is_file(UPLOAD_PATH . basename($it['image_filename']))) {
                                $thumb = ds_upload_url(basename($it['image_filename']));
                            } else {
                                $g = ds_find_image_for_keywords($it['name']);
                                if ($g && is_file(UPLOAD_PATH . $g)) { $thumb = ds_upload_url($g); }
                            }
                            if ($thumb === '') { $thumb = APP_URL . '/public/assets/images/hero.jpg'; }
                            ?>
                            <tr>
                                <td>
                                    <div class="cart-thumb">
                                        <img src="<?= h($thumb) ?>" alt="<?= h($it['name']) ?>">
                                        <div class="name"><?= h($it['name']) ?></div>
                                    </div>
                                </td>
                                <td class="text-end">R<?= number_format((float)$it['price'], 2) ?></td>
                                <td class="text-end"><?= intval($it['quantity']) ?></td>
                                <td class="text-end">R<?= number_format($line, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card p-4 shadow-sm">
                <h4 class="mb-3">Totals</h4>
                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong>R<?= number_format($subtotal, 2) ?></strong></div>
                <?php if ($discount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success"><span>EcoReward (<?= number_format($discount,0) ?>% off)</span><strong>-R<?= number_format($discountValue, 2) ?></strong></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between border-top pt-2"><span>Total</span><strong class="text-success">R<?= number_format($finalTotal, 2) ?></strong></div>
                <form action="webhook_paypal.php" method="POST" class="checkout-actions mt-3">
                    <input type="hidden" name="total" value="<?= number_format($finalTotal, 2, '.', '') ?>">
                    <input type="hidden" name="eco_reward_used" value="<?= $rewardId ?? '' ?>">
                    <button type="submit" class="btn btn-success btn-lg w-100">Proceed to Payment</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include(__DIR__ . '/partials/footer.php'); ?>
