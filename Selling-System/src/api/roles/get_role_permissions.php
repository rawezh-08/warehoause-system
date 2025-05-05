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
// Temporarily allowing access for development/testing
// In production, uncomment the following block
/*
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "لە ژوورەوە نیت. تکایە داخلبوونەوە دووبارە بکەوە."]);
    exit;
}
*/

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create permission model
$permissionModel = new Permission($db);

// Check if role ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $role_id = (int)$_GET['id'];
    
    try {
        // Get role name
        $role = $permissionModel->getRoleById($role_id);
        
        if ($role) {
            // Get detailed role permissions with full information
            $permissions = $permissionModel->getRolePermissions($role_id);
            
            // Return success response with permissions data
            http_response_code(200); // OK
            echo json_encode([
                "status" => "success",
                "data" => [
                    "role_name" => $role['name'],
                    "permissions" => $permissions
                ]
            ]);
        } else {
            // Return error if role not found
            http_response_code(404); // Not Found
            echo json_encode([
                "status" => "error", 
                "message" => "ڕۆڵ نەدۆزرایەوە"
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
} else {
    // Return error response if role ID is not provided
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "error", 
        "message" => "داتای ناتەواو. تکایە ناسنامەی ڕۆڵ داخل بکە."
    ]);
}
?> 