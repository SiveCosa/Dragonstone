<?php
// src/api/api_cart.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../csrf.php';

session_start();

function json_error($msg, $code = 400) { http_response_code($code); echo json_encode(['error'=>$msg]); exit; }

// Utility: get or create cart id for current user
function get_or_create_cart_id($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return intval($row['cart_id']);
    $ins = $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $ins->execute([$user_id]);
    return intval($pdo->lastInsertId());
}

// Build cart response with product details
function get_cart_contents($pdo, $cart_id) {
    $stmt = $pdo->prepare("
        SELECT ci.id as cart_item_id, ci.product_id, ci.quantity,
               p.name, p.price, p.currency, p.image_filename, p.points_on_purchase
        FROM cart_items ci
        JOIN products p ON p.product_id = ci.product_id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cart_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = 0.0;
    foreach ($items as &$it) {
        $subtotal = floatval($it['price']) * intval($it['quantity']);
        $it['subtotal'] = number_format($subtotal, 2, '.', '');
        $total += $subtotal;
    }
    return ['items' => $items, 'total' => number_format($total,2,'.','')];
}

// Simple auth guard for mutation
$user_id = $_SESSION['user_id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // optional param: ?user=me or ?cart_id=#
        if (!$user_id) {
            // anonymous read: return empty or cart by cart_id
            if (!empty($_GET['cart_id'])) {
                $cart_id = intval($_GET['cart_id']);
                $res = get_cart_contents($pdo, $cart_id);
                echo json_encode($res); exit;
            }
            echo json_encode(['items'=>[], 'total'=>'0.00']); exit;
        }
        $cart_id = get_or_create_cart_id($pdo, $user_id);
        echo json_encode(get_cart_contents($pdo, $cart_id));
        exit;
    }

    // For POST/PUT/DELETE require CSRF
    $payload = $_POST;
    // If JSON payload (fetch), decode
    if ($method !== 'POST' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?? [];
    }

    $csrf = $payload['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validate_csrf_token($csrf)) json_error('Invalid CSRF token', 403);

    if (!$user_id) json_error('Authentication required', 401);

    $action = $payload['action'] ?? ($payload['op'] ?? 'get');

    if ($action === 'add') {
        $product_id = intval($payload['product_id'] ?? 0);
        $quantity = max(1, intval($payload['quantity'] ?? 1));
        if ($product_id <= 0) json_error('product_id required');
        // ensure product exists and has stock (basic)
        $pstmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = ?");
        $pstmt->execute([$product_id]);
        $prod = $pstmt->fetch(PDO::FETCH_ASSOC);
        if (!$prod) json_error('product not found', 404);
        // insert/upsert
        $cart_id = get_or_create_cart_id($pdo, $user_id);
        $it = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $it->execute([$cart_id, $product_id]);
        $row = $it->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $u = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
            $u->execute([$quantity, $row['id']]);
        } else {
            $i = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $i->execute([$cart_id, $product_id, $quantity]);
        }
        echo json_encode(['status'=>'ok', 'cart' => get_cart_contents($pdo, $cart_id)]);
        exit;
    }

    if ($action === 'update') {
        $cart_item_id = intval($payload['cart_item_id'] ?? 0);
        $quantity = max(0, intval($payload['quantity'] ?? 1));
        if ($cart_item_id <= 0) json_error('cart_item_id required');
        if ($quantity <= 0) {
            // remove
            $d = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = (SELECT cart_id FROM carts WHERE user_id = ?)");
            $d->execute([$cart_item_id, $user_id]);
        } else {
            $u = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = (SELECT cart_id FROM carts WHERE user_id = ?)");
            $u->execute([$quantity, $cart_item_id, $user_id]);
        }
        $cart_id = get_or_create_cart_id($pdo, $user_id);
        echo json_encode(['status'=>'ok', 'cart' => get_cart_contents($pdo, $cart_id)]);
        exit;
    }

    if ($action === 'remove') {
        $cart_item_id = intval($payload['cart_item_id'] ?? 0);
        if ($cart_item_id <= 0) json_error('cart_item_id required');
        $d = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = (SELECT cart_id FROM carts WHERE user_id = ?)");
        $d->execute([$cart_item_id, $user_id]);
        $cart_id = get_or_create_cart_id($pdo, $user_id);
        echo json_encode(['status'=>'ok', 'cart' => get_cart_contents($pdo, $cart_id)]);
        exit;
    }

    if ($action === 'clear') {
        $d = $pdo->prepare("DELETE ci FROM cart_items ci JOIN carts c ON ci.cart_id = c.cart_id WHERE c.user_id = ?");
        $d->execute([$user_id]);
        echo json_encode(['status'=>'ok', 'message'=>'cart cleared', 'cart' => ['items'=>[], 'total'=>'0.00'] ]);
        exit;
    }

    json_error('Unknown action: ' . $action);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Server error', 'message'=>$e->getMessage()]);
    exit;
}
