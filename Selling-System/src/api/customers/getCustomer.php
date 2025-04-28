<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check if ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ناسنامەی کڕیار دیاری نەکراوە'
    ]);
    exit;
}

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
                guarantor_name,
                guarantor_phone,
                address,
                debit_on_business,
                debt_on_customer,
                notes,
                created_at,
                updated_at
            FROM customers
            WHERE id = :id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format the response
        echo json_encode([
            'status' => 'success',
            'data' => $customer
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'کڕیاری داواکراو نەدۆزرایەوە'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'کێشەیەک هەیە لە پەیوەندیکردن بە داتابەیسەوە'
    ]);
}
?> 