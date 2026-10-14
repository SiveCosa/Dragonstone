<?php
require_once __DIR__ . '/src/config.php';
// logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally destroy the session
session_destroy();

// Decide redirect target: if admin page triggered logout (referrer contains /admin/) go to admin login, otherwise user login
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($ref, '/admin/') !== false) {
    header('Location: ' . APP_URL . '/admin/index.php'); // admin login
    exit();
}

// default redirect to user login page
header('Location: ' . APP_URL . '/login.php');
exit();