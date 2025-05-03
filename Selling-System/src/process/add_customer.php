<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Customer.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Validate required fields
    if (empty($_POST['businessMan']) || empty($_POST['phone1'])) {
        throw new Exception('تکایە هەموو خانە پێویستەکان پڕبکەوە');
    }
    
    // Validate phone format
    if (!preg_match('/^07\d{9}$/', $_POST['phone1'])) {
        throw new Exception('ژمارەی مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
    }
    
    // Validate phone2 if provided
    if (!empty($_POST['phone2']) && !preg_match('/^07\d{9}$/', $_POST['phone2'])) {
        throw new Exception('ژمارەی مۆبایلی دووەم دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
    }
    
    // Validate guarantor phone if provided
    if (!empty($_POST['guarantorPhone']) && !preg_match('/^07\d{9}$/', $_POST['guarantorPhone'])) {
        throw new Exception('ژمارەی مۆبایلی کەفیل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت');
    }

    // Create customer model
    $customerModel = new Customer($conn);

    // Debug data
    error_log("Processing customer data: " . json_encode($_POST));

    // Clean comma from numerical values
    $debitOnBusiness = isset($_POST['debitOnBusiness']) && $_POST['debitOnBusiness'] !== '' ? str_replace(',', '', $_POST['debitOnBusiness']) : 0;
    $debtOnCustomer = isset($_POST['debt_on_customer']) && $_POST['debt_on_customer'] !== '' ? str_replace(',', '', $_POST['debt_on_customer']) : 0;
    
    // Prepare data
    $data = [
        'name' => $_POST['businessMan'],
        'phone1' => $_POST['phone1'],
        'phone2' => $_POST['phone2'] ?? null,
        'guarantor_name' => $_POST['guarantorName'] ?? null,
        'guarantor_phone' => $_POST['guarantorPhone'] ?? null,
        'address' => $_POST['customerAddress'] ?? null,
        'debit_on_business' => $debitOnBusiness,
        'debt_on_customer' => $debtOnCustomer,
        'notes' => $_POST['customerNotes'] ?? null,
        'is_business_partner' => 0,
        'supplier_id' => isset($_POST['supplier_id']) ? $_POST['supplier_id'] : null
    ];

    // Add customer
    $result = $customerModel->add($data);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'customer_id' => $result,
            'message' => 'کڕیار بە سەرکەوتوویی زیاد کرا'
        ]);
    } else {
        throw new Exception('هەڵە لە زیادکردنی کڕیار - تکایە سەیری فایلی لۆگ بکە بۆ وردەکاری زیاتر');
    }

} catch (Exception $e) {
    error_log("Customer addition error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 