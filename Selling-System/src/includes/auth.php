<?php
// Set session configuration
ini_set('session.gc_maxlifetime', 28800); // 8 hours
ini_set('session.cookie_lifetime', 28800); // 8 hours
session_start();

// Check if the session has an admin_id or user_id
if ((!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) && 
    (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']))) {
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

// Function to check if user has permission
function hasPermission($permission_code) {
    // Admin has all permissions
    if (isset($_SESSION['admin_id'])) {
        return true;
    }
    
    // For regular users, check their role permissions
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../models/Permission.php';
        
        $database = new Database();
        $db = $database->getConnection();
        $permissionModel = new Permission($db);
        
        return $permissionModel->userHasPermission($_SESSION['user_id'], $permission_code);
    }
    
    return false;
}

// Function to enforce permission check
function requirePermission($permission_code) {
    if (!hasPermission($permission_code)) {
        // Store the current URL in session to redirect back after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to access denied page
        header("Location: /Selling-System/src/views/access_denied.php");
        exit();
    }
}
?> 