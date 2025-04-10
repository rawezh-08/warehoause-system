<?php
require_once '../config/database.php';

// Get employee ID from request
$employeeId = $_GET['id'] ?? null;

if (!$employeeId) {
    echo json_encode([
        'success' => false,
        'message' => 'IDی کارمەند دیاری نەکراوە'
    ]);
    exit;
}

try {
    // Get employee by ID
    $query = "SELECT * FROM employees WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$employeeId]);
    
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($employee) {
        // Return success response
        echo json_encode([
            'success' => true,
            'employee' => $employee
        ]);
    } else {
        // Employee not found
        echo json_encode([
            'success' => false,
            'message' => 'کارمەند نەدۆزرایەوە'
        ]);
    }
    
} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی گەڕانەوەی زانیاری کارمەند: ' . $e->getMessage()
    ]);
}
?> 