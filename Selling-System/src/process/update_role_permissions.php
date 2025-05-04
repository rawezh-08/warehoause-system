<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page
    header("Location: ../../../index.php");
    exit();
}

// Include database connection
require_once '../config/database.php';

// Check if the admin has permission to manage roles
$admin_id = $_SESSION['admin_id'];
$has_permission = false;

// First check if this is an admin account (admin_accounts table)
$admin_check_query = "SELECT id FROM admin_accounts WHERE id = $admin_id";
$admin_check_result = $db->query($admin_check_query);
if ($admin_check_result->num_rows > 0) {
    $has_permission = true; // Admin has all permissions
} else {
    // Check if it's a user account with appropriate permissions
    $permission_query = "CALL check_user_permission($admin_id, 'manage_roles')";
    $permission_result = $db->query($permission_query);
    if ($permission_result && $permission_result->num_rows > 0) {
        $permission_row = $permission_result->fetch_assoc();
        $has_permission = (bool)$permission_row['has_permission'];
    }
    $db->next_result(); // Clear the result
}

if (!$has_permission) {
    // Redirect to dashboard if no permission
    header("Location: ../Views/admin/dashboard.php");
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['role_id'])) {
    $_SESSION['error_message'] = "داواکاری ڕێگەپێدراو نییە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Get role ID
$role_id = (int)$_POST['role_id'];

// Check if role exists
$role_check_query = "SELECT id, name FROM user_roles WHERE id = ?";
$role_check_stmt = $db->prepare($role_check_query);
$role_check_stmt->bind_param("i", $role_id);
$role_check_stmt->execute();
$role_check_result = $role_check_stmt->get_result();

if ($role_check_result->num_rows === 0) {
    $_SESSION['error_message'] = "ڕۆڵی داواکراو بوونی نییە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

$role = $role_check_result->fetch_assoc();

// Begin transaction
$db->begin_transaction();

try {
    // Delete all existing permissions for this role
    $delete_query = "DELETE FROM role_permissions WHERE role_id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bind_param("i", $role_id);
    $delete_stmt->execute();
    
    // Add new permissions if any are selected
    if (isset($_POST['permissions']) && is_array($_POST['permissions']) && !empty($_POST['permissions'])) {
        $insert_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        foreach ($_POST['permissions'] as $permission_id) {
            $permission_id = (int)$permission_id;
            $insert_stmt->bind_param("ii", $role_id, $permission_id);
            $insert_stmt->execute();
        }
    }
    
    // Commit transaction
    $db->commit();
    
    $_SESSION['success_message'] = "دەسەڵاتەکانی ڕۆڵی " . $role['name'] . " بە سەرکەوتوویی نوێ کرانەوە";
    header("Location: ../Views/admin/manage_role_permissions.php?role_id=" . $role_id);
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    
    $_SESSION['error_message'] = "هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەی دەسەڵاتەکان: " . $e->getMessage();
    header("Location: ../Views/admin/manage_role_permissions.php?role_id=" . $role_id);
    exit();
} 