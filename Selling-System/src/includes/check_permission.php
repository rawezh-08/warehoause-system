<?php
/**
 * Permission check utility
 * فایلی پشکنینی دەسەڵاتەکانی بەکارهێنەر
 */

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Permission.php';

/**
 * Check if the current user has a specific permission
 * 
 * @param string $permission_code The permission code to check
 * @param bool $redirect Whether to redirect if permission denied (default true)
 * @param string $redirect_url Custom URL to redirect to if permission denied
 * @return bool True if user has permission, false otherwise
 */
function checkPermission($permission_code, $redirect = true, $redirect_url = null) {
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if admin is logged in (admins have all permissions)
    if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        return true;
    }
    
    // Check if regular user is logged in
    if (!isset($_SESSION['user_id'])) {
        // User not logged in, redirect to login page
        if ($redirect) {
            header('Location: /index.php');
            exit;
        }
        return false;
    }
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create permission model
    $permissionModel = new Permission($db);
    
    // Check if user has the required permission
    $hasPermission = $permissionModel->userHasPermission($user_id, $permission_code);
    
    // If no permission and redirect is true, redirect to access denied page
    if (!$hasPermission && $redirect) {
        if ($redirect_url) {
            // Redirect to custom URL
            header('Location: ' . $redirect_url);
        } else {
            // Redirect to default access denied page
            redirectToAccessDenied();
        }
        exit;
    }
    
    return $hasPermission;
}

/**
 * Get all permissions the current user has
 * Useful for UI control
 * 
 * @return array Array of permission codes the user has
 */
function getUserPermissions() {
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Admins have all permissions
    if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        // Get all permission codes
        $database = new Database();
        $db = $database->getConnection();
        $permissionModel = new Permission($db);
        
        $allPermissions = $permissionModel->getAllPermissions();
        return array_column($allPermissions, 'code');
    }
    
    // User not logged in
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    // Get user permissions
    $user_id = $_SESSION['user_id'];
    $database = new Database();
    $db = $database->getConnection();
    $permissionModel = new Permission($db);
    
    return $permissionModel->getUserPermissionCodes($user_id);
}

/**
 * Redirect to access denied page
 */
function redirectToAccessDenied() {
    header('Location: /Selling-System/src/views/access_denied.php');
    exit;
} 