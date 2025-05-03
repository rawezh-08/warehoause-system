<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    
    if (empty($phone)) {
        echo json_encode(['error' => 'Phone number is required']);
        exit;
    }

    try {
        // Check in customers table
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers WHERE phone1 = ? OR phone2 = ?");
        $stmt->execute([$phone, $phone]);
        $customerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Check in suppliers table
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM suppliers WHERE phone1 = ? OR phone2 = ?");
        $stmt->execute([$phone, $phone]);
        $supplierCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Check in employees table
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM employees WHERE phone = ?");
        $stmt->execute([$phone]);
        $employeeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $totalCount = $customerCount + $supplierCount + $employeeCount;

        echo json_encode([
            'exists' => $totalCount > 0,
            'message' => $totalCount > 0 ? 'ئەم ژمارە مۆبایلە پێشتر تۆمار کراوە' : 'ئەم ژمارە مۆبایلە بەردەستە'
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error occurred']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
} 