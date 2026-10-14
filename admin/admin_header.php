<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}
$role = $_SESSION['role'] ?? 'staff';
$adminCssV = @filemtime(__DIR__ . '/../public/assets/css/admin.css');
if (!$adminCssV) { $adminCssV = time(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Dragonstone</title>

    <!-- Admin Styles (with cache-busting) -->
    <link rel="stylesheet" href="../public/assets/css/admin.css?v=<?= $adminCssV ?>">

    <!-- Bootstrap (for grid/layout support) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Chart.js (for analytics dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Admin JS -->
    <script src="../public/assets/js/admin.js" defer></script>
</head>
<body>
<header class="admin-header text-white p-3 mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="logo">
            <h1 class="h3 mb-0">🌱 Dragonstone Admin</h1>
        </div>
        <nav>
            <ul class="nav">
                <?php if ($role === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="manage_products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="manage_users.php">Users</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="manage_orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="manage_community.php">Community</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link text-white" href="manage_products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="manage_community.php">Community</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link text-white" href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="admin-content container">