<?php
/**
 * API endpoint for adding general expenses (withdrawals)
 * This handles other warehouse expenses
 */

// Include database connection
require_once '../includes/db_connection.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'خەتایەک ڕوویدا',
    'data' => null
];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'داواکاری نادروستە';
    echo json_encode($response);
    exit;
}

// Get form data
$amount = $_POST['amount'] ?? null;
$expenseDate = $_POST['expenseDate'] ?? null;
$notes = $_POST['notes'] ?? '';
$createdBy = $_POST['createdBy'] ?? 1; // Default to admin user if not specified

// Validate required fields
if (!$amount || !$expenseDate || !$notes) {
    $response['message'] = 'تکایە هەموو زانیارییەکان بنووسە';
    echo json_encode($response);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Format expense date
    $formattedDate = date('Y-m-d', strtotime($expenseDate));

    // Call stored procedure to add expense
    $stmt = $conn->prepare("CALL add_expense(?, ?, ?, ?)");
    $stmt->execute([
        $amount,
        $formattedDate,
        $notes,
        $createdBy
    ]);
    
    // Get result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Commit transaction
    $conn->commit();
    
    // Set success response
    $response['status'] = 'success';
    $response['message'] = 'مەسروفات بە سەرکەوتوویی زیادکرا';
    $response['data'] = [
        'id' => $result['result']
    ];
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    // Set error message
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit; 