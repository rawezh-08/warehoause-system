<?php
// Include database connection
require_once '../../includes/db_connection.php';
require_once '../../includes/auth.php';

// Set headers
header('Content-Type: application/json');

try {
    // Get all purchases with related data
    $query = "SELECT 
                p.id,
                p.invoice_number,
                p.date,
                p.subtotal,
                p.shipping_cost,
                p.other_cost,
                p.discount,
                p.total_amount,
                p.paid_amount,
                p.remaining_amount,
                p.payment_type,
                p.notes,
                s.name as supplier_name,
                GROUP_CONCAT(CONCAT(pi.product_name, ' (', pi.quantity, ')') SEPARATOR ', ') as products_list
              FROM purchases p
              LEFT JOIN suppliers s ON p.supplier_id = s.id
              LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
              GROUP BY p.id
              ORDER BY p.date DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $response = [
        'success' => true,
        'data' => $purchases
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    // Log the error
    error_log("Database Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان',
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("General Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان',
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
} 