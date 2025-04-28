<?php


require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['employee_id'])) {
        throw new Exception('Employee ID is required');
    }

    $employee_id = intval($_GET['employee_id']);
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT salary FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    
    $salary = $stmt->fetchColumn();
    
    if ($salary === false) {
        throw new Exception('Employee not found');
    }
    
    echo json_encode([
        'success' => true,
        'salary' => $salary
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 