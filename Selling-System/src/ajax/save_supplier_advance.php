<?php

// Include database connection
require_once '../config/database.php';
require_once '../../includes/auth.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها داواکاری POST قبوڵ دەکرێت']);
    exit;
}

// Get POST data
$supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$advance_date = isset($_POST['advance_date']) ? $_POST['advance_date'] : date('Y-m-d');
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
$transaction_type = isset($_POST['transaction_type']) ? $_POST['transaction_type'] : 'supplier_advance';

// Validate required fields
if (!$supplier_id || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'تکایە هەموو خانەکان پڕ بکەرەوە']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check if supplier has debt
    $debtQuery = "SELECT debt_on_myself FROM suppliers WHERE id = :supplier_id";
    $debtStmt = $conn->prepare($debtQuery);
    $debtStmt->bindParam(':supplier_id', $supplier_id);
    $debtStmt->execute();
    $supplier = $debtStmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplier) {
        echo json_encode([
            'success' => false, 
            'message' => 'دابینکەر نەدۆزرایەوە'
        ]);
        exit;
    }

    if ($supplier['debt_on_myself'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ناتوانرێت پارەی پێشەکی بدەیت چونکە قەرزمان لەسەرە بە بڕی ' . number_format($supplier['debt_on_myself']) . ' دینار'
        ]);
        exit;
    }

    // Prepare metadata for notes
    $metadata = [
        'payment_method' => $payment_method,
        'notes' => $notes
    ];
    
    // Encode metadata to JSON
    $jsonNotes = json_encode($metadata);
    if ($jsonNotes === false) {
        echo json_encode([
            'success' => false,
            'message' => 'هەڵەیەک ڕوویدا لە کاتی پێکهێنانی تێبینیەکان'
        ]);
        exit;
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Insert advance payment transaction
        $query = "INSERT INTO supplier_debt_transactions 
                  (supplier_id, amount, transaction_type, notes, created_by) 
                  VALUES (:supplier_id, :amount, :transaction_type, :notes, :created_by)";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':supplier_id', $supplier_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':transaction_type', $transaction_type);
        $stmt->bindParam(':notes', $jsonNotes);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);

        if (!$stmt->execute()) {
            throw new PDOException('هەڵەیەک ڕوویدا لە کاتی تۆمارکردنی پارەی پێشەکی');
        }

        // Update supplier's debt_on_supplier
        $updateQuery = "UPDATE suppliers 
                       SET debt_on_supplier = debt_on_supplier + :amount 
                       WHERE id = :supplier_id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':amount', $amount);
        $updateStmt->bindParam(':supplier_id', $supplier_id);
        
        if (!$updateStmt->execute()) {
            throw new PDOException('هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەی بەلەمەکانی دابینکەر');
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'پارەی پێشەکی بە سەرکەوتوویی تۆمارکرا',
            'transaction_id' => $conn->lastInsertId()
        ]);
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە داتابەیس: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} 