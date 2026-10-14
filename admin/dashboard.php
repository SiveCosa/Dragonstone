<?php
require_once __DIR__ . '/../src/config.php';
require_roles(['admin']);
include __DIR__ . '/admin_header.php';

// Summary KPIs using PDO and current schema
$totalSales = (float)($pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn() ?: 0);
$totalUsers = (int)($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0);
$totalProducts = (int)($pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0);
$totalEcoPoints = (int)($pdo->query("SELECT COALESCE(SUM(eco_points),0) FROM users")->fetchColumn() ?: 0);
$carbonEstimate = (float)($pdo->query("SELECT COALESCE(SUM(pf.kg_co2e_per_unit),0) FROM product_footprint pf")->fetchColumn() ?: 0);

// Top categories by product count (proxy for sales in current schema)
$topCategories = [];
$categoryCounts = [];
$tcStmt = $pdo->query("SELECT c.name AS category, COUNT(p.product_id) AS cnt
                       FROM categories c
                       LEFT JOIN products p ON p.category_id = c.category_id
                       GROUP BY c.category_id, c.name
                       ORDER BY cnt DESC, c.name ASC
                       LIMIT 5");
foreach ($tcStmt as $row) {
    $topCategories[] = $row['category'];
    $categoryCounts[] = (int)$row['cnt'];
}

// Monthly revenue (last 12 months)
$months = [];
$revenue = [];
$mrStmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%b') AS month, DATE_FORMAT(created_at, '%Y-%m') AS ym, SUM(total_amount) AS total
                        FROM orders
                        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        GROUP BY ym, month
                        ORDER BY ym ASC");
foreach ($mrStmt as $row) {
    $months[] = $row['month'];
    $revenue[] = (float)$row['total'];
}
?>

<main class="container my-5">
    <h2 class="text-success mb-4">📊 Admin Dashboard</h2>

    <!-- KPIs -->
    <div class="row text-center mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm p-3 border-0 bg-light">
                <h5>Total Sales</h5>
                <h3 class="text-success">R<?php echo number_format($totalSales, 2); ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm p-3 border-0 bg-light">
                <h5>Total Users</h5>
                <h3 class="text-success"><?php echo $totalUsers; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm p-3 border-0 bg-light">
                <h5>Total Products</h5>
                <h3 class="text-success"><?php echo $totalProducts; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm p-3 border-0 bg-light">
                <h5>Estimated CO₂ (per unit sum)</h5>
                <h3 class="text-success"><?php echo number_format($carbonEstimate, 2); ?> kg</h3>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card p-4 shadow-sm">
                <h5>Top Categories by Products</h5>
                <canvas id="categoryChart" height="250"></canvas>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4 shadow-sm">
                <h5>Monthly Revenue (ZAR)</h5>
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Eco Activity -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card p-4 shadow-sm">
                <h5>EcoPoints Earned by Users</h5>
                <canvas id="ecoChart" height="100"></canvas>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx1 = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($topCategories); ?>,
            datasets: [{
                label: 'Products',
                data: <?php echo json_encode($categoryCounts); ?>,
                backgroundColor: '#28a745'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const ctx2 = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Revenue (ZAR)',
                data: <?php echo json_encode($revenue); ?>,
                borderColor: '#28a745',
                fill: false
            }]
        },
        options: { responsive: true, tension: 0.4 }
    });

    const ctx3 = document.getElementById('ecoChart').getContext('2d');
    new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: ['EcoPoints Earned', 'Estimated CO₂ (kg)'],
            datasets: [{
                data: [<?php echo (int)$totalEcoPoints; ?>, <?php echo (float)$carbonEstimate; ?>],
                backgroundColor: ['#2ecc71', '#a3e635']
            }]
        },
        options: { responsive: true }
    });
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
