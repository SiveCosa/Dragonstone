<?php
require_once __DIR__ . '/../src/helpers.php';
ds_session_start();
$order_id = intval($_GET['order_id'] ?? 0);
include(__DIR__ . '/partials/header.php');
?>
<h1>Thank you</h1>
<?php if ($order_id): ?>
    <p>Your order #<?=intval($order_id)?> is confirmed. Check your email for details.</p>
<?php else: ?>
    <p>Your payment was accepted. Thank you.</p>
<?php endif; ?>
<a class="btn" href="index.php">Continue shopping</a>
<?php include(__DIR__ . '/partials/footer.php'); ?>
