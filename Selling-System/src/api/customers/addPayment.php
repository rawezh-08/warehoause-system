<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check if required data is provided
if(!isset($_POST['customerId']) || !isset($_POST['amount']) || !isset($_POST['direction'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'زانیاری پێویست دیاری نەکراوە'
    ]);
    exit;
}

$customerId = $_POST['customerId'];
$amount = floatval($_POST['amount']);
$direction = $_POST['direction'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Validate amount
if($amount <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'بڕی پارە دەبێت گەورەتر بێت لە سفر'
    ]);
    exit;
}

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();

    // Get current customer balance
    $query = "SELECT debit_on_business, debt_on_customer FROM customers WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $customerId);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        $db->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'کڕیاری داواکراو نەدۆزرایەوە'
        ]);
        exit;
    }

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentBalance = $customer['debit_on_business'];
    $currentDebtOnCustomer = $customer['debt_on_customer'];

    // Determine transaction type and balance update
    switch($direction) {
        case 'from_customer':
            // Customer paying their debt or making advance payment
            if($currentBalance > 0) {
                // If customer has debt, apply payment to debt first
                if($amount <= $currentBalance) {
                    // Full amount goes to debt
                    $newBalance = $currentBalance - $amount;
                    $newDebtOnCustomer = $currentDebtOnCustomer;
                    $transactionType = 'payment';
                } else {
                    // Part goes to debt, rest goes to advance
                    $debtPayment = $currentBalance;
                    $advancePayment = $amount - $currentBalance;
                    $newBalance = 0;
                    $newDebtOnCustomer = $currentDebtOnCustomer + $advancePayment;
                    $transactionType = 'payment_and_advance';
                }
            } else {
                // No debt, full amount goes to advance
                $newBalance = $currentBalance;
                $newDebtOnCustomer = $currentDebtOnCustomer + $amount;
                $transactionType = 'advance_payment';
            }
            break;

        case 'to_customer':
            // We're paying our debt to customer
            if($currentDebtOnCustomer > 0) {
                // Use advance payment first
                if($amount <= $currentDebtOnCustomer) {
                    // Full amount from advance
                    $newDebtOnCustomer = $currentDebtOnCustomer - $amount;
                    $newBalance = $currentBalance;
                } else {
                    // Part from advance, rest creates debt
                    $advanceUsed = $currentDebtOnCustomer;
                    $remainingAmount = $amount - $advanceUsed;
                    $newDebtOnCustomer = 0;
                    $newBalance = $currentBalance - $remainingAmount;
                }
            } else {
                // No advance payment available
                if($currentBalance >= 0) {
                    $newBalance = $currentBalance - $amount;
                    $newDebtOnCustomer = $currentDebtOnCustomer;
                } else {
                    if($amount > abs($currentBalance)) {
                        $db->rollBack();
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'بڕی پارە زیاترە لە قەرزەکەمان'
                        ]);
                        exit;
                    }
                    $newBalance = $currentBalance + $amount;
                    $newDebtOnCustomer = $currentDebtOnCustomer;
                }
            }
            $transactionType = 'collection';
            break;

        case 'adjust_balance':
            // Manual balance adjustment
            $newBalance = $currentBalance + $amount;
            $newDebtOnCustomer = $currentDebtOnCustomer;
            $transactionType = 'manual_adjustment';
            break;

        default:
            $db->rollBack();
            echo json_encode([
                'status' => 'error',
                'message' => 'جۆری پارەدان نادروستە'
            ]);
            exit;
    }

    // Update customer balance
    $updateQuery = "UPDATE customers 
                   SET debit_on_business = :balance,
                       debt_on_customer = :debt_on_customer 
                   WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':balance', $newBalance);
    $updateStmt->bindParam(':debt_on_customer', $newDebtOnCustomer);
    $updateStmt->bindParam(':id', $customerId);
    $updateStmt->execute();

    // Record transaction
    $transactionQuery = "INSERT INTO debt_transactions (
        customer_id, amount, transaction_type, notes, created_by
    ) VALUES (
        :customer_id, :amount, :transaction_type, :notes, :created_by
    )";
    
    $transactionStmt = $db->prepare($transactionQuery);
    $transactionStmt->bindParam(':customer_id', $customerId);
    $transactionStmt->bindParam(':amount', $amount);
    $transactionStmt->bindParam(':transaction_type', $transactionType);
    $transactionStmt->bindParam(':notes', $notes);
    $transactionStmt->bindValue(':created_by', 1); // Replace with actual user ID from session
    $transactionStmt->execute();

    // Commit transaction
    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'پارەدان بە سەرکەوتوویی تۆمارکرا',
        'data' => [
            'newBalance' => $newBalance,
            'newDebtOnCustomer' => $newDebtOnCustomer
        ]
    ]);

} catch(PDOException $e) {
    if($db) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'کێشەیەک هەیە لە پەیوەندیکردن بە داتابەیسەوە'
    ]);
}
?> 