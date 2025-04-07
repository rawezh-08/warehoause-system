<?php
// Include database connection
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if transaction ID is provided
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $transactionId = intval($_GET['id']);
    
    try {
        // Get transaction details
        $sql = "SELECT * FROM debt_transactions WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $transactionId);
        $stmt->execute();
        
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            echo json_encode(['success' => true, 'transaction' => $transaction]);
        } else {
            echo json_encode(['success' => false, 'message' => 'مامەڵەی قەرز نەدۆزرایەوە.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی مامەڵەی قەرز پێویستە.']);
} 