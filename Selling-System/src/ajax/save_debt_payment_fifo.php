<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get POST data
        $customerId = $_POST['customer_id'];
        $amount = $_POST['amount'];
        $paymentMethod = $_POST['payment_method'];
        $notes = $_POST['notes'];
        $paymentDate = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $createdBy = $_SESSION['user_id']; // Assuming you have user session
        
        // Call the FIFO procedure
        $stmt = $conn->prepare("CALL pay_customer_debt_fifo(?, ?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $customerId);
        $stmt->bindParam(2, $amount);
        $stmt->bindParam(3, $notes);
        $stmt->bindParam(4, $createdBy);
        $stmt->bindParam(5, $paymentMethod);
        $stmt->bindParam(6, $paymentDate);
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['status'] === 'success') {
            echo json_encode([
                'success' => true,
                'message' => 'قەرز بە سەرکەوتوویی گەڕێنرایەوە',
                'paid_amount' => $result['paid_amount'],
                'remaining_debt' => $result['remaining_debt']
            ]);
        } else {
            throw new Exception('هەڵەیەک ڕوویدا لە کاتی گەڕاندنەوەی قەرز');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?> 