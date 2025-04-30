<?php
// Set content type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Check if invoice number is provided
if (!isset($_POST['invoice_number']) || empty($_POST['invoice_number'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invoice number is required']);
    exit;
}

$invoiceNumber = $_POST['invoice_number'];

// Return test data with whatever invoice number is provided
echo json_encode([
    'status' => 'success',
    'invoice' => $invoiceNumber,
    'items' => [
        [
            'product_name' => 'کاڵای تێست ١',
            'quantity' => 5,
            'unit_type' => 'piece',
            'unit_price' => 10000,
            'total_price' => 50000
        ],
        [
            'product_name' => 'کاڵای تێست ٢',
            'quantity' => 2,
            'unit_type' => 'box',
            'unit_price' => 25000,
            'total_price' => 50000
        ]
    ]
]);
?> 