<?php
// Include authentication check


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Supplier.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Validate required fields
    if (empty($_POST['supplierName']) || empty($_POST['supplierPhone'])) {
        throw new Exception('تکایە هەموو خانە پێویستەکان پڕبکەوە');
    }
    
    // Create supplier model
    $supplierModel = new Supplier($conn);

    // Debug data
    error_log("Processing supplier data: " . json_encode($_POST));

    // Clean comma from numerical values
    $debtOnMyself = isset($_POST['debt_on_myself']) && $_POST['debt_on_myself'] !== '' 
        ? str_replace(',', '', $_POST['debt_on_myself']) 
        : 0;
    
    $debtOnSupplier = isset($_POST['debt_on_supplier']) && $_POST['debt_on_supplier'] !== '' 
        ? str_replace(',', '', $_POST['debt_on_supplier']) 
        : 0;
    
    // Prepare data
    $data = [
        'name' => $_POST['supplierName'],
        'phone1' => $_POST['supplierPhone'],
        'phone2' => $_POST['supplierPhone2'] ?? '',
        'debt_on_myself' => $debtOnMyself,
        'debt_on_supplier' => $debtOnSupplier,
        'notes' => $_POST['supplierNotes'] ?? null
    ];

    // Add supplier
    $result = $supplierModel->add($data);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'supplier_id' => $result,
            'message' => 'دابینکەر بە سەرکەوتوویی زیاد کرا'
        ]);
    } else {
        throw new Exception('هەڵە لە زیادکردنی دابینکەر - تکایە سەیری فایلی لۆگ بکە بۆ وردەکاری زیاتر');
    }

} catch (Exception $e) {
    error_log("Supplier addition error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 