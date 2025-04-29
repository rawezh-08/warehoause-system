<?php
// Set session configuration
ini_set('session.gc_maxlifetime', 28800); // 8 hours
ini_set('session.cookie_lifetime', 28800); // 8 hours

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the session has an admin_id
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // No valid session, redirect to login
    $_SESSION['auth_error'] = "تکایە چوونە ژوورەوە بکەن بۆ بینینی ئەم پەرەیە";
    header("Location: /index.php");
    exit();
}

// Check if the session is expired (optional)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
    // Session expired (after 8 hours)
    session_unset();
    session_destroy();
    header("Location: /index.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Require authentication
 * Redirects to login page if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /login.php');
        exit;
    }
}
?> 