<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    // Return error JSON
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Include database connection
require_once '../config/database.php';



// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Get user ID
$user_id = (int)$_POST['user_id'];

// Check if the admin has permission to manage accounts
$admin_id = $_SESSION['admin_id'];
$has_permission = false;

// First check if this is an admin account (from admin_accounts table)
$admin_check_query = "SELECT id FROM admin_accounts WHERE id = $admin_id";
$admin_check_result = $db->query($admin_check_query);
if ($admin_check_result->num_rows > 0) {
    $has_permission = true; // Admin has all permissions
} else {
    // Check if it's a user account with appropriate permissions
    $permission_query = "CALL check_user_permission($admin_id, 'manage_accounts')";
    $permission_result = $db->query($permission_query);
    if ($permission_result && $permission_result->num_rows > 0) {
        $permission_row = $permission_result->fetch_assoc();
        $has_permission = (bool)$permission_row['has_permission'];
    }
    $db->next_result(); // Clear the result
}

if (!$has_permission) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

// Get user data
$query = "SELECT username, employee_id, role_id, is_active FROM user_accounts WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user = $result->fetch_assoc();

// Return user data
echo json_encode([
    'success' => true,
    'user' => $user
]);
exit(); 