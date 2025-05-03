<?php
require_once '../includes/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    
    if (empty($phone)) {
        echo json_encode(['error' => 'Phone number is required']);
        exit;
    }

    try {
        // Check in customers table
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers WHERE phone1 = :phone OR phone2 = :phone");
        $stmt->execute(['phone' => $phone]);
        $customerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Check in suppliers table
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM suppliers WHERE supplier_phone = :phone OR supplier_phone2 = :phone");
        $stmt->execute(['phone' => $phone]);
        $supplierCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Check in business_partners table
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM business_partners WHERE partner_phone1 = :phone OR partner_phone2 = :phone");
        $stmt->execute(['phone' => $phone]);
        $partnerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $totalCount = $customerCount + $supplierCount + $partnerCount;

        if ($totalCount > 0) {
            echo json_encode([
                'exists' => true,
                'message' => 'ئەم ژمارەیە پێشتر بەکارهاتووە لە سیستەمەکەدا'
            ]);
        } else {
            echo json_encode([
                'exists' => false,
                'message' => 'ئەم ژمارەیە بەردەستە'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'error' => 'Database error occurred',
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
} 