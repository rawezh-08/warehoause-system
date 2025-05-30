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

// Check if the ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID is required'
    ]);
    exit;
}

$supplierId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get supplier by ID to check if exists
$supplier = $supplierModel->getById($supplierId);

if (!$supplier) {
    echo json_encode([
        'success' => false,
        'message' => 'دابینکەر نەدۆزرایەوە'
    ]);
    exit;
}

// Try to delete the supplier
try {
    if ($supplierModel->delete($supplierId)) {
        echo json_encode([
            'success' => true,
            'message' => 'دابینکەر بە سەرکەوتوویی سڕایەوە'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'کێشەیەک ڕوویدا لە سڕینەوەی دابینکەر'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 