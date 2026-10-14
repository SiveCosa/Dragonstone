<?php
// Redirect legacy product_detail.php to the new product page
$id = intval($_GET['id'] ?? 0);
header('Location: ' . (string)('..' . '/public/product.php?id=' . $id));
exit;
?>

<main class="container my-5">
    <div class="row">
        <div class="col-md-6">
            <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>"
                 class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="col-md-6">
            <h2 class="text-success"><?php echo htmlspecialchars($product['name']); ?></h2>
            <p class="lead"><?php echo htmlspecialchars($product['description']); ?></p>
            <h4 class="text-dark">R<?php echo number_format($product['price'], 2); ?></h4>
            <p><strong>Carbon Impact:</strong> Saves approximately <?php echo $product['carbon_score']; ?> kg CO₂ per unit</p>

            <form action="cart.php" method="POST" class="mb-3">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="number" name="quantity" value="1" min="1" class="form-control w-25 mb-2">
                <button type="submit" class="btn btn-success">Add to Cart</button>
            </form>

            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#carbonModal">
                Calculate Carbon Savings
            </button>
        </div>
    </div>
</main>

<!-- Carbon Calculator Modal -->
<div class="modal fade" id="carbonModal" tabindex="-1" aria-labelledby="carbonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="carbonModalLabel">Carbon Footprint Calculator</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Estimate how much CO₂ you’ll save with this product:</p>
                <input type="number" id="calcQuantity" class="form-control mb-3" min="1" value="1">
                <button id="calcBtn" class="btn btn-success w-100">Calculate</button>
                <div id="calcResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script src="public/assets/js/footprint.js"></script>
<?php include(__DIR__ . '/partials/footer.php'); ?>
