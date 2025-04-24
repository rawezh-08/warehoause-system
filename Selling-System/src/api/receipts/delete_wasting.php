<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_wasting_errors.log');

// Log the start of the script
error_log("Starting delete_wasting.php script");

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../controllers/receipts/WastingReceiptsController.php';

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
    
    error_log("Processing wasting wasting_id: " . $wasting_id);
    
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        error_log("Failed to establish database connection");
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }
    
    error_log("Database connection established successfully");
    
    try {
        // Call the stored procedure to delete the wasting record
        $stmt = $conn->prepare("CALL DeleteWastingRecord(?)");
        $stmt->execute([$wasting_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'بەفیڕۆچووەکە بە سەرکەوتوویی سڕایەوە'
        ]);
        
    } catch (PDOException $e) {
        // Check if this is a custom error from the stored procedure
        if ($e->getCode() == '45000') {
            throw new Exception($e->getMessage());
        }
        throw $e;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in delete_wasting.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString()
        ]
    ]);
} 