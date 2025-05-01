<?php
// Include database connection
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';

// Set headers
header('Content-Type: application/json');

try {
    // Get all suppliers
    $stmt = $conn->prepare("SELECT id, name FROM suppliers ORDER BY name ASC LIMIT 10");
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for Select2
    $formatted_suppliers = [];
    foreach($suppliers as $supplier) {
        $formatted_suppliers[] = [
            'id' => $supplier['id'],
            'text' => $supplier['name']
        ];
    }
    
    echo json_encode($formatted_suppliers);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 