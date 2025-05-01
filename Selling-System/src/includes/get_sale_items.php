<?php
// Include authentication and database connection
require_once 'auth.php';
require_once '../../config/database.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'فشل في معالجة الطلب',
    'items' => []
];

// Check if sale_id is provided
if (!isset($_POST['sale_id']) || empty($_POST['sale_id'])) {
    $response['message'] = 'Sale ID is required';
    echo json_encode($response);
    exit;
}

$sale_id = $_POST['sale_id'];

try {
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Get sale details
    $saleQuery = "SELECT s.*, c.name as customer_name 
                 FROM sales s 
                 LEFT JOIN customers c ON s.customer_id = c.id 
                 WHERE s.id = :sale_id";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':sale_id', $sale_id);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        $response['message'] = 'No sale found with this ID';
        echo json_encode($response);
        exit;
    }

    // Get sale items
    $itemsQuery = "SELECT si.*, p.name as product_name, p.code as product_code 
                  FROM sale_items si 
                  LEFT JOIN products p ON si.product_id = p.id 
                  WHERE si.sale_id = :sale_id";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bindParam(':sale_id', $sale_id);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare response
    $response = [
        'status' => 'success',
        'message' => 'تم جلب البيانات بنجاح',
        'sale' => $sale,
        'items' => $items
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit; 