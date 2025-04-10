<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Customer.php';

// Set headers to return JSON
header('Content-Type: application/json');

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'شێوازی داواکاری نادروستە. تکایە شێوازی GET بەکاربهێنە.'
    ]);
    exit;
}

// Check if ID parameter is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامەی کڕیار پێویستە.'
    ]);
    exit;
}

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Initialize customer model
    $customerModel = new Customer($conn);
    
    // Get customer by ID
    $customerId = intval($_GET['id']);
    $customer = $customerModel->getById($customerId);
    
    if ($customer) {
        echo json_encode([
            'success' => true,
            'customer' => $customer
        ]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode([
            'success' => false,
            'message' => 'کڕیار نەدۆزرایەوە.'
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