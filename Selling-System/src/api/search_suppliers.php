<?php
// Include database connection
require_once '../config/database.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? $_GET['q'] : '';

try {
    // Build search query
    $sql = "SELECT 
                id, 
                name, 
                phone1, 
                phone2, 
                address,
                debt_on_myself,
                notes
            FROM suppliers
            WHERE name LIKE :query OR phone1 LIKE :query OR phone2 LIKE :query
            ORDER BY name ASC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->execute();
    
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format suppliers for Select2
    $formattedSuppliers = [];
    foreach ($suppliers as $supplier) {
        $formattedSuppliers[] = [
            'id' => $supplier['id'],
            'text' => $supplier['name'],
            'phone1' => $supplier['phone1'],
            'phone2' => $supplier['phone2'],
            'address' => $supplier['address'],
            'debt_on_myself' => $supplier['debt_on_myself'],
            'notes' => $supplier['notes']
        ];
    }
    
    echo json_encode($formattedSuppliers);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 