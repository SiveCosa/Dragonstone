<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    die('Invalid CSRF');
}

$title = trim($_POST['title'] ?? '');
$body  = trim($_POST['body'] ?? '');
$user_id = intval($_SESSION['user_id']);

if ($title === '' || $body === '') {
    http_response_code(422);
    die('Missing fields');
}

// Optional: basic image handling placeholder (no posts upload dir defined in repo)
$image = null;

$stmt = $pdo->prepare("INSERT INTO community_posts (user_id, title, body) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $title, $body]);

// Award points
$pts = (int)ECOPOINTS_COMMUNITY_POST;
$pdo->prepare("UPDATE users SET eco_points = eco_points + ? WHERE user_id = ?")->execute([$pts, $user_id]);

// Flash success on the same page after redirect
if (function_exists('flash_success')) { flash_success('Your post has been published.'); }
header('Location: ' . APP_URL . '/public/community.php');
exit;
