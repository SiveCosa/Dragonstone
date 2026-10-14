<?php
// db.php
$localhost = true; // set to false when deploying to 000Webhost

if ($localhost) {
    // Local XAMPP environment
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $dbname = 'dragonstone';
} else {
    // 000Webhost environment (commented out)
    /*
    $host = 'your_000webhost_host';
    $user = 'your_000webhost_user';
    $password = 'your_000webhost_password';
    $dbname = 'your_000webhost_dbname';
    */
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
