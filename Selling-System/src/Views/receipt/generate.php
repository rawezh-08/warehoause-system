<?php

require_once '../../config/database.php';
require_once '../../controllers/ReceiptController.php';

// Check if sale_id is provided
if (!isset($_GET['sale_id'])) {
    die("Sale ID is required");
}

$sale_id = $_GET['sale_id'];

// Initialize receipt controller and generate receipt
$receiptController = new ReceiptController($conn);
$receiptController->generateReceipt($sale_id);
?> 