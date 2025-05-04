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
$name = trim($_POST['name']);
$description = trim($_POST['description']);

// Validate data
if (empty($name)) {
    $_SESSION['error_message'] = "تکایە ناوی ڕۆڵەکە بنووسە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Check if role name already exists
$check_query = "SELECT id FROM user_roles WHERE name = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("s", $name);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error_message'] = "ئەم ناوی ڕۆڵە پێشتر بەکارهاتووە";
    header("Location: ../Views/admin/role_management.php");
    exit();
}

// Add new role
$insert_query = "INSERT INTO user_roles (name, description) VALUES (?, ?)";
$insert_stmt = $db->prepare($insert_query);
$insert_stmt->bind_param("ss", $name, $description);

if ($insert_stmt->execute()) {
    $_SESSION['success_message'] = "ڕۆڵ بە سەرکەوتوویی زیاد کرا";
    header("Location: ../Views/admin/role_management.php");
    exit();
} else {
    $_SESSION['error_message'] = "هەڵەیەک ڕوویدا لە کاتی زیادکردنی ڕۆڵ: " . $db->error;
    header("Location: ../Views/admin/role_management.php");
    exit();
} 