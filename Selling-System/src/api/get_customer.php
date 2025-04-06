<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Customer.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Customer ID is required");
    }
    
    $customerId = (int)$_GET['id'];
    
    // Create Customer model
    $customerModel = new Customer($conn);
    
    // Get customer data
    $customer = $customerModel->getById($customerId);
    
    if (!$customer) {
        throw new Exception("Customer not found");
    }
    
    // Return customer data
    echo json_encode([
        'success' => true,
        'customer' => $customer
    ]);
    
} catch (Exception $e) {
    // Return error message
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 