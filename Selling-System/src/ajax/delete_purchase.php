<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if purchase_id is provided
if (!isset($_POST['purchase_id'])) {
    echo json_encode(['success' => false, 'message' => 'Purchase ID is required']);
    exit;
}

$purchaseId = $_POST['purchase_id'];

try {
    $purchaseController = new PurchaseReceiptsController($conn);
    $success = $purchaseController->deletePurchase($purchaseId);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete purchase']);
    }
} catch (Exception $e) {
    error_log("Error in delete_purchase.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the purchase']);
} 