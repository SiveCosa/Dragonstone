<?php
// src/api/api_footprint.php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$productId = intval($_POST['product_id'] ?? 0);
$quantity  = intval($_POST['quantity'] ?? 1);

$stmt = $pdo->prepare("SELECT name, carbon_score FROM products WHERE product_id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['error' => 'Product not found']);
    exit;
}

$totalSavings = $product['carbon_score'] * $quantity;
$message = sprintf(
    "By choosing %s, you save approximately %.2f kg of CO₂ emissions compared to non-sustainable alternatives!",
    $product['name'],
    $totalSavings
);

// Optionally reward EcoPoints (2 points per kg CO₂ saved)
if (isset($_SESSION['user_id'])) {
    $ecoPoints = intval($totalSavings * 2);
    $upd = $pdo->prepare("UPDATE users SET eco_points = eco_points + ? WHERE user_id = ?");
    $upd->execute([$ecoPoints, (int)$_SESSION['user_id']]);
}

echo json_encode([
    'product'        => $product['name'],
    'quantity'       => $quantity,
    'carbon_per_unit'=> $product['carbon_score'],
    'total_savings'  => $totalSavings,
    'message'        => $message
]);
