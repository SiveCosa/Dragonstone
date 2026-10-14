<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    die('Invalid CSRF');
}

$post_id = intval($_POST['post_id'] ?? 0);
$body = trim($_POST['body'] ?? '');
$user_id = intval($_SESSION['user_id']);

if ($post_id <= 0 || $body === '') {
    http_response_code(422);
    die('Missing fields');
}

// Insert comment
$stmt = $pdo->prepare("INSERT INTO community_comments (post_id, user_id, body) VALUES (?, ?, ?)");
$stmt->execute([$post_id, $user_id, $body]);

// Reward user
$pts = (int)ECOPOINTS_COMMUNITY_COMMENT;
$pdo->prepare("UPDATE users SET eco_points = eco_points + ? WHERE user_id = ?")->execute([$pts, $user_id]);

// Flash a success message and return to the same page
if (function_exists('flash_success')) { flash_success('Your comment has been posted.'); }
header("Location: " . APP_URL . "/public/post_detail.php?id=" . $post_id);
exit;
