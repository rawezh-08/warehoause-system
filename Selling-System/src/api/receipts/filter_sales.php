<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../Controllers/receipts/SaleReceiptsController.php';

try {
    // Set up filters based on request parameters
    $filters = [];
    
    if (isset($_POST['start_date']) && !empty($_POST['start_date'])) {
        $filters['start_date'] = $_POST['start_date'];
    }
    
    if (isset($_POST['end_date']) && !empty($_POST['end_date'])) {
        $filters['end_date'] = $_POST['end_date'];
    }
    
    if (isset($_POST['customer_name']) && !empty($_POST['customer_name'])) {
        $filters['customer_name'] = $_POST['customer_name'];
    }
    
    if (isset($_POST['invoice_number']) && !empty($_POST['invoice_number'])) {
        $filters['invoice_number'] = $_POST['invoice_number'];
    }
    
    // Initialize controller
    $salesController = new SaleReceiptsController($conn);
    
    // Get filtered data
    $salesData = $salesController->getSalesData(0, 0, $filters);
    
    // Format only the date, leave numbers as is
    foreach ($salesData as &$row) {
        // Ensure numeric values are returned as numbers, not strings
        if (isset($row['total_amount'])) {
            $row['total_amount'] = floatval($row['total_amount']);
        }
        if (isset($row['subtotal'])) {
            $row['subtotal'] = floatval($row['subtotal']);
        }
        if (isset($row['shipping_cost'])) {
            $row['shipping_cost'] = floatval($row['shipping_cost']);
        }
        if (isset($row['other_costs'])) {
            $row['other_costs'] = floatval($row['other_costs']);
        }
        if (isset($row['discount'])) {
            $row['discount'] = floatval($row['discount']);
        }
        
        // For empty values, set defaults
        $row['shipping_cost'] = $row['shipping_cost'] ?? 0;
        $row['other_costs'] = $row['other_costs'] ?? 0;
        $row['discount'] = $row['discount'] ?? 0;
    }

    // Cache results for 5 minutes (300 seconds)
    header('Cache-Control: max-age=300, public');
    
    // Return success response with data
    echo json_encode([
        'success' => true,
        'data' => $salesData,
        'count' => count($salesData)
    ]);
    
} catch (Exception $e) {
    // Log detailed error
    error_log("Error in filter_sales.php: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " on line " . $e->getLine());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 