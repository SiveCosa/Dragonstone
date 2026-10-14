<?php
// src/actions/cart_add.php
// Handles Add to Cart from non-AJAX forms and shows a message on the same page via flash
require_once __DIR__ . '/../config.php';

ds_session_start();

// Helper: redirect back to product page
function back_to_product($pid) {
    $pid = intval($pid);
    $fallback = APP_URL . '/public/product.php?id=' . $pid;
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    // Prefer product page if referer is missing or different context
    if (strpos($ref, '/public/product.php') !== false) {
        header('Location: ' . $ref);
    } else {
        header('Location: ' . $fallback);
    }
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        flash_warning('Please log in to add items to your cart.');
        $pid = intval($_POST['product_id'] ?? 0);
        back_to_product($pid);
    }

    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        flash_error('Invalid security token. Please try again.');
        $pid = intval($_POST['product_id'] ?? 0);
        back_to_product($pid);
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    if ($product_id <= 0) {
        flash_error('Invalid product.');
        back_to_product($product_id);
    }

    // Ensure product exists
    $pstmt = $pdo->prepare('SELECT product_id, stock, name FROM products WHERE product_id = ?');
    $pstmt->execute([$product_id]);
    $prod = $pstmt->fetch(PDO::FETCH_ASSOC);
    if (!$prod) {
        flash_error('Product not found.');
        back_to_product($product_id);
    }

    $user_id = intval($_SESSION['user_id']);

    // Get or create cart id
    $stmt = $pdo->prepare('SELECT cart_id FROM carts WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $cart_id = intval($row['cart_id']);
    } else {
        $ins = $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)');
        $ins->execute([$user_id]);
        $cart_id = intval($pdo->lastInsertId());
    }

    // Upsert cart item
    $it = $pdo->prepare('SELECT id FROM cart_items WHERE cart_id = ? AND product_id = ?');
    $it->execute([$cart_id, $product_id]);
    $existing = $it->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $u = $pdo->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE id = ?');
        $u->execute([$quantity, intval($existing['id'])]);
    } else {
        $i = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)');
        $i->execute([$cart_id, $product_id, $quantity]);
    }

    flash_success('Added to cart: ' . h($prod['name']) . ' × ' . $quantity);
    back_to_product($product_id);

} catch (Throwable $e) {
    // Do not expose sensitive details
    $pid = intval($_POST['product_id'] ?? 0);
    flash_error('Could not add to cart at this time.');
    back_to_product($pid);
}
