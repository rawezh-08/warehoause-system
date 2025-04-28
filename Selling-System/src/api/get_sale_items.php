<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$sale_id = isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0;

if ($sale_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Sale ID.']);
    exit;
}

try {
    $query = "SELECT 
                si.id as sale_item_id, 
                si.product_id, 
                si.quantity, 
                si.unit_type, 
                si.unit_price, 
                si.returned_quantity, 
                p.name as product_name, 
                p.code as product_code, 
                u.is_piece, 
                u.is_box, 
                u.is_set, 
                p.pieces_per_box, 
                p.boxes_per_set
              FROM sale_items si
              JOIN products p ON si.product_id = p.id
              JOIN units u ON p.unit_id = u.id
              WHERE si.sale_id = :sale_id
              ORDER BY p.name ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sale_id', $sale_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'items' => $items]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}

?> 