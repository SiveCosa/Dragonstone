<?php
// public/product.php
require_once __DIR__ . '/../src/config.php';

// Fetch product by product_id using PDO and join footprint
$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.product_id, p.sku, p.name, p.description, p.price, p.currency, p.stock, p.image_filename,
                              pf.kg_co2e_per_unit, pf.shipping_kg_co2e_estimate, pf.breakdown_json
                       FROM products p
                       LEFT JOIN product_footprint pf ON pf.product_id = p.product_id
                       WHERE p.product_id = ? LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = $product['name'] ?? 'Product';
include(__DIR__ . '/partials/header.php');

if (!$product) {
    echo "<div class='alert alert-warning'>Product not found.</div>";
    include __DIR__ . '/partials/footer.php';
    exit;
}

$img = '';
if (!empty($product['image_filename'])) {
    $candidate = basename($product['image_filename']);
    $path = UPLOAD_PATH . $candidate;
    if (is_file($path)) { $img = ds_upload_url($candidate); }
}
if ($img === '') {
    $guess = ds_find_image_for_keywords($product['name'] ?? '');
    if ($guess && is_file(UPLOAD_PATH . $guess)) {
        $img = ds_upload_url($guess);
    } else {
        $img = APP_URL . '/public/assets/images/hero.jpg';
    }
}
?>
<div class="container product-page my-5">
    <div class="row">
        <div class="col-md-6 product-gallery">
            <img src="<?=h($img)?>" class="img-fluid" alt="<?=h($product['name'])?>">
        </div>
        <div class="col-md-6">
            <h2><?=h($product['name'])?></h2>
            <p class="product-meta"><?=h($product['description'])?></p>
            <div class="product-price">R<?=number_format((float)$product['price'], 2)?> <small class="text-muted"><?=h($product['currency'] ?? CURRENCY)?></small></div>

            <div class="my-3">
                <strong>Carbon Footprint</strong>
                <?php
                $kg_unit = (float)($product['kg_co2e_per_unit'] ?? 0) + (float)($product['shipping_kg_co2e_estimate'] ?? 0);
                $badge = $kg_unit <= 0.5 ? 'success' : ($kg_unit <= 2.0 ? 'warning' : 'danger');
                ?>
                <p class="mb-1"><span class="badge bg-<?=$badge?>"><?=number_format($kg_unit, 3)?> kg CO₂e per unit</span></p>
                <?php if (!empty($product['breakdown_json'])):
                    $bd = json_decode($product['breakdown_json'], true);
                    if (is_array($bd)):
                    ?>
                    <small class="text-muted">Breakdown:</small>
                    <ul class="small">
                        <?php foreach ($bd as $k=>$v): ?>
                            <li><?=h(ucfirst((string)$k))?>: <?=number_format((float)$v,3)?> kg</li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; endif; ?>
            </div>

            <form method="post" action="<?=APP_URL?>/src/actions/cart_add.php" class="product-actions">
                <input type="hidden" name="product_id" value="<?=intval($product['product_id'])?>">
                <input type="hidden" name="csrf_token" value="<?=h(generate_csrf_token())?>">
                <div class="quantity-group">
                    <label class="me-2">Quantity</label>
                    <input class="form-control" style="width:90px" type="number" name="quantity" value="1" min="1">
                </div>
                <button type="submit" class="btn btn-success">Add to Cart</button>
            </form>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/partials/footer.php'); ?>
