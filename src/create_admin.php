<?php
// src/create_admin.php
// Utility script to set or create the admin user using current schema (users.name, users.email, users.password_hash, users.role_key)
// Usage examples:
//   http://localhost/DragonStone/src/create_admin.php
//   http://localhost/DragonStone/src/create_admin.php?pw=YourStrongPassword

require_once __DIR__ . '/config.php';

ds_session_start();

// Default admin details
$adminEmail = 'admin@dragonstone.local'; // matches seed file
$adminName  = 'Administrator';
$adminRole  = 'admin';

// Accept password via query param for convenience during local setup
$plainPw = trim($_GET['pw'] ?? 'admin123');
if (strlen($plainPw) < 8) {
    // Minimum sanity for local setup
    $plainPw = 'admin12345';
}

$hash = password_hash($plainPw, PASSWORD_DEFAULT);

try {
    // Check if the admin (by email) exists already
    $check = $pdo->prepare("SELECT user_id, name, role_key FROM users WHERE email = ? LIMIT 1");
    $check->execute([$adminEmail]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update the password hash and ensure role is admin
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, role_key = ? WHERE user_id = ?");
        $upd->execute([$hash, $adminRole, $existing['user_id']]);
        echo "Admin password updated for {$adminEmail}. You can now log in.\n";
    } else {
        // Create a new admin row
        $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash, role_key) VALUES (?, ?, ?, ?)");
        $ins->execute([$adminName, $adminEmail, $hash, $adminRole]);
        echo "Admin account created for {$adminEmail}.\n";
    }

    echo "Username (email): {$adminEmail}\n";
    echo "Password: {$plainPw}\n";
    echo "Next: visit " . APP_URL . "/login.php to sign in.\n";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Error creating admin: " . $e->getMessage();
}
?>
