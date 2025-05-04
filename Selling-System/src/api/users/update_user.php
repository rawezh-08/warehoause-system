<?php
// Include database connection
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../includes/auth.php';

// Check if user has permission
if (!hasPermission('manage_accounts')) {
    exit(json_encode(['status' => 'error', 'message' => 'دەسەڵاتت نییە بۆ دەستکاریکردنی بەکارهێنەر']));
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit(json_encode(['status' => 'error', 'message' => 'Only POST method is allowed']));
}

// Validate required fields
if (!isset($_POST['user_id']) || empty($_POST['user_id']) ||
    !isset($_POST['username']) || empty($_POST['username']) ||
    !isset($_POST['role_id']) || empty($_POST['role_id'])) {
    exit(json_encode(['status' => 'error', 'message' => 'ناو و ڕۆڵی بەکارهێنەر پێویستن']));
}

// Get inputs and sanitize
$user_id = intval($_POST['user_id']);
$username = trim($_POST['username']);
$password = isset($_POST['password']) ? $_POST['password'] : null;
$employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
$role_id = intval($_POST['role_id']);
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

// Validate username
if (strlen($username) < 3) {
    exit(json_encode(['status' => 'error', 'message' => 'ناوی بەکارهێنەر دەبێت لانیکەم 3 پیت بێت']));
}

// Validate password if provided
if (!empty($password) && strlen($password) < 8) {
    exit(json_encode(['status' => 'error', 'message' => 'وشەی نهێنی دەبێت لانیکەم 8 پیت بێت']));
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
    
    // Check if username already exists and is not the current user
    if ($userModel->usernameExistsExcept($username, $user_id)) {
        exit(json_encode(['status' => 'error', 'message' => 'ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە']));
    }
    
    // Prepare user data for update
    $userData = [
        'id' => $user_id,
        'username' => $username,
        'employee_id' => $employee_id,
        'role_id' => $role_id,
        'is_active' => $is_active
    ];
    
    // Add password to update data if provided
    if (!empty($password)) {
        $userData['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Update user
    $result = $userModel->updateUser($userData);
    
    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'زانیاری بەکارهێنەر بە سەرکەوتوویی نوێکرایەوە']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەی بەکارهێنەر']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'هەڵەی ناوخۆیی: ' . $e->getMessage()]);
} 