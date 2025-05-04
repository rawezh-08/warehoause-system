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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    $_SESSION['error_message'] = "داواکاری ڕێگەپێدراو نییە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Get user ID to delete
$user_id = (int)$_POST['user_id'];

// Check if user exists
$check_query = "SELECT id FROM user_accounts WHERE id = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['error_message'] = "بەکارهێنەری داواکراو بوونی نییە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Cannot delete yourself
if ($user_id == $_SESSION['admin_id']) {
    $_SESSION['error_message'] = "ناتوانیت هەژماری خۆت بسڕیتەوە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Delete the user
$delete_query = "DELETE FROM user_accounts WHERE id = ?";
$delete_stmt = $db->prepare($delete_query);
$delete_stmt->bind_param("i", $user_id);

if ($delete_stmt->execute()) {
    $_SESSION['success_message'] = "بەکارهێنەر بە سەرکەوتوویی سڕایەوە";
    header("Location: ../Views/admin/user_management.php");
    exit();
} else {
    $_SESSION['error_message'] = "هەڵەیەک ڕوویدا لە کاتی سڕینەوەی بەکارهێنەر: " . $db->error;
    header("Location: ../Views/admin/user_management.php");
    exit();
} 