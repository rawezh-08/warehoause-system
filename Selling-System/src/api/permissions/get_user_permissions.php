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

// Check if user is logged in
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

// If user is admin, return all permissions
if (isset($_SESSION['admin_id'])) {
    // Get all permissions for admin
    $permissions = $permissionModel->getAllPermissions();
    $permissionCodes = array_column($permissions, 'code');
    
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "دەسەڵاتەکانی بەڕێوەبەر",
        "data" => [
            "permissions" => $permissionCodes,
            "is_admin" => true
        ]
    ]);
    exit;
}

// Get user permissions
$user_id = $_SESSION['user_id'];
$permissionCodes = $permissionModel->getUserPermissionCodes($user_id);

// Get user role information
$query = "SELECT ur.id, ur.name as role_name 
          FROM user_accounts ua
          JOIN user_roles ur ON ua.role_id = ur.id
          WHERE ua.id = :user_id";
          
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$role = $stmt->fetch(PDO::FETCH_ASSOC);

// Return result
http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "دەسەڵاتەکانی بەکارهێنەر",
    "data" => [
        "permissions" => $permissionCodes,
        "role" => $role,
        "is_admin" => false
    ]
]);
exit; 