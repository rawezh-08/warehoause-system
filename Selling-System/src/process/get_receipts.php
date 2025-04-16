<?php
// Include database connection
require_once '../config/db_connection.php';

// Get database connection
$pdo = getDbConnection();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the receipt type is provided
if (!isset($_POST['type'])) {
    echo json_encode([
        'success' => false,
        'message' => 'جۆری پسوڵە دیاری نەکراوە'
    ]);
    exit;
}

// Get the receipt type
$receiptType = $_POST['type'];

// Validate receipt type
if (!in_array($receiptType, ['selling', 'buying', 'wasting'])) {
    echo json_encode([
        'success' => false,
        'message' => 'جۆری پسوڵە نادروستە'
    ]);
    exit;
}

try {
    // Prepare database query based on receipt type
    $query = '';
    
    if ($receiptType === 'selling') {
        $query = "SELECT s.id, s.invoice_number as title, c.name AS customer, s.date, 
                        (s.paid_amount + COALESCE(s.remaining_amount, 0)) as total,
                        CASE 
                            WHEN s.remaining_amount > 0 THEN 'قەرز'
                            ELSE 'نەقد'
                        END as status
                 FROM sales s 
                 LEFT JOIN customers c ON s.customer_id = c.id 
                 ORDER BY s.date DESC";
    } elseif ($receiptType === 'buying') {
        $query = "SELECT p.id, p.invoice_number as title, s.name AS vendor, p.date,
                        p.invoice_number as vendor_invoice,
                        (p.paid_amount + COALESCE(p.remaining_amount, 0)) as total,
                        CASE 
                            WHEN p.remaining_amount > 0 THEN 'قەرز'
                            ELSE 'نەقد'
                        END as status
                 FROM purchases p
                 LEFT JOIN suppliers s ON p.supplier_id = s.id 
                 ORDER BY p.date DESC";
    } elseif ($receiptType === 'wasting') {
        $query = "SELECT e.id, 'خەرجی' as title, 'بەفیڕۆچوو' as responsible, 
                        e.expense_date as date, e.notes as reason, 
                        e.amount as total, 'نەقد' as status
                 FROM expenses e 
                 ORDER BY e.expense_date DESC";
    }
    
    // Execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    // Fetch all results
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the results
    echo json_encode([
        'success' => true,
        'data' => $receipts
    ]);
    
} catch (PDOException $e) {
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => 'خەتا لە کاتی بەدەستهێنانی زانیاری: ' . $e->getMessage()
    ]);
} 