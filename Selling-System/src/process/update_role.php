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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "داواکاری ڕێگەپێدراو نییە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Get form data
$role_id = (int)$_POST['role_id'];
$name = trim($_POST['name']);
$description = trim($_POST['description']);

// Validate data
if (empty($name) || empty($role_id)) {
    $_SESSION['error_message'] = "تکایە هەموو خانە پێویستەکان پڕبکەرەوە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Check if role exists
$role_check_query = "SELECT id FROM user_roles WHERE id = ?";
$role_check_stmt = $db->prepare($role_check_query);
$role_check_stmt->bind_param("i", $role_id);
$role_check_stmt->execute();
$role_check_result = $role_check_stmt->get_result();

if ($role_check_result->num_rows === 0) {
    $_SESSION['error_message'] = "ڕۆڵی داواکراو بوونی نییە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Check if role name already exists for other roles
$check_query = "SELECT id FROM user_roles WHERE name = ? AND id != ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("si", $name, $role_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error_message'] = "ئەم ناوی ڕۆڵە پێشتر بەکارهاتووە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Update role
$update_query = "UPDATE user_roles SET name = ?, description = ? WHERE id = ?";
$update_stmt = $db->prepare($update_query);
$update_stmt->bind_param("ssi", $name, $description, $role_id);

if ($update_stmt->execute()) {
    $_SESSION['success_message'] = "ڕۆڵ بە سەرکەوتوویی نوێ کرایەوە";
    header("Location: ../Views/admin/role_management.php");
    exit();
} else {
    $_SESSION['error_message'] = "هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەی ڕۆڵ: " . $db->error;
    header("Location: ../Views/admin/role_management.php");
    exit();
} 