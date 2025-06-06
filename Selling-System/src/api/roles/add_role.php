<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if data is not empty
if (!empty($data->name)) {
    try {
        // Prepare role data
        $roleData = [
            'name' => $data->name,
            'description' => $data->description ?? ""
        ];

        // Add role
        $role_id = $permissionModel->addRole($roleData);

        if ($role_id) {
            // Assign permissions if provided
            if (isset($data->permissions) && is_array($data->permissions) && !empty($data->permissions)) {
                $permissionModel->assignPermissions($role_id, $data->permissions);
            }

            // Return success response
            http_response_code(201); // Created
            echo json_encode([
                "status" => "success",
                "message" => "ڕۆڵ بەسەرکەوتوویی زیادکرا",
                "id" => $role_id
            ]);
        } else {
            // Return error response
            http_response_code(500); // Internal Server Error
            echo json_encode([
                "status" => "error", 
                "message" => "هەڵەیەک ڕوویدا لە کاتی زیادکردنی ڕۆڵ"
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
    // Return error response if data is incomplete
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "error", 
        "message" => "داتای ناتەواو. تکایە ناوی ڕۆڵ داخل بکە."
    ]);
}
?> 