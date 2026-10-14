<?php
// public/webhook_paypal.php
require_once __DIR__ . '/../src/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "/login.php");
    exit;
}

$userId          = (int)$_SESSION['user_id'];
$total           = $_POST['total'] ?? 0;
$rewardId        = $_POST['eco_reward_used'] ?? null;
$discountApplied = $_POST['discount'] ?? 0;

// Insert order
$stmt = $pdo->prepare("INSERT INTO orders (user_id, total, eco_reward_used, discount_applied) VALUES (?, ?, ?, ?)");
$stmt->execute([$userId, $total, $rewardId, $discountApplied]);

// Email notification
$userStmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$message = "
<h3>Thank you for your order!</h3>
<p>Your payment of R{$total} has been successfully received.</p>
<p>We'll notify you once your order has been shipped.</p>
<p>🌿 The DragonStone Team</p>
";
sendEmail($user['email'], "Order Confirmation", $message);

header("Location: order_success.php");
exit;
