<?php
session_start();

// Disable browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// --- Require login ---
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    header("Location: index.php?action=login");
    exit();
}

// --- Role-based access control ---
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'user';

// All admin-only pages
$admin_only_pages = [
    'Dashboard.php',
    'practiceaddproduct.php',
    'Orders.php',
    'admin_inquiries.php',
    'add_stock.php',
    'add_product.php',
    'admin_sidebar.php',
    'Sales.php',
    'user_accounts.php',
];

// All normal user-only pages (optional)
$user_only_pages = [
    'orders.php',
    'profile.php',
    'checkout.php'
];

// Block users from admin pages
if (in_array($current_page, $admin_only_pages, true) && $user_role !== 'admin') {
    header("Location: index.php");
    exit();
}

// Optional: block guests from user-only pages
if (in_array($current_page, $user_only_pages, true) && !isset($_SESSION['user_id'])) {
    header("Location: index.php?action=login");
    exit();
}

// Regenerate session ID periodically (every 5 mins)
if (!isset($_SESSION['last_regen'])) {
    $_SESSION['last_regen'] = time();
} elseif (time() - $_SESSION['last_regen'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regen'] = time();
}
?>
