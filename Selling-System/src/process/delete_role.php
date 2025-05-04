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
    $_SESSION['error_message'] = "مۆڵەتی پێویستت نییە بۆ ئەم کردارە";
    header("Location: ../Views/admin/dashboard.php");
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['role_id'])) {
    $_SESSION['error_message'] = "داواکاری ڕێگەپێدراو نییە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Get role ID to delete
$role_id = (int)$_POST['role_id'];

// Check if role exists
$check_query = "SELECT id FROM user_roles WHERE id = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("i", $role_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['error_message'] = "ڕۆڵی داواکراو بوونی نییە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Cannot delete default roles (IDs 1-5)
if ($role_id <= 5) {
    $_SESSION['error_message'] = "ناتوانیت ڕۆڵە سەرەکییەکان بسڕیتەوە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Check if role is assigned to any users
$users_check_query = "SELECT id FROM user_accounts WHERE role_id = ?";
$users_check_stmt = $db->prepare($users_check_query);
$users_check_stmt->bind_param("i", $role_id);
$users_check_stmt->execute();
$users_check_result = $users_check_stmt->get_result();

if ($users_check_result->num_rows > 0) {
    $_SESSION['error_message'] = "ناتوانیت ئەم ڕۆڵە بسڕیتەوە چونکە بەکارهێنەر پێیەوە بەستراوە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Delete the role (role_permissions will be deleted automatically due to foreign key constraints)
$delete_query = "DELETE FROM user_roles WHERE id = ?";
$delete_stmt = $db->prepare($delete_query);
$delete_stmt->bind_param("i", $role_id);

if ($delete_stmt->execute()) {
    $_SESSION['success_message'] = "ڕۆڵ بە سەرکەوتوویی سڕایەوە";
    header("Location: ../Views/admin/role_management.php");
    exit();
} else {
    $_SESSION['error_message'] = "هەڵەیەک ڕوویدا لە کاتی سڕینەوەی ڕۆڵ: " . $db->error;
    header("Location: ../Views/admin/role_management.php");
    exit();
} 