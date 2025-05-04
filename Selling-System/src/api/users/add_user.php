<?php
// Include database connection
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../includes/auth.php';

// Check if user has permission
if (!hasPermission('manage_accounts')) {
    exit(json_encode(['status' => 'error', 'message' => 'دەسەڵاتت نییە بۆ زیادکردنی بەکارهێنەر']));
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit(json_encode(['status' => 'error', 'message' => 'Only POST method is allowed']));
}

// Validate required fields
if (!isset($_POST['username']) || empty($_POST['username']) ||
    !isset($_POST['password']) || empty($_POST['password']) ||
    !isset($_POST['role_id']) || empty($_POST['role_id'])) {
    exit(json_encode(['status' => 'error', 'message' => 'ناوی بەکارهێنەر، وشەی نهێنی و ڕۆڵی بەکارهێنەر پێویستن']));
}

// Get inputs and sanitize
$username = trim($_POST['username']);
$password = $_POST['password'];
$employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
$role_id = intval($_POST['role_id']);
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

// Validate username
if (strlen($username) < 3) {
    exit(json_encode(['status' => 'error', 'message' => 'ناوی بەکارهێنەر دەبێت لانیکەم 3 پیت بێت']));
}

// Validate password
if (strlen($password) < 8) {
    exit(json_encode(['status' => 'error', 'message' => 'وشەی نهێنی دەبێت لانیکەم 8 پیت بێت']));
}

// Get current user ID
$created_by = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

if (!$created_by) {
    exit(json_encode(['status' => 'error', 'message' => 'هەڵەی ناوخۆیی: ناسنامەی بەکارهێنەر نەدۆزرایەوە']));
}

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create user model
    $userModel = new User($db);
    
    // Check if username already exists
    if ($userModel->usernameExists($username)) {
        exit(json_encode(['status' => 'error', 'message' => 'ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە']));
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Add user
    $result = $userModel->addUser([
        'username' => $username,
        'password_hash' => $hashed_password,
        'employee_id' => $employee_id,
        'role_id' => $role_id,
        'is_active' => $is_active,
        'created_by' => $created_by
    ]);
    
    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'بەکارهێنەر بە سەرکەوتوویی زیادکرا', 'user_id' => $result]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی بەکارهێنەر']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'هەڵەی ناوخۆیی: ' . $e->getMessage()]);
} 