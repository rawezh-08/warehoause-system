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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "لە ژوورەوە نیت. تکایە داخلبوونەوە دووبارە بکەوە."]);
    exit;
}

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create permission model
$permissionModel = new Permission($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if role ID is provided
if (!empty($data->id)) {
    // Prevent deleting default roles (IDs 1-5)
    if ($data->id <= 5) {
        http_response_code(403); // Forbidden
        echo json_encode([
            "status" => "error",
            "message" => "ناتوانرێت ڕۆڵە سەرەکییەکان بسڕدرێنەوە."
        ]);
        exit;
    }

    try {
        // Delete role
        $result = $permissionModel->deleteRole($data->id);

        if ($result) {
            // Return success response
            http_response_code(200); // OK
            echo json_encode([
                "status" => "success",
                "message" => "ڕۆڵ بەسەرکەوتوویی سڕایەوە"
            ]);
        } else {
            // Return error response
            http_response_code(500); // Internal Server Error
            echo json_encode([
                "status" => "error", 
                "message" => "هەڵەیەک ڕوویدا لە کاتی سڕینەوەی ڕۆڵ. لەوانەیە ئەم ڕۆڵە بۆ بەکارهێنەرێک بەکارهاتبێت."
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
        "message" => "داتای ناتەواو. تکایە ناسنامەی ڕۆڵ داخل بکە."
    ]);
}
?> 