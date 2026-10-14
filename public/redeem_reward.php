<?php
// public/redeem_reward.php
require_once __DIR__ . '/../src/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "/login.php");
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$rewardId = intval($_POST['reward_id'] ?? 0);

// Fetch reward
$stmt = $pdo->prepare("SELECT * FROM rewards WHERE id = ?");
$stmt->execute([$rewardId]);
$reward = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reward) {
    die("Invalid reward");
}

// Fetch user points
$stmt = $pdo->prepare("SELECT eco_points FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user['eco_points'] < $reward['points_required']) {
    die("Insufficient points");
}

// Deduct points and log redemption
$pdo->prepare("UPDATE users SET eco_points = eco_points - ? WHERE user_id = ?")
    ->execute([$reward['points_required'], $userId]);
$pdo->prepare("INSERT INTO redemptions (user_id, reward_id) VALUES (?, ?)")
    ->execute([$userId, $rewardId]);

header("Location: user_dashboard.php?redeemed=success");
exit;
