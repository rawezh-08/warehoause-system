<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get phone number from POST data
        if (!isset($_POST['phone']) || empty($_POST['phone'])) {
            throw new Exception('ژمارەی مۆبایل پێویستە');
        }
        
        $phone = $_POST['phone'];
        
        // Create database connection
        $db = new Database();
        $conn = $db->getConnection();
        
        // Query to check if phone number exists in customers or suppliers table
        $query = "SELECT c.id AS customer_id, s.id AS supplier_id 
                 FROM customers c 
                 LEFT JOIN suppliers s ON s.phone1 = :phone OR s.phone2 = :phone 
                 WHERE c.phone1 = :phone OR c.phone2 = :phone 
                 LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':phone' => $phone]);
        
        // Return the result
        $response = [
            'exists' => $stmt->rowCount() > 0
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Return error response
        $response = [
            'error' => true,
            'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
} else {
    // Return error for non-POST requests
    $response = [
        'error' => true,
        'message' => 'تەنها داواکاری POST قبوڵ دەکرێت'
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} 