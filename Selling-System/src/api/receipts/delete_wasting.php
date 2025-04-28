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
    
    // Begin transaction
    $conn->beginTransaction();
    
    try {
        // First check if the wasting record exists
        $checkStmt = $conn->prepare("SELECT id FROM wastings WHERE id = ?");
        $checkStmt->execute([$wasting_id]);
        
        if ($checkStmt->rowCount() === 0) {
            throw new Exception('بەفیڕۆچووەکە نەدۆزرایەوە');
        }
        
        // Get wasting items to restore product quantities
        $itemsStmt = $conn->prepare("
            SELECT product_id, quantity 
            FROM wasting_items 
            WHERE wasting_id = ?
        ");
        $itemsStmt->execute([$wasting_id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Restore product quantities
        foreach ($items as $item) {
            $updateStmt = $conn->prepare("
                UPDATE products 
                SET current_quantity = current_quantity + ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Delete wasting items
        $deleteItemsStmt = $conn->prepare("DELETE FROM wasting_items WHERE wasting_id = ?");
        $deleteItemsStmt->execute([$wasting_id]);
        
        // Delete wasting record
        $deleteWastingStmt = $conn->prepare("DELETE FROM wastings WHERE id = ?");
        $deleteWastingStmt->execute([$wasting_id]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'بەفیڕۆچووەکە بە سەرکەوتوویی سڕایەوە'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in delete_wasting.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
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