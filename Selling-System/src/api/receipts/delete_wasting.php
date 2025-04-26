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
    
    // Debug: Log the wasting ID
    error_log("Deleting wasting ID: " . $wasting_id);
    
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        error_log("Failed to establish database connection");
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }
    
    error_log("Database connection established successfully");
    
    // Check if wasting exists
    $stmt = $conn->prepare("SELECT * FROM wastings WHERE id = ?");
    $stmt->execute([$wasting_id]);
    $wasting = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Log the query result
    error_log("Query result: " . print_r($wasting, true));
    
    if (!$wasting) {
        throw new Exception('بەفیڕۆچوو نەدۆزرایەوە');
    }
    
    // Delete the wasting
    $stmt = $conn->prepare("DELETE FROM wastings WHERE id = ?");
    $stmt->execute([$wasting_id]);
    
    // Debug: Log the number of affected rows
    error_log("Number of affected rows: " . $stmt->rowCount());
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'بەفیڕۆچوو بە سەرکەوتوویی سڕایەوە'
        ]);
    } else {
        throw new Exception('هیچ ڕیزێک نەگۆڕا');
    }
    
} catch (Exception $e) {
    // Debug: Log any errors
    error_log("Error in delete_wasting.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 