<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Prepare query
    $query = "SELECT 
                id,
                name,
                phone1,
                phone2,
                debt_on_myself,
                debt_on_supplier,
                notes,
                created_at,
                updated_at
              FROM suppliers
              ORDER BY name ASC";
              
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Fetch all suppliers
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $suppliers
    ]);
    
} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی بارکردنی زانیارییەکان: ' . $e->getMessage()
    ]);
} 