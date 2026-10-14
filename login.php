<?php
require_once 'src/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email !== '' && $password !== '') {
        $stmt = $pdo->prepare("SELECT user_id, name, email, password_hash, role_key FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['role'] = $user['role_key'];
            $_SESSION['username'] = $user['name'];

            if (in_array($user['role_key'], ['admin','staff'], true)) {
                redirect('admin/dashboard.php');
            } else {
                redirect('public/index.php');
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Both fields are required.";
    }
}
?>

<?php include 'public/partials/header.php'; ?>
<section class="auth-container">
    <div class="auth-card">
        <h2>Welcome back</h2>
        <p class="subtitle">Log in to continue your eco-journey 🌿</p>
        <form method="POST" action="">
            <label for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" required>
            <label for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required>
            <button type="submit" class="btn btn-success">Login</button>
            <?php if (!empty($error)) echo "<div class='alert alert-warning mt-2'>".h($error)."</div>"; ?>
        </form>
        <p class="mt-2">Don’t have an account? <a href="register.php">Register</a></p>
    </div>
</section>
<?php include 'public/partials/footer.php'; ?>
