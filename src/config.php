<?php
// =========================================================
// Dragonstone Global Configuration
// =========================================================

// ---- Environment Setup ----
define('APP_NAME', 'Dragonstone');
// IMPORTANT: Match case of your local folder name (DragonStone)
define('APP_URL', 'http://localhost/DragonStone'); // for local dev
// define('APP_URL', 'https://your-000webhost-url'); // uncomment for deployment


// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';


// ---- Currency / Locale ----
define('CURRENCY', 'ZAR');
define('LANGUAGE', 'en');

// ---- PayPal Integration ----
// (You’ll add your real credentials later)
define('PAYPAL_CLIENT_ID', 'your-paypal-client-id-here');
define('PAYPAL_SECRET', 'your-paypal-secret-here');
define('PAYPAL_ENV', 'sandbox'); // change to 'live' for production

// ---- File Paths ----
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/products/');
define('UPLOAD_URL', APP_URL . '/public/uploads/products/');

// ---- Security ----
define('CSRF_TOKEN_KEY', 'dragonstone_secure_token');

// ---- Eco Points Logic ----
define('ECOPOINTS_PER_RAND', 0.5); // 1 point per R2 spent
define('ECOPOINTS_REVIEW', 10); // points per product review
define('ECOPOINTS_COMMUNITY_POST', 20);
define('ECOPOINTS_COMMUNITY_COMMENT', 5);

// ---- Carbon Footprint Conversion (example logic) ----
define('CARBON_OFFSET_PER_PRODUCT', 0.25); // kg CO₂ saved per eco product
?>
