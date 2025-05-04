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
 * @return bool True if user has permission, false otherwise
 */
function checkPermission($permission_code, $redirect = true) {
    // Check if user is logged in
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
        redirectToAccessDenied();
    }
    
    return $hasPermission;
}

/**
 * Redirect to access denied page
 */
function redirectToAccessDenied() {
    header('Location: /Selling-System/src/views/access_denied.php');
    exit;
} 