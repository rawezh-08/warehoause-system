<?php
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Supplier.php';

// Create a database connection
$db = new Database();
// Ensure we get a valid PDO connection
$conn = $db->getConnection();

// Create Supplier model instance
$supplierModel = new Supplier($conn);

// Get the JSON input
$jsonData = file_get_contents('php://input');
$supplierData = json_decode($jsonData, true);

// Check if required fields are provided
if (!isset($supplierData['id']) || !isset($supplierData['name']) || !isset($supplierData['phone1'])) {
    echo json_encode([
        'success' => false,
        'message' => 'هەموو خانە پێویستەکان بنووسە'
    ]);
    exit;
}

// Get the original supplier data
$supplierId = filter_var($supplierData['id'], FILTER_SANITIZE_NUMBER_INT);
$originalSupplier = $supplierModel->getById($supplierId);

if (!$originalSupplier) {
    echo json_encode([
        'success' => false,
        'message' => 'دابینکەر نەدۆزرایەوە'
    ]);
    exit;
}

// Clean and prepare data
$cleanData = [
    'name' => trim($supplierData['name']),
    'phone1' => trim($supplierData['phone1']),
    'phone2' => isset($supplierData['phone2']) ? trim($supplierData['phone2']) : '',
    'debt_on_myself' => isset($supplierData['debt_on_myself']) ? (float)$supplierData['debt_on_myself'] : 0,
    'notes' => isset($supplierData['notes']) ? trim($supplierData['notes']) : ''
];

// Try to update the supplier
try {
    if ($supplierModel->update($supplierId, $cleanData)) {
        echo json_encode([
            'success' => true,
            'message' => 'زانیاری دابینکەر بە سەرکەوتوویی نوێ کرایەوە'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'کێشەیەک ڕوویدا لە نوێکردنەوەی زانیاری دابینکەر'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 