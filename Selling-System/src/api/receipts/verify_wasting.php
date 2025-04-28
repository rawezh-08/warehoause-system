<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/verify_wasting_errors.log');

// Log the start of the script
error_log("Starting verify_wasting.php script");

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Log POST data
error_log("POST data received: " . print_r($_POST, true));

try {
    // Get wasting ID from POST data
    $wasting_id = isset($_POST['wasting_id']) ? intval($_POST['wasting_id']) : 0;
    
    if ($wasting_id <= 0) {
        throw new Exception('IDی بەفیڕۆچوو نادروستە');
    }
    
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        error_log("Failed to establish database connection");
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }
    
    error_log("Database connection established successfully");
    
    // Check if wasting record exists
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM wastings 
        WHERE id = ?
    ");
    $stmt->execute([$wasting_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return result
    echo json_encode([
        'success' => true,
        'exists' => $result['count'] > 0,
        'wasting_id' => $wasting_id
    ]);
    
} catch (Exception $e) {
    error_log("Error in verify_wasting.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 