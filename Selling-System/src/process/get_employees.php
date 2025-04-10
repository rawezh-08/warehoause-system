<?php
require_once '../config/database.php';

try {
    // Get filter parameters
    $name = $_GET['name'] ?? '';
    $position = $_GET['position'] ?? '';
    $phone = $_GET['phone'] ?? '';
    
    // Build query
    $query = "SELECT * FROM employees WHERE 1=1";
    $params = [];
    
    if ($name) {
        $query .= " AND name = ?";
        $params[] = $name;
    }
    
    if ($position) {
        $query .= " AND position = ?";
        $params[] = $position;
    }
    
    if ($phone) {
        $query .= " AND phone LIKE ?";
        $params[] = "%$phone%";
    }
    
    $query .= " ORDER BY id DESC";
    
    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'employees' => $employees
    ]);
    
} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی گەڕانەوەی زانیاری کارمەندان: ' . $e->getMessage()
    ]);
}
?> 