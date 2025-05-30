<?php
// Enable error reporting
ini_set('display_errors', 0); // Disable display of errors
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/delete_sale_errors.log');

// Log the start of the script
error_log("Starting delete_sale.php script");

// Set headers for JSON response
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';

    // Log POST data
    error_log("POST data received: " . print_r($_POST, true));

    // Validate input
    if (!isset($_POST['receipt_id']) || empty($_POST['receipt_id'])) {
        error_log("Receipt ID is missing from POST data");
        throw new Exception('پێناسەی پسووڵە پێویستە');
    }
    
    $receipt_id = intval($_POST['receipt_id']);
    
    error_log("Processing sale receipt_id: " . $receipt_id);
    
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
        // Check if sale exists and is not a draft
        $stmt = $conn->prepare("SELECT * FROM sales WHERE id = ? AND is_draft = 0");
        $stmt->execute([$receipt_id]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$receipt) {
            throw new Exception('پسووڵەی فرۆشتن نەدۆزرایەوە - ID: ' . $receipt_id);
        }
        
        // Get sale items to restore product quantities
        $stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$receipt_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update product quantities (restore)
        foreach ($items as $item) {
            $stmt = $conn->prepare("UPDATE products SET current_quantity = current_quantity + ? WHERE id = ?");
            $stmt->execute([$item['pieces_count'], $item['product_id']]);
        }
        
        // Delete sale items
        $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$receipt_id]);
        
        // Delete the sale
        $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$receipt_id]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'پسووڵەی فرۆشتن بە سەرکەوتوویی سڕایەوە'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in delete_sale.php: " . $e->getMessage());
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
} catch (Error $e) {
    // Log the error
    error_log("PHP Error in delete_sale.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەکی سیستەمی ڕوویدا',
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString()
        ]
    ]);
} 