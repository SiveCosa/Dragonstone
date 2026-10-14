<?php
session_start();
require_once __DIR__ . '/../src/config.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = "Both fields are required.";
    } else {
        // Authenticate against users table; allow admin or staff roles
        $stmt = $pdo->prepare("SELECT user_id, name, email, password_hash, role_key FROM users WHERE email = ? AND role_key IN ('admin','staff') LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['name'];
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['role'] = $user['role_key'];
            // Redirect based on role
            if ($user['role_key'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: manage_products.php");
            }
            exit();
        } else {
            $error = "Invalid credentials or insufficient privileges.";
        }
    }
}
$adminCssV = @filemtime(__DIR__ . '/../public/assets/css/admin.css');
if (!$adminCssV) { $adminCssV = time(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | Dragonstone</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css?v=<?= $adminCssV ?>">
</head>
<body class="admin-login-page">
<div class="login-container">
    <h2>Admin Login</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn btn-success">Login</button>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
