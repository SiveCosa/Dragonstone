<?php
require_once 'src/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "Invalid CSRF token";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $error = 'All fields are required.';
        } else {
            // Check if email already exists
            $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash, role_key) VALUES (?, ?, ?, 'customer')");
                $ins->execute([$name, $email, $hash]);
                redirect('login.php');
            }
        }
    }
}
?>

<?php include 'public/partials/header.php'; ?>
<section class="auth-container">
    <div class="auth-card">
        <h2>Create an Account</h2>
        <p class="subtitle">Join Dragonstone and live sustainably with style 🌱</p>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()); ?>">
            <label for="name">Full Name</label>
            <input id="name" class="form-control" type="text" name="name" required>

            <label for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" required>

            <label for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required>

            <button type="submit" class="btn btn-success">Register</button>
            <?php if (!empty($error)) echo "<div class='alert alert-warning mt-2'>".h($error)."</div>"; ?>
        </form>
        <p class="mt-2">Already have an account? <a href="login.php">Login</a></p>
    </div>
</section>
<?php include 'public/partials/footer.php'; ?>
