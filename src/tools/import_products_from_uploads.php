<?php
// src/tools/import_products_from_uploads.php
// One-time importer: scans public/uploads/products and creates missing products, assigns categories, and updates category tile images.
// SECURITY: Only allow when an admin is logged in.

require_once __DIR__ . '/../config.php';

ds_session_start();
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo "Forbidden: admin login required.";
    exit;
}

$dir = UPLOAD_PATH; // .../public/uploads/products/
if (!is_dir($dir)) {
    echo "Uploads directory not found: " . h($dir);
    exit;
}

// Load categories into a map by slug and name
$catsStmt = $pdo->query("SELECT category_id, name, slug FROM categories");
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);
$catByName = [];
$catBySlug = [];
foreach ($categories as $c) {
    $catByName[mb_strtolower($c['name'])] = (int)$c['category_id'];
    $catBySlug[mb_strtolower($c['slug'])] = (int)$c['category_id'];
}

// Keyword -> category slug mapping
$kwMap = [
    'cleaning' => 'cleaning', 'compost' => 'garden', 'trash' => 'cleaning', 'laundry' => 'cleaning', 'refill' => 'cleaning', 'bottle' => 'lifestyle', 'sponge' => 'cleaning', 'wool' => 'cleaning',
    'kitchen' => 'kitchen', 'beeswax' => 'kitchen', 'utensil' => 'kitchen', 'straw' => 'kitchen', 'silicone' => 'kitchen', 'jar' => 'kitchen', 'glass' => 'home',
    'home' => 'home', 'decor' => 'home', 'candle' => 'home', 'vase' => 'home', 'journal' => 'home', 'throw' => 'home', 'blanket' => 'home',
    'bathroom' => 'bathroom', 'towel' => 'bathroom', 'loofah' => 'bathroom', 'shampoo' => 'bathroom', 'soap' => 'bathroom', 'deodorant' => 'bathroom', 'skincare' => 'bathroom',
    'lifestyle' => 'lifestyle', 'yoga' => 'lifestyle', 'mindfulness' => 'lifestyle', 'bottle' => 'lifestyle', 'reusable' => 'lifestyle', 'tea' => 'lifestyle',
    'kids' => 'kids', 'baby' => 'kids', 'toy' => 'kids', 'pet' => 'kids',
    'outdoor' => 'garden', 'garden' => 'garden', 'solar' => 'garden', 'seed' => 'garden', 'fertilizer' => 'garden', 'planter' => 'garden', 'rainwater' => 'garden'
];

function pick_category_id_for_filename($fname, $catBySlug, $kwMap) {
    $lower = mb_strtolower($fname);
    foreach ($kwMap as $kw => $slug) {
        if (mb_strpos($lower, $kw) !== false) {
            $key = mb_strtolower($slug);
            return $catBySlug[$key] ?? null;
        }
    }
    return null;
}

// Utility to make a nice product name from filename
function name_from_filename($filename) {
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $base = str_replace(['_', '-', '(', ')'], ' ', $base);
    // collapse multiple spaces
    $base = trim(preg_replace('/\s+/', ' ', $base));
    // Capitalize words
    $parts = preg_split('/\s+/', $base);
    $parts = array_map(function($w){ return mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'); }, $parts);
    return implode(' ', $parts);
}

// Collect existing image basenames to avoid duplicates
$existing = $pdo->query("SELECT product_id, name, image_filename FROM products")->fetchAll(PDO::FETCH_ASSOC);
$byImage = [];
$byName  = [];
foreach ($existing as $row) {
    if (!empty($row['image_filename'])) $byImage[mb_strtolower($row['image_filename'])] = (int)$row['product_id'];
    $byName[mb_strtolower($row['name'])] = (int)$row['product_id'];
}

$created = 0; $updated = 0; $skipped = 0;
$perCatFirstImage = []; // for tile_image

$files = scandir($dir);
foreach ($files as $f) {
    if ($f === '.' || $f === '..') continue;
    if (!preg_match('/\.(jpe?g|png|webp|gif)$/i', $f)) continue;
    $rel = $f; // store basename only
    $lower = mb_strtolower($rel);

    // Determine target category
    $catId = pick_category_id_for_filename($f, $catBySlug, $kwMap);

    // Record first image per category for tile usage
    if ($catId && empty($perCatFirstImage[$catId])) {
        $perCatFirstImage[$catId] = $rel;
    }

    if (isset($byImage[$lower])) {
        // Already linked to some product; ensure category if missing and continue
        $pid = $byImage[$lower];
        if ($catId) {
            $stmt = $pdo->prepare("UPDATE products SET category_id = COALESCE(category_id, ?) WHERE product_id = ?");
            $stmt->execute([$catId, $pid]);
        }
        $skipped++;
        continue;
    }

    // Try match by name heuristic
    $guessName = name_from_filename($rel);
    $pid = $byName[mb_strtolower($guessName)] ?? null;

    if ($pid) {
        $stmt = $pdo->prepare("UPDATE products SET image_filename = ?, category_id = COALESCE(category_id, ?) WHERE product_id = ?");
        $stmt->execute([$rel, $catId, $pid]);
        $updated++;
        continue;
    }

    // Create new product with placeholder fields
    $sku = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/','', $guessName), 0, 8)) . '-' . rand(100,999);
    $price = 99.00 + (rand(0, 200) * 1.00); // 99 - 299 ZAR
    $stock = rand(10, 120);
    $points = (int)round($price * ECOPOINTS_PER_RAND);

    $ins = $pdo->prepare("INSERT INTO products (sku, name, description, category_id, price, currency, stock, image_filename, points_on_purchase) VALUES (?,?,?,?,?,?,?,?,?)");
    $desc = $guessName . ' — sustainable and eco-friendly.';
    $ins->execute([$sku, $guessName, $desc, $catId, $price, CURRENCY, $stock, $rel, $points]);
    $newId = (int)$pdo->lastInsertId();
    $byImage[$lower] = $newId;
    $byName[mb_strtolower($guessName)] = $newId;
    $created++;
}

// Update category tile images from first product image per category
foreach ($perCatFirstImage as $cid => $img) {
    $pdo->prepare("UPDATE categories SET tile_image = ? WHERE category_id = ?")->execute([$img, $cid]);
}

// Output summary
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Import Products from Uploads</title>';
echo '<link rel="stylesheet" href="' . APP_URL . '/public/assets/css/admin.css">';
echo '</head><body class="admin-login-page"><div class="login-container">';
echo '<h2>Import Complete</h2>';
echo '<p class="text-muted">Created: ' . (int)$created . ', Updated: ' . (int)$updated . ', Skipped(existing): ' . (int)$skipped . '</p>';
echo '<p><a class="btn btn-success" href="' . APP_URL . '/admin/manage_products.php">View Products</a></p>';
echo '<p><a class="btn btn-outline-success" href="' . APP_URL . '/public/index.php">Go to Storefront</a></p>';
echo '</div></body></html>';
