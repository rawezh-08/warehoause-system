<?php
// Include database connection
require_once '../config/database.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_POST['id']) || !isset($_POST['type'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامە و جۆری پسوڵە پێویستە'
    ]);
    exit;
}

$receipt_id = intval($_POST['id']);
$receipt_type = $_POST['type'];

// Validate receipt type
if ($receipt_type !== 'selling') {
    echo json_encode([
        'success' => false,
        'message' => 'ئەم جۆرە پسوڵەیە پشتگیری ناکرێت بۆ سڕینەوە'
    ]);
    exit;
}

try {
    // Start a transaction
    $conn->beginTransaction();
    
    // Check if the receipt exists and is a draft
    $check_stmt = $conn->prepare("SELECT * FROM sales WHERE id = ? AND is_draft = 1");
    $check_stmt->execute([$receipt_id]);
    $draft_receipt = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$draft_receipt) {
        echo json_encode([
            'success' => false,
            'message' => 'ڕەشنووسی پسوڵە نەدۆزرایەوە یان پێشتر پەسەند کراوە'
        ]);
        $conn->rollBack();
        exit;
    }
    
    // Delete sale items first (foreign key constraint)
    $delete_items_stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
    $delete_items_stmt->execute([$receipt_id]);
    
    // Delete the draft sale
    $delete_sale_stmt = $conn->prepare("DELETE FROM sales WHERE id = ? AND is_draft = 1");
    $delete_sale_stmt->execute([$receipt_id]);
    
    if ($delete_sale_stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'نەتوانرا ڕەشنووسی پسوڵە بسڕدرێتەوە'
        ]);
        $conn->rollBack();
        exit;
    }
    
    // Commit the transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ڕەشنووسی پسوڵە بە سەرکەوتوویی سڕایەوە'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی ڕەشنووس',
        'debug' => [
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage()
        ]
    ]);
}
?> 