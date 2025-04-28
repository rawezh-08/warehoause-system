<?php


require_once '../config/database.php';

// Get data from request
$data = json_decode(file_get_contents('php://input'), true);

// Validate required data
if (!isset($data['id']) || !isset($data['name']) || !isset($data['phone']) || !isset($data['salary'])) {
    echo json_encode([
        'success' => false,
        'message' => 'داتای پێویست دیاری نەکراوە'
    ]);
    exit;
}

$id = $data['id'];
$name = $data['name'];
$phone = $data['phone'];
$salary = $data['salary'];
$notes = $data['notes'] ?? '';

try {
    // Check if employee with this phone number already exists (except for the current employee)
    $checkQuery = "SELECT id FROM employees WHERE phone = ? AND id != ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$phone, $id]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ژمارەی مۆبایل پێشتر بەکارهاتووە'
        ]);
        exit;
    }
    
    // Update employee
    $query = "UPDATE employees SET name = ?, phone = ?, salary = ?, notes = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$name, $phone, $salary, $notes, $id]);
    
    // Check if employee was updated
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'زانیاری کارمەند بە سەرکەوتوویی نوێ کرایەوە'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'هیچ گۆڕانکاریەک نەکرا'
        ]);
    }
    
} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی نوێکردنەوەی زانیاری کارمەند: ' . $e->getMessage()
    ]);
}
?> 