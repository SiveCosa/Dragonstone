<?php
require_once __DIR__ . '/../src/config.php';

$pageTitle = 'Your Cart';
include(__DIR__ . '/partials/header.php');

$uid = current_user_id();
if (!$uid) { echo "<div class='alert'>Please <a href='" . APP_URL . "/login.php'>login</a> to view your cart</div>"; include __DIR__ . '/partials/footer.php'; exit; }

$stmt = $pdo->prepare("SELECT c.cart_id FROM carts c WHERE c.user_id = ?");
$stmt->execute([$uid]); $cart = $stmt->fetch(PDO::FETCH_ASSOC);
$items = [];
if ($cart) {
    $stmt = $pdo->prepare("SELECT ci.id, ci.quantity, p.product_id, p.name, p.price, p.currency, p.image_filename FROM cart_items ci JOIN products p ON p.product_id = ci.product_id WHERE ci.cart_id = ?");
    $stmt->execute([$cart['cart_id']]); $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total = 0;
?>
    <h1>Your Cart</h1>
<?php if (empty($items)): ?>
    <div class="alert">Your cart is empty. <a href="<?=APP_URL?>/public/index.php">Shop now</a></div>
<?php else: ?>
    <table class="cart-table">
        <thead>
            <tr>
                <th class="col-product">Product</th>
                <th class="col-price">Price</th>
                <th class="col-qty">Quantity</th>
                <th class="col-subtotal">Subtotal</th>
                <th class="col-action">Remove</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it):
            $sub = (float)$it['price'] * (int)$it['quantity']; $total += $sub;
            // thumbnail resolve
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
                <td class="col-product">
                    <div class="cart-thumb">
                        <img src="<?=h($thumb)?>" alt="<?=h($it['name'])?>">
                        <div class="name"><a href="<?= APP_URL ?>/public/product.php?id=<?= intval($it['product_id']) ?>" class="text-decoration-none"><?= h($it['name']) ?></a></div>
                    </div>
                </td>
                <td class="col-price">R<?=number_format((float)$it['price'],2)?></td>
                <td class="col-qty"><?=intval($it['quantity'])?></td>
                <td class="col-subtotal">R<?=number_format($sub,2)?></td>
                <td class="col-action">
                    <form method="post" action="cart.php" class="d-inline">
                        <input type="hidden" name="remove_id" value="<?=intval($it['id'])?>">
                        <input type="hidden" name="ds_csrf" value="<?=h(ds_csrf_token())?>">
                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="cart-summary">
        <div class="total">Total: R<?=number_format($total,2)?></div>
        <a class="btn btn-success" href="checkout.php">Checkout with PayPal</a>
    </div>
<?php endif; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    if (!ds_verify_csrf($_POST['ds_csrf'] ?? '')) {
        if (function_exists('flash_error')) { flash_error('Invalid request. Please try again.'); }
    } else {
        $rid = intval($_POST['remove_id']);
        $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = (SELECT cart_id FROM carts WHERE user_id = ?)")
            ->execute([$rid, $uid]);
        if (function_exists('flash_success')) { flash_success('Item removed from your cart.'); }
    }
    header("Location: cart.php");
    exit;
}
include __DIR__ . '/partials/footer.php';
