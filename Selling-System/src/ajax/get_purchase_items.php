<?php
// Include necessary files
require_once '../config/database.php';
require_once '../include/db_connection.php';
session_start();

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'items' => [],
    'total_amount' => 0
];

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Get the purchase ID from the POST data
if (!isset($_POST['purchase_id']) || empty($_POST['purchase_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Purchase ID is required'
    ]);
    exit;
}

$purchase_id = $_POST['purchase_id'];

try {
    // Get all purchase items for the given purchase ID
    $query = "SELECT pi.*, p.name as product_name, 
                    COALESCE((SELECT SUM(pr.quantity) 
                    FROM product_returns pr 
                    WHERE pr.receipt_id = :purchase_id 
                    AND pr.receipt_type = 'buying' 
                    AND pr.item_id = pi.id), 0) as returned_quantity
              FROM purchase_items pi
              JOIN products p ON pi.product_id = p.id
              WHERE pi.purchase_id = :purchase_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':purchase_id', $purchase_id);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return successful response with items data
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 