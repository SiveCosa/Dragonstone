<?php
// public/category.php
require_once __DIR__ . '/../src/config.php';

// Support both slug and name from query
$slug = trim($_GET['slug'] ?? '');
$name = trim($_GET['name'] ?? '');

// Resolve category by slug or name; if none provided, list all categories
if ($slug === '' && $name !== '') {
    $stmt = $pdo->prepare("SELECT slug FROM categories WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) { $slug = $row['slug']; }
}

include(__DIR__ . '/partials/header.php');
?>
<div class="container category-page my-5">
<?php
if ($slug === '') {
    // List all categories
    $c = $pdo->query("SELECT category_id, name, slug, tile_image FROM categories ORDER BY name");
    $cats = $c->fetchAll(PDO::FETCH_ASSOC);
    echo '<h1>All Categories</h1><div class="row g-3">';
    foreach ($cats as $cat) {
        $imgUrl = '';
        // Enforce specific images FIRST (always override for these three categories)
        $enforced = [
            'home decor & living' => 'Soy wax candles with natural essential oils.jpg',
            'kids & pets' => 'Natural pet grooming foam.jpg',
            'lifestyle & wellness' => 'Mindfulness kit with sustainably sourced incense.jpg',
        ];
        $nameKey = mb_strtolower(str_replace('é','e', $cat['name']));
        if (isset($enforced[$nameKey]) && ds_upload_exists($enforced[$nameKey])) {
            $imgUrl = ds_upload_url($enforced[$nameKey]);
        } else {
            // Otherwise use DB tile_image or heuristic match from uploads
            if (!empty($cat['tile_image']) && ds_upload_exists($cat['tile_image'])) {
                $imgUrl = ds_upload_url($cat['tile_image']);
            } else {
                $guess = ds_find_image_for_keywords($cat['name']);
                if ($guess && ds_upload_exists($guess)) { $imgUrl = ds_upload_url($guess); }
            }
        }
        if ($imgUrl === '') { $imgUrl = APP_URL . '/public/assets/images/hero.jpg'; }
        echo '<div class="col-6 col-md-3"><a class="card product-card h-100 text-decoration-none" href="'.APP_URL.'/public/category.php?slug='.urlencode($cat['slug']).'">';
        echo '<img src="'.h($imgUrl).'" class="card-img-top" alt="'.h($cat['name']).'">';
        echo '<div class="card-body"><h6>'.h($cat['name']).'</h6></div></a></div>';
    }
    echo '</div>'; echo '</div>'; include __DIR__ . '/partials/footer.php'; exit;
}

// Load selected category
$stmt = $pdo->prepare("SELECT category_id, name, slug FROM categories WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$cat = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cat) {
    echo "<div class='alert alert-warning'>Category not found.</div>";
    echo '</div>';
    include __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = h($cat['name']) . " - Category";

// Load products in category
$pr = $pdo->prepare("SELECT product_id, name, price, currency, image_filename FROM products WHERE category_id = ? ORDER BY name");
$pr->execute([$cat['category_id']]);
$products = $pr->fetchAll(PDO::FETCH_ASSOC);
?>
<h1><?= h($cat['name']) ?></h1>
<?php if ($products): ?>
    <div class="row g-3 mt-2">
        <?php foreach ($products as $p):
            $img = '';
            if (!empty($p['image_filename'])) {
                $cand = basename($p['image_filename']);
                if (is_file(UPLOAD_PATH . $cand)) { $img = ds_upload_url($cand); }
            }
            if ($img === '') {
                $guess = ds_find_image_for_keywords($p['name']);
                if ($guess && is_file(UPLOAD_PATH . $guess)) { $img = ds_upload_url($guess); }
            }
            if ($img === '') { $img = APP_URL . '/public/assets/images/hero.jpg'; }
            ?>
            <div class="col-6 col-md-3">
                <div class="card product-card h-100">
                    <img src="<?= h($img) ?>" class="card-img-top" alt="<?= h($p['name']) ?>">
                    <div class="card-body">
                        <h6 class="card-title" title="<?= h($p['name']) ?>"><?= h(mb_strimwidth($p['name'], 0, 48, '…')) ?></h6>
                        <div class="cta-row">
                            <div class="price">R<?= number_format((float)$p['price'], 2) ?> <small class="text-muted"><?= h($p['currency'] ?? CURRENCY) ?></small></div>
                            <a href="<?= APP_URL ?>/public/product.php?id=<?= intval($p['product_id']) ?>" class="btn btn-sm btn-outline-success">View</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info mt-3">No products in this category yet.</div>
<?php endif; ?>
</div>
<?php include(__DIR__ . '/partials/footer.php'); ?>
