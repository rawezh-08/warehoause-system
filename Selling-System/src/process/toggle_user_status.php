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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id']) || !isset($_POST['status'])) {
    $_SESSION['error_message'] = "داواکاری ڕێگەپێدراو نییە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Get form data
$user_id = (int)$_POST['user_id'];
$status = (int)$_POST['status'] ? 1 : 0; // Convert to 1 or 0

// Check if user exists
$check_query = "SELECT id, username FROM user_accounts WHERE id = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['error_message'] = "بەکارهێنەری داواکراو بوونی نییە";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

$user = $check_result->fetch_assoc();

// Cannot deactivate yourself
if ($user_id == $_SESSION['admin_id'] && $status == 0) {
    $_SESSION['error_message'] = "ناتوانیت هەژماری خۆت ناچالاک بکەیت";
    header("Location: ../Views/admin/user_management.php");
    exit();
}

// Update user status
$update_query = "UPDATE user_accounts SET is_active = ? WHERE id = ?";
$update_stmt = $db->prepare($update_query);
$update_stmt->bind_param("ii", $status, $user_id);

if ($update_stmt->execute()) {
    $message = $status == 1 ? "چالاککرایەوە" : "ناچالاککرا";
    $_SESSION['success_message'] = "بەکارهێنەر " . $user['username'] . " بە سەرکەوتوویی " . $message;
    header("Location: ../Views/admin/user_management.php");
    exit();
} else {
    $_SESSION['error_message'] = "هەڵەیەک ڕوویدا لە کاتی گۆڕینی دۆخی بەکارهێنەر: " . $db->error;
    header("Location: ../Views/admin/user_management.php");
    exit();
} 