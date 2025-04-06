<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Customer.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }
    
    // Check if ID is provided
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("Customer ID is required");
    }
    
    $customerId = (int)$_POST['id'];
    
    // Extract customer data
    $customerData = [
        'name' => $_POST['name'] ?? '',
        'phone1' => $_POST['phone1'] ?? '',
        'phone2' => $_POST['phone2'] ?? null,
        'address' => $_POST['address'] ?? null,
        'guarantor_name' => $_POST['guarantor_name'] ?? null,
        'guarantor_phone' => $_POST['guarantor_phone'] ?? null,
        'debit_on_business' => isset($_POST['debit_on_business']) ? (float)$_POST['debit_on_business'] : 0,
        'notes' => $_POST['notes'] ?? null
    ];
    
    // Validate required fields
    if (empty($customerData['name'])) {
        throw new Exception("ناوی کڕیار داواکراوە");
    }
    
    if (empty($customerData['phone1'])) {
        throw new Exception("ژمارەی مۆبایل داواکراوە");
    }
    
    // Create Customer model
    $customerModel = new Customer($conn);
    
    // Get original customer data
    $originalCustomer = $customerModel->getById($customerId);
    
    if (!$originalCustomer) {
        throw new Exception("کڕیار نەدۆزرایەوە");
    }
    
    // Update customer
    $result = $customerModel->update($customerId, $customerData);
    
    if (!$result) {
        throw new Exception("هەڵە لە نوێکردنەوەی زانیاری کڕیار");
    }
    
    // Note: The debt transaction is already handled in the Customer::update method
    // So we don't need to add it here, removing the duplicate code
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => "زانیاری کڕیار بە سەرکەوتوویی نوێ کرایەوە"
    ]);
    
} catch (Exception $e) {
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 