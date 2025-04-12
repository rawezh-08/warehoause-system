<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['id']) || empty($input['id'])) {
        throw new Exception('ناسنامەی کارمەند دیاری نەکراوە');
    }

    $employeeId = intval($input['id']);

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Check if employee exists
    $checkStmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
    $checkStmt->execute([$employeeId]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('کارمەندەکە نەدۆزرایەوە');
    }

    // Begin transaction
    $conn->beginTransaction();

    try {
        // Delete the employee
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'کارمەندەکە بە سەرکەوتوویی سڕایەوە'
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 