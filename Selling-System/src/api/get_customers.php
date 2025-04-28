<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Get all customers
    $query = "SELECT id, name FROM customers ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $customers
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Error in get_customers.php: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 