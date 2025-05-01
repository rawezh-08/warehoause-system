<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Check if purchase_id is provided
if (!isset($_POST['purchase_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Purchase ID is required']);
    exit;
}

$purchaseId = $_POST['purchase_id'];

try {
    $purchaseController = new PurchaseReceiptsController($conn);
    $items = $purchaseController->getPurchaseItems($purchaseId);

    if ($items) {
        echo json_encode([
            'status' => 'success',
            'items' => $items
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No items found for this purchase'
        ]);
    }
} catch (Exception $e) {
    error_log("Error in get_purchase_items.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while retrieving purchase items'
    ]);
} 