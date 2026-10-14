<?php
// src/api/api_products.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

// simple router via GET params
try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // GET /src/api/api_products.php            -> list (optional q param)
        // GET /src/api/api_products.php?id=123     -> single product
        // GET /src/api/api_products.php?slug=abc   -> product by slug (if used)

        if (!empty($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $pdo->prepare("
                SELECT p.product_id, p.sku, p.name, p.description, p.price, p.currency, p.stock, p.image_filename, p.points_on_purchase,
                       pf.kg_co2e_per_unit, pf.shipping_kg_co2e_estimate, pf.breakdown_json
                FROM products p
                LEFT JOIN product_footprint pf ON pf.product_id = p.product_id
                WHERE p.product_id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) { http_response_code(404); echo json_encode(['error'=>'Product not found']); exit; }
            // decode JSON breakdown if present
            if (!empty($product['breakdown_json'])) {
                $product['breakdown'] = json_decode($product['breakdown_json'], true);
                unset($product['breakdown_json']);
            }
            echo json_encode(['product' => $product]);
            exit;
        }

        // search / list
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $search = "%" . $q . "%";
            $stmt = $pdo->prepare("
                SELECT p.product_id, p.sku, p.name, p.description, p.price, p.currency, p.stock, p.image_filename, p.points_on_purchase
                FROM products p
                WHERE p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?
                ORDER BY p.name ASC
                LIMIT 200
            ");
            $stmt->execute([$search, $search, $search]);
        } else {
            $stmt = $pdo->query("
                SELECT p.product_id, p.sku, p.name, p.description, p.price, p.currency, p.stock, p.image_filename, p.points_on_purchase
                FROM products p
                ORDER BY p.product_id DESC
                LIMIT 500
            ");
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['products' => $rows]);
        exit;
    }

    // unsupported methods
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Server error', 'message'=>$e->getMessage()]);
    exit;
}
