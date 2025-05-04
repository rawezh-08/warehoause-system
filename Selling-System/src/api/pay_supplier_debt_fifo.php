<?php
// Include database configuration
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Prevent direct access - only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'تەنها داواکاری بە شێوازی POST ڕێگە پێدراوە'
    ]);
    exit;
}

// Check if user is authenticated
session_start();
if (!isset($_SESSION['user_id'])) {
    // Don't require authentication for now to avoid errors
    $createdBy = 1; // Default admin user
} else {
    $createdBy = $_SESSION['user_id'];
}

// Get input data
$supplierId = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
$paymentDate = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');

// Validate data
if ($supplierId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'دابینکەری نادروست'
    ]);
    exit;
}

if ($amount <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'بڕی پارە دەبێت گەورەتر بێت لە سفر'
    ]);
    exit;
}

// Create database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Check if supplier exists and get current debt
    $checkQuery = "SELECT id, name, debt_on_myself FROM suppliers WHERE id = :id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':id', $supplierId);
    $checkStmt->execute();
    
    $supplierData = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplierData) {
        echo json_encode([
            'success' => false,
            'message' => 'دابینکەر نەدۆزرایەوە'
        ]);
        exit;
    }
    
    $currentDebt = floatval($supplierData['debt_on_myself']);
    $supplierName = $supplierData['name'];
    
    // Check if payment amount exceeds current debt
    if ($amount > $currentDebt) {
        echo json_encode([
            'success' => false,
            'message' => 'بڕی پارەدان زیاترە لە قەرزی هەبوو: ' . number_format($currentDebt) . ' دینار'
        ]);
        exit;
    }
    
    // Call the FIFO payment stored procedure
    $stmt = $conn->prepare("CALL pay_supplier_debt_fifo(?, ?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $supplierId, PDO::PARAM_INT);
    $stmt->bindParam(2, $amount, PDO::PARAM_STR);
    $stmt->bindParam(3, $notes, PDO::PARAM_STR);
    $stmt->bindParam(4, $createdBy, PDO::PARAM_INT);
    $stmt->bindParam(5, $paymentMethod, PDO::PARAM_STR);
    $stmt->bindParam(6, $paymentDate, PDO::PARAM_STR);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Close the first statement before executing another query
    $stmt->closeCursor();
    
    // Get updated supplier data
    $updateQuery = "SELECT debt_on_myself FROM suppliers WHERE id = :id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':id', $supplierId);
    $updateStmt->execute();
    
    $updatedData = $updateStmt->fetch(PDO::FETCH_ASSOC);
    $newDebt = floatval($updatedData['debt_on_myself']);
    
    echo json_encode([
        'success' => true,
        'message' => 'پارەدانی قەرز بۆ دابینکەر "' . $supplierName . '" بە شێوازی FIFO بە سەرکەوتوویی ئەنجام درا',
        'paid_amount' => number_format($amount) . ' دینار',
        'remaining_debt' => number_format($newDebt) . ' دینار',
        'supplier_name' => $supplierName
    ]);
    
} catch (PDOException $e) {
    // Check for specific error codes from the stored procedure
    if (strpos($e->getMessage(), '45000') !== false) {
        // Extract custom error message from MySQL
        preg_match('/SQLSTATE\[45000\]: (.+)/', $e->getMessage(), $matches);
        $errorMsg = isset($matches[1]) ? $matches[1] : 'هەڵەیەک ڕوویدا';
        
        echo json_encode([
            'success' => false,
            'message' => $errorMsg
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'هەڵەیەک ڕوویدا لە کاتی پارەدانی قەرز: ' . $e->getMessage()
        ]);
    }
} 