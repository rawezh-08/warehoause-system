<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
require_once '../../config/database.php';
require_once '../../models/Permission.php';
require_once '../../includes/auth.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the permission code from GET request
$permission_code = isset($_GET['permission_code']) ? $_GET['permission_code'] : null;

if (empty($permission_code)) {
    // Return error if permission code is not provided
    http_response_code(400); // Bad request
    echo json_encode([
        "status" => "error",
        "message" => "تکایە کۆدی دەسەڵات دیاری بکە",
        "has_permission" => false
    ]);
    exit;
}

// Check if admin is logged in (admins have all permissions)
if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "بەڕێوەبەر دەسەڵاتی هەیە",
        "has_permission" => true
    ]);
    exit;
}

// Check if regular user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "بەکارهێنەر لە ژوورەوە نیە",
        "has_permission" => false
    ]);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create permission model
$permissionModel = new Permission($db);

// Check if user has the required permission
$hasPermission = $permissionModel->userHasPermission($user_id, $permission_code);

// Return result
http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => $hasPermission ? "بەکارهێنەر دەسەڵاتی هەیە" : "بەکارهێنەر دەسەڵاتی نیە",
    "has_permission" => $hasPermission
]);
exit; 