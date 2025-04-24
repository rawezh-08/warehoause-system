<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_draft_errors.log');

// Log the start of the script
error_log("Starting delete_draft.php script");

require_once '../../config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Log POST data
error_log("POST data received: " . print_r($_POST, true));

try {
    // Validate input
    if (!isset($_POST['receipt_id']) || empty($_POST['receipt_id'])) {
        error_log("Receipt ID is missing from POST data");
        throw new Exception('پێناسەی پسووڵە پێویستە');
    }
    
    $receipt_id = intval($_POST['receipt_id']);
    
    error_log("Processing draft receipt_id: " . $receipt_id);
    
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        error_log("Failed to establish database connection");
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }
    
    error_log("Database connection established successfully");
    
    try {
        // Call the stored procedure to delete the draft receipt
        $stmt = $conn->prepare("CALL DeleteDraftReceipt(?)");
        $stmt->execute([$receipt_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'ڕەشنووسی پسووڵە بە سەرکەوتوویی سڕایەوە'
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
    error_log("Error in delete_draft.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
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