<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Check if debt_on_customer column exists
    $checkColumnQuery = "SHOW COLUMNS FROM customers LIKE 'debt_on_customer'";
    $checkColumnStmt = $db->prepare($checkColumnQuery);
    $checkColumnStmt->execute();
    
    if ($checkColumnStmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $addColumnQuery = "ALTER TABLE customers ADD COLUMN debt_on_customer DECIMAL(15,2) DEFAULT 0 AFTER debit_on_business";
        $db->exec($addColumnQuery);
    }

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
            ORDER BY name ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the response
        echo json_encode([
            'status' => 'success',
            'data' => $customers
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'data' => []
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'کێشەیەک هەیە لە پەیوەندیکردن بە داتابەیسەوە: ' . $e->getMessage()
    ]);
}
?> 