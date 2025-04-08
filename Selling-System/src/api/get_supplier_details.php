<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Check if supplier_id is provided
if (!isset($_GET['supplier_id']) || empty($_GET['supplier_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ناسنامەی دابینکەر پێویستە'
    ]);
    exit;
}

$supplierId = $_GET['supplier_id'];
$conn = null;

try {
    // Connect to database using the global $conn from config
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
    
    // Get supplier details
    $stmt = $conn->prepare("
        SELECT 
            id,
            name,
            phone1,
            phone2,
            notes,
            debt_on_myself,
            debt_on_supplier
        FROM 
            suppliers
        WHERE 
            id = :supplier_id
    ");
    
    $stmt->bindParam(':supplier_id', $supplierId, PDO::PARAM_INT);
    $stmt->execute();
    
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($supplier) {
        echo json_encode([
            'success' => true,
            'supplier' => $supplier
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'دابینکەر نەدۆزرایەوە'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
} finally {
    // Close connection
    if ($conn) {
        $conn = null;
    }
} 