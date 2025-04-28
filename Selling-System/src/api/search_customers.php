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
                guarantor_name,
                guarantor_phone,
                debit_on_business,
                notes
            FROM customers
            WHERE name LIKE :query OR phone1 LIKE :query OR phone2 LIKE :query
            ORDER BY name ASC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->execute();
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format customers for Select2
    $formattedCustomers = [];
    foreach ($customers as $customer) {
        $formattedCustomers[] = [
            'id' => $customer['id'],
            'text' => $customer['name'],
            'phone1' => $customer['phone1'],
            'phone2' => $customer['phone2'],
            'address' => $customer['address'],
            'guarantor_name' => $customer['guarantor_name'],
            'guarantor_phone' => $customer['guarantor_phone'],
            'debit_on_business' => $customer['debit_on_business'],
            'notes' => $customer['notes']
        ];
    }
    
    echo json_encode($formattedCustomers);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 