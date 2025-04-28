<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Check if sale ID is provided
if (!isset($_GET['sale_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Sale ID is required']);
    exit;
}

$saleId = intval($_GET['sale_id']);

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get sale items
$query = "SELECT si.*, p.name as product_name 
          FROM sale_items si 
          JOIN products p ON si.product_id = p.id 
          WHERE si.sale_id = :sale_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':sale_id', $saleId);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return items as JSON
header('Content-Type: application/json');
echo json_encode($items); 