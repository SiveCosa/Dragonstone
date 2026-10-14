<?php require_once __DIR__ . '/../src/config.php'; ?>
<?php include(__DIR__ . '/partials/header.php'); ?>

<section class="hero">
    <h1>Live Sustainably with Style</h1>
</section>

<section class="container">
    <h2 style="margin-top:2rem;">Shop by Category</h2>
    <div class="categories">
        <?php
        // Prefer DB-driven categories with tile images; fallback to heuristic mapping from uploads/products
        try {
            $catsStmt = $pdo->query("SELECT name, slug, tile_image FROM categories ORDER BY name");
            $dbCats = $catsStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {   $dbCats = []; }

        if ($dbCats) {
            // Known friendly fallbacks by category name (ensures visuals even if DB lacks tile_image and heuristic fails)
            $fallbackMap = [
                'Cleaning & Household Supplies' => 'Compostable dish sponges.jpg',
                'Kitchen & Dining' => 'Beeswax food wraps.jpg',
                'Home Décor & Living' => 'Soy wax candles with natural essential oils.jpg',
                'Bathroom & Personal Care' => 'Natural loofahs and exfoliating mitts.jpg',
                'Lifestyle & Wellness' => 'Mindfulness kit with sustainably sourced incense.jpg',
                'Kids & Pets' => 'Natural pet grooming foam.jpg',
                'Outdoor & Garden' => 'Solar-powered garden lights.jpg',
            ];
            foreach ($dbCats as $c) {
                $imgFile = '';
                // Prefer explicit tile_image if the file actually exists in uploads
                if (!empty($c['tile_image']) && ds_upload_exists($c['tile_image'])) {
                    $imgFile = $c['tile_image'];
                } else {
                    // Try to find a representative image from uploads based on category name
                    $guess = ds_find_image_for_keywords($c['name']);
                    if ($guess && ds_upload_exists($guess)) { $imgFile = $guess; }
                }
                // Final fallback: curated mapping
                if ($imgFile === '' && isset($fallbackMap[$c['name']]) && ds_upload_exists($fallbackMap[$c['name']])) {
                    $imgFile = $fallbackMap[$c['name']];
                }
                // Enforce specific images for requested categories (overrides DB tile_image per client request)
                $enforced = [
                    'Home Décor & Living' => 'Soy wax candles with natural essential oils.jpg',
                    'Kids & Pets' => 'Natural pet grooming foam.jpg',
                    'Lifestyle & Wellness' => 'Mindfulness kit with sustainably sourced incense.jpg',
                ];
                if (isset($enforced[$c['name']]) && ds_upload_exists($enforced[$c['name']])) {
                    $imgFile = $enforced[$c['name']];
                }
                $bgUrl = $imgFile ? ds_upload_url($imgFile) : (APP_URL . '/public/assets/images/hero.jpg');
                ?>
                <div class="category-tile"
                     style="background-image:url('<?= h($bgUrl) ?>'); background-repeat:no-repeat;"
                     onclick="window.location.href='category.php?slug=<?= urlencode($c['slug']) ?>'">
                    <span><?= h($c['name']) ?></span>
                </div>
                <?php
            }
        } else {
            // Fallback hardcoded list (first-run)
            $categories = [
                ["Cleaning & Household Supplies", "Compostable dish sponges.jpg"],
                ["Kitchen & Dining", "Beeswax food wraps.jpg"],
                ["Home Décor & Living", "Soy wax candles with natural essential oils.jpg"],
                ["Bathroom & Personal Care", "Natural loofahs and exfoliating mitts.jpg"],
                ["Lifestyle & Wellness", "Mindfulness kit with sustainably sourced incense.jpg"],
                ["Kids & Pets", "Natural pet grooming foam.jpg"],
                ["Outdoor & Garden", "Solar-powered garden lights.jpg"]
            ];
            foreach ($categories as $cat): ?>
                <?php $bgUrl = ds_upload_exists($cat[1]) ? ds_upload_url($cat[1]) : (APP_URL . '/public/assets/images/hero.jpg'); ?>
                <div class="category-tile"
                     style="background-image:url('<?= h($bgUrl) ?>'); background-repeat:no-repeat;"
                     onclick="window.location.href='category.php?name=<?= urlencode($cat[0]) ?>'">
                    <span><?= h($cat[0]) ?></span>
                </div>
            <?php endforeach; }
        ?>
    </div>
</section>

<?php include(__DIR__ . '/partials/footer.php'); ?>
