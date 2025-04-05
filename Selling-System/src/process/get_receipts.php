<?php
// Include database connection
require_once '../config/db_connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the receipt type is provided
if (!isset($_POST['type'])) {
    echo json_encode([
        'success' => false,
        'message' => 'جۆری پسوڵە دیاری نەکراوە'
    ]);
    exit;
}

// Get the receipt type
$receiptType = $_POST['type'];

// Validate receipt type
if (!in_array($receiptType, ['selling', 'buying', 'wasting'])) {
    echo json_encode([
        'success' => false,
        'message' => 'جۆری پسوڵە نادروستە'
    ]);
    exit;
}

try {
    // Prepare database query based on receipt type
    $query = '';
    
    if ($receiptType === 'selling') {
        $query = "SELECT r.id, r.title, c.name AS customer, r.date, r.total, r.status 
                 FROM receipts r 
                 LEFT JOIN customers c ON r.customer_id = c.id 
                 WHERE r.type = 'selling' 
                 ORDER BY r.date DESC";
    } elseif ($receiptType === 'buying') {
        $query = "SELECT r.id, r.title, v.name AS vendor, r.date, r.vendor_invoice, r.total, r.status 
                 FROM receipts r 
                 LEFT JOIN vendors v ON r.vendor_id = v.id 
                 WHERE r.type = 'buying' 
                 ORDER BY r.date DESC";
    } elseif ($receiptType === 'wasting') {
        $query = "SELECT r.id, r.title, s.name AS responsible, r.date, r.reason, r.total, r.status 
                 FROM receipts r 
                 LEFT JOIN staff s ON r.responsible_id = s.id 
                 WHERE r.type = 'wasting' 
                 ORDER BY r.date DESC";
    }
    
    // Execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    // Fetch all results
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the results
    echo json_encode([
        'success' => true,
        'data' => $receipts
    ]);
    
} catch (PDOException $e) {
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => 'خەتا لە کاتی بەدەستهێنانی زانیاری: ' . $e->getMessage()
    ]);
} 