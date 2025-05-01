<?php
// Include database connection
require_once '../config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log incoming request
error_log('get_sale_items.php accessed with POST data: ' . json_encode($_POST));

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'فشل في معالجة الطلب',
    'items' => []
];

// Check if sale_id is provided
if (!isset($_POST['sale_id']) || empty($_POST['sale_id'])) {
    $response['message'] = 'Sale ID is required';
    error_log('Error: Sale ID is missing');
    echo json_encode($response);
    exit;
}

$sale_id = $_POST['sale_id'];
error_log('Processing sale_id: ' . $sale_id);

try {
    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    error_log('Database connection established');

    // Get sale details
    $saleQuery = "SELECT s.*, c.name as customer_name 
                 FROM sales s 
                 LEFT JOIN customers c ON s.customer_id = c.id 
                 WHERE s.id = :sale_id";
    $saleStmt = $conn->prepare($saleQuery);
    $saleStmt->bindParam(':sale_id', $sale_id);
    $saleStmt->execute();
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
    error_log('Sale query executed. Result: ' . ($sale ? 'Sale found' : 'No sale found'));

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
    error_log('Items query executed. Found: ' . count($items) . ' items');

    // Prepare response
    $response = [
        'status' => 'success',
        'message' => 'تم جلب البيانات بنجاح',
        'sale' => $sale,
        'items' => $items
    ];
    error_log('Success response prepared');

} catch (PDOException $e) {
    error_log('PDO Error: ' . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return response as JSON
header('Content-Type: application/json');
error_log('Sending response: ' . json_encode($response));
echo json_encode($response);
exit; 