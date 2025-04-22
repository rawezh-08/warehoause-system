<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Customer.php';

// Set headers to return JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'شێوازی داواکاری نادروستە. تکایە شێوازی POST بەکاربهێنە.'
    ]);
    exit;
}

// Get the JSON data from the request body
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Check if JSON is valid
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'داتای JSON نادروستە.'
    ]);
    exit;
}

// Validate required fields
if (!isset($data['id']) || empty($data['id']) || 
    !isset($data['name']) || empty($data['name']) || 
    !isset($data['phone1']) || empty($data['phone1'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'زانیاری نادروست. تکایە ناسنامە، ناو و ژمارەی مۆبایل بنووسە.'
    ]);
    exit;
}

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize customer model
    $customerModel = new Customer($conn);
    
    // Check if customer exists
    $customerId = intval($data['id']);
    $existingCustomer = $customerModel->getById($customerId);
    
    if (!$existingCustomer) {
        http_response_code(404); // Not Found
        echo json_encode([
            'success' => false,
            'message' => 'کڕیار نەدۆزرایەوە.'
        ]);
        exit;
    }
    
    // Prepare customer data for update
    $customerData = [
        'name' => $data['name'],
        'phone1' => $data['phone1'],
        'phone2' => $data['phone2'] ?? '',
        'guarantor_name' => $data['guarantor_name'] ?? '',
        'guarantor_phone' => $data['guarantor_phone'] ?? '',
        'address' => $data['address'] ?? '',
        'debit_on_business' => $data['debit_on_business'] ?? 0,
        'notes' => $data['notes'] ?? ''
    ];
    
    // Update customer
    $result = $customerModel->update($customerId, $customerData);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'زانیاری کڕیار بە سەرکەوتوویی نوێ کرایەوە.'
        ]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'success' => false,
            'message' => 'هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەی زانیاری کڕیار.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
}
?> 