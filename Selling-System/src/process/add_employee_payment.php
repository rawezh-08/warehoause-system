<?php
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $employeeId = $_POST['employeeId'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $paymentType = $_POST['paymentType'] ?? null;
    $paymentDate = $_POST['paymentDate'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $createdBy = 1; // TODO: Replace with actual user ID from session

    // Debug: Log the received data
    error_log("Received data: " . print_r($_POST, true));

    // Validate required fields
    if (!$employeeId || !$amount || !$paymentType || !$paymentDate) {
        echo json_encode([
            'success' => false,
            'message' => 'تکایە هەموو خانە پڕەکان پڕ بکەرەوە',
            'debug' => [
                'employeeId' => $employeeId,
                'amount' => $amount,
                'paymentType' => $paymentType,
                'paymentDate' => $paymentDate
            ]
        ]);
        exit;
    }

    // Validate payment type - updated to match database ENUM
    $allowedPaymentTypes = ['salary', 'bonus', 'overtime'];
    if (!in_array($paymentType, $allowedPaymentTypes)) {
        echo json_encode([
            'success' => false,
            'message' => 'جۆری پارەدان نادروستە',
            'debug' => [
                'paymentType' => $paymentType,
                'allowedTypes' => $allowedPaymentTypes
            ]
        ]);
        exit;
    }

    // Clean and validate amount
    $amount = str_replace(',', '', $amount);
    if (!is_numeric($amount) || $amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'بڕی پارە دەبێت ژمارە بێت و گەورەتر بێت لە سفر',
            'debug' => [
                'amount' => $amount
            ]
        ]);
        exit;
    }

    try {
        // Debug: Log the SQL parameters
        error_log("SQL Parameters: " . print_r([
            'employeeId' => $employeeId,
            'amount' => $amount,
            'paymentType' => $paymentType,
            'paymentDate' => $paymentDate,
            'notes' => $notes,
            'createdBy' => $createdBy
        ], true));

        // Call the stored procedure
        $stmt = $conn->prepare("CALL add_employee_payment(?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $employeeId,
            $amount,
            $paymentType,
            $paymentDate,
            $notes,
            $createdBy
        ]);

        // Get the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug: Log the result
        error_log("Stored procedure result: " . print_r($result, true));

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'پارەدان بە سەرکەوتوویی زیاد کرا',
                'paymentId' => $result['payment_id']
            ]);
        } else {
            throw new Exception("هەڵەیەک ڕوویدا لە کاتی زیادکردنی پارەدان - نەدۆزرایەوە");
        }
    } catch (PDOException $e) {
        // Debug: Log the PDO error
        error_log("PDO Error: " . $e->getMessage());
        error_log("Error Code: " . $e->getCode());
        error_log("SQL State: " . $e->errorInfo[0]);
        
        echo json_encode([
            'success' => false,
            'message' => 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی پارەدان: ' . $e->getMessage(),
            'debug' => [
                'errorCode' => $e->getCode(),
                'sqlState' => $e->errorInfo[0],
                'errorInfo' => $e->errorInfo
            ]
        ]);
    } catch (Exception $e) {
        // Debug: Log the general error
        error_log("General Error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'debug' => [
                'error' => $e->getMessage()
            ]
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'تکایە فۆڕمەکە بە شێوەی دروست نارد بکەرەوە'
    ]);
}

$conn = null; // Close connection
?> 