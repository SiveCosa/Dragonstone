<?php
require_once __DIR__ . '/../../src/config.php';
$cssV = @filemtime(__DIR__ . '/../assets/css/main.css');
if (!$cssV) { $cssV = time(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= APP_NAME ?> | Sustainable Living</title>
    <meta name="csrf-token" content="<?=h(generate_csrf_token())?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/main.css?v=<?= $cssV ?>">
    <link rel="icon" href="<?= APP_URL ?>/public/assets/images/favicon.ico">
    <script>
        window.APP_URL = "<?=APP_URL?>";
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<header class="site-header">
    <div class="container nav-flex">
        <a href="<?= APP_URL ?>/public/index.php" class="logo">🌱 <?= APP_NAME ?></a>
        <nav class="nav-links">
            <a href="<?= APP_URL ?>/public/category.php">Shop</a>
            <a href="<?= APP_URL ?>/public/community.php">Community</a>
            <a href="<?= APP_URL ?>/public/cart.php">Cart</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= APP_URL ?>/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="main-content">
<?php
    // Render flash messages if any
    if (function_exists('get_flashes')) {
        $flashes = get_flashes();
        if ($flashes) {
            echo '<div class="container" style="margin-top:1rem">';
            foreach ($flashes as $f) {
                $type = $f['type'] ?? 'info';
                $cls = 'alert-info';
                if ($type === 'success') $cls = 'alert-success';
                elseif ($type === 'warning') $cls = 'alert-warning';
                elseif ($type === 'error') $cls = 'alert-danger';
                $msg = h($f['message'] ?? '');
                echo '<div class="alert ' . $cls . '" role="alert">' . $msg . '</div>';
            }
            echo '</div>';
        }
    }
?>
