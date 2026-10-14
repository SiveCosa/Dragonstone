<?php
// helpers.php

// Basic HTML escape
function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function sanitize_input($data) {
    return h(trim((string)$data));
}

function ds_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Role guard for admin area
function require_roles(array $allowedRoles, $fallbackUrl = null) {
    ds_session_start();
    $role = $_SESSION['role'] ?? null;
    if (!isset($_SESSION['admin_logged_in']) || !$role || !in_array($role, $allowedRoles, true)) {
        // Default fallback to admin login or products page if logged in but unauthorized
        if ($fallbackUrl === null) {
            $fallbackUrl = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'])
                ? (APP_URL . '/admin/manage_products.php')
                : (APP_URL . '/admin/index.php');
        }
        header('Location: ' . $fallbackUrl);
        exit();
    }
}

// ---------- Flash messaging (session-based) ----------
// Store one or more messages to be shown on the next page load
function set_flash($type, $message) {
    ds_session_start();
    if (!isset($_SESSION['flashes']) || !is_array($_SESSION['flashes'])) {
        $_SESSION['flashes'] = [];
    }
    $type = strtolower((string)$type);
    if (!in_array($type, ['success','info','warning','error'], true)) {
        $type = 'info';
    }
    $_SESSION['flashes'][] = ['type' => $type, 'message' => (string)$message];
}
function flash_success($message) { set_flash('success', $message); }
function flash_info($message)    { set_flash('info', $message); }
function flash_warning($message) { set_flash('warning', $message); }
function flash_error($message)   { set_flash('error', $message); }

// Retrieve and clear flashes
function get_flashes() {
    ds_session_start();
    $fl = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return is_array($fl) ? $fl : [];
}

// CSRF helpers bridging to src/csrf.php
function ds_csrf_token() {
    if (!function_exists('generate_csrf_token')) return '';
    return generate_csrf_token();
}

function ds_verify_csrf($token) {
    if (function_exists('verify_csrf_token')) return verify_csrf_token($token);
    if (function_exists('validate_csrf_token')) return validate_csrf_token($token);
    return false;
}

// Find a product image filename in UPLOAD_PATH that best matches given keywords (e.g., product or category name)
function ds_find_image_for_keywords($keywords) {
    $dir = defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../public/uploads/products/';
    if (!is_dir($dir)) return null;
    $kw = strtolower((string)$keywords);
    $best = null; $bestScore = -1;
    // list files once
    $files = @scandir($dir);
    if (!$files) return null;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        if (!preg_match('/\.(jpe?g|png|gif|webp)$/i', $f)) continue;
        $name = strtolower(str_replace(['_', '-', '(', ')'], ' ', pathinfo($f, PATHINFO_FILENAME)));
        // simple scoring: count overlapping words
        $score = 0;
        foreach (preg_split('/\s+/', $kw) as $w) {
            if ($w !== '' && strpos($name, $w) !== false) { $score++; }
        }
        if ($score > $bestScore) { $bestScore = $score; $best = $f; }
    }
    return $best;
}

// Safely build a public URL for a file in uploads/products (handles spaces & special chars)
function ds_upload_url($filename) {
    if (!$filename) return null;
    $base = basename((string)$filename);
    $encoded = rawurlencode($base);
    return rtrim(defined('UPLOAD_URL') ? UPLOAD_URL : '', '/') . '/' . $encoded;
}

// Check if a filename exists in the uploads directory
function ds_upload_exists($filename) {
    if (!$filename) return false;
    $base = basename((string)$filename);
    $path = (defined('UPLOAD_PATH') ? UPLOAD_PATH : (__DIR__ . '/../public/uploads/products/')) . $base;
    return is_file($path);
}

function sendEmail($to, $subject, $message)
{
    $headers = "From: no-reply@dragonstone.co.za\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    @mail($to, $subject, $message, $headers);
}

?>
