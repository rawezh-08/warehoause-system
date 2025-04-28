<?php
// Include authentication check


// Include database connection
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get customer ID
    $customerId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Validate data
    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کڕیار نادروستە.']);
        exit;
    }
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Check if customer has any sales
        $sql = "SELECT COUNT(*) FROM sales WHERE customer_id = :customer_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        $salesCount = $stmt->fetchColumn();
        
        if ($salesCount > 0) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'ناتوانرێت ئەم کڕیارە بسڕێتەوە چونکە ' . $salesCount . ' فرۆشتنی هەیە. تکایە سەرەتا ئەو فرۆشتنانە بسڕەوە.']);
            exit;
        }
        
        // Delete debt transactions
        $sql = "DELETE FROM debt_transactions WHERE customer_id = :customer_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        
        // Delete the customer
        $sql = "DELETE FROM customers WHERE id = :customer_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'کڕیار بە سەرکەوتوویی سڕایەوە.']);
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'داواکاری نادروستە.']);
} 