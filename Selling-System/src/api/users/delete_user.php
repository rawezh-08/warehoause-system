<?php
// Include database connection
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../includes/auth.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit(json_encode(['status' => 'error', 'message' => 'Only POST method is allowed']));
}

// Validate required fields
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    exit(json_encode(['status' => 'error', 'message' => 'ناسنامەی بەکارهێنەر پێویستە']));
}

// Get inputs and sanitize
$user_id = intval($_POST['user_id']);

// Get current user ID from session
$current_user_id = $_SESSION['user_id'] ?? 0;

// Prevent users from deleting themselves
if ($user_id == $current_user_id) {
    exit(json_encode(['status' => 'error', 'message' => 'ناتوانیت هەژماری خۆت بسڕیتەوە']));
}

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create user model
    $userModel = new User($db);
    
    // Check if user exists
    $user = $userModel->getUserById($user_id);
    if (!$user) {
        exit(json_encode(['status' => 'error', 'message' => 'بەکارهێنەری داواکراو نەدۆزرایەوە']));
    }
    
    // Delete user
    $result = $userModel->deleteUser($user_id);
    
    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'بەکارهێنەر بە سەرکەوتوویی سڕایەوە']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی بەکارهێنەر']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'هەڵەی ناوخۆیی: ' . $e->getMessage()]);
} 