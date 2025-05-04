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


// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "داواکاری ڕێگەپێدراو نییە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Get form data
$user_id = (int)$_POST['user_id'];
$username = trim($_POST['username']);
$password = $_POST['password']; // May be empty if not changing
$employee_id = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : NULL;
$role_id = (int)$_POST['role_id'];

// Validate data
if (empty($username) || empty($user_id) || empty($role_id)) {
    $_SESSION['error_message'] = "تکایە هەموو خانە پێویستەکان پڕبکەرەوە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Check if username already exists for other users
$check_query = "SELECT id FROM user_accounts WHERE username = ? AND id != ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("si", $username, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error_message'] = "ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Check if role exists
$role_check_query = "SELECT id FROM user_roles WHERE id = ?";
$role_check_stmt = $db->prepare($role_check_query);
$role_check_stmt->bind_param("i", $role_id);
$role_check_stmt->execute();
$role_check_result = $role_check_stmt->get_result();

if ($role_check_result->num_rows === 0) {
    $_SESSION['error_message'] = "ڕۆڵی هەڵبژێردراو بوونی نییە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Check if employee exists (if provided)
if ($employee_id !== NULL) {
    $employee_check_query = "SELECT id FROM employees WHERE id = ?";
    $employee_check_stmt = $db->prepare($employee_check_query);
    $employee_check_stmt->bind_param("i", $employee_id);
    $employee_check_stmt->execute();
    $employee_check_result = $employee_check_stmt->get_result();

    if ($employee_check_result->num_rows === 0) {
        $_SESSION['error_message'] = "کارمەندی هەڵبژێردراو بوونی نییە";
        header("Location: ../Views/admin/user_management.php");
        exit();
    }
}

// Update user information
if (!empty($password)) {
    // If password is provided, update it as well
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $update_query = "UPDATE user_accounts SET 
                    username = ?, 
                    password_hash = ?, 
                    employee_id = ?, 
                    role_id = ? 
                    WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bind_param("ssiii", $username, $password_hash, $employee_id, $role_id, $user_id);
} else {
    // If password is not provided, keep the existing one
    $update_query = "UPDATE user_accounts SET 
                    username = ?, 
                    employee_id = ?, 
                    role_id = ? 
                    WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bind_param("siii", $username, $employee_id, $role_id, $user_id);
}

if ($update_stmt->execute()) {
    $_SESSION['success_message'] = "زانیاری بەکارهێنەر بە سەرکەوتوویی نوێ کرایەوە";
    header("Location: ../Views/admin/user_management.php");
    exit();
} else {
    $_SESSION['error_message'] = "هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەی زانیاری بەکارهێنەر: " . $db->error;
    header("Location: ../Views/admin/user_management.php");
    exit();
} 