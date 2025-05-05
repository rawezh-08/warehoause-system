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

// Check if user is authorized to view all permissions
// Only admin or users with manage_roles permission should see this
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        "status" => "error",
        "message" => "تکایە داخیل بە پێش ئەوەی ئەم کردارە ئەنجام بدەیت",
        "data" => []
    ]);
    exit;
}

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create permission model
$permissionModel = new Permission($db);

// Get user ID for permission checking
$user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : $_SESSION['user_id'];

// If not admin, check if user has permission to manage roles
if (!isset($_SESSION['admin_id'])) {
    $hasPermission = $permissionModel->userHasPermission($user_id, 'بەڕێوەبردنی ڕۆڵەکان');
    
    if (!$hasPermission) {
        http_response_code(403); // Forbidden
        echo json_encode([
            "status" => "error",
            "message" => "تۆ دەسەڵاتی بینینی هەموو دەسەڵاتەکانت نیە",
            "data" => []
        ]);
        exit;
    }
}

// Get all permissions
$permissions = $permissionModel->getAllPermissions();

// Get permissions grouped by category
$permissionsByGroup = $permissionModel->getPermissionsByGroup();

// Return result
http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "دەسەڵاتەکان بە سەرکەوتوویی وەرگیران",
    "data" => [
        "permissions" => $permissions,
        "grouped_permissions" => $permissionsByGroup
    ]
]);
exit; 