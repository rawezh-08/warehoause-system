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
$username = trim($_POST['username']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$employee_id = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : NULL;
$role_id = (int)$_POST['role_id'];
$created_by = $_SESSION['admin_id'];

// Validate data
if (empty($username) || empty($password) || empty($confirm_password)) {
    $_SESSION['error_message'] = "تکایە هەموو خانە پێویستەکان پڕبکەرەوە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Check if passwords match
if ($password !== $confirm_password) {
    $_SESSION['error_message'] = "وشەی نهێنی و دووبارەکردنەوەکەی یەک ناگرنەوە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Check if username already exists
$check_query = "SELECT id FROM user_accounts WHERE username = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("s", $username);
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

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

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

// Use the stored procedure to add the user
$add_query = "CALL add_user(?, ?, ?, ?, ?)";
$add_stmt = $db->prepare($add_query);
$add_stmt->bind_param("ssiii", $username, $password_hash, $employee_id, $role_id, $created_by);

if ($add_stmt->execute()) {
    $_SESSION['success_message'] = "بەکارهێنەر بە سەرکەوتوویی زیاد کرا";
    header("Location: ../Views/admin/user_management.php");
    exit();
} else {
    $_SESSION['error_message'] = "هەڵەیەک ڕوویدا لە کاتی زیادکردنی بەکارهێنەر: " . $db->error;
    header("Location: ../Views/admin/user_management.php");
    exit();
} 