<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Get all suppliers
    $query = "SELECT id, name FROM suppliers ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $suppliers
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Error in get_suppliers.php: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 