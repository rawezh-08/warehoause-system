<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Debug logging
$input = file_get_contents('php://input');
error_log("Received input: " . $input);

try {
    $data = json_decode($input, true);
    error_log("Decoded data: " . print_r($data, true));
    
    if (!isset($data['id']) || !isset($data['type'])) {
        throw new Exception('ID and type are required');
    }

    $id = $data['id'];
    $type = $data['type'];
    
    error_log("Processing delete: ID=$id, Type=$type");
    
    // Determine which table to delete from based on type
    $table = '';
    switch ($type) {
        case 'employee':
            $table = 'employees';
            break;
        case 'customer':
            $table = 'customers';
            break;
        case 'supplier':
            $table = 'suppliers';
            break;
        default:
            throw new Exception('Invalid type specified');
    }
    
    error_log("Table selected: $table");

    // Check database connection
    if (!isset($conn) || !($conn instanceof PDO)) {
        error_log("Database connection issue: " . print_r($conn, true));
        throw new Exception('Database connection error');
    }

    // Check if record exists
    try {
        $check_sql = "SELECT id FROM $table WHERE id = :id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        error_log("Record check rows: " . $check_stmt->rowCount());
        
        if ($check_stmt->rowCount() === 0) {
            throw new Exception("Record not found in table $table with ID $id");
        }
    } catch (PDOException $e) {
        error_log("PDO Error during record check: " . $e->getMessage());
        throw new Exception('Database error: ' . $e->getMessage());
    }

    // Delete the record
    try {
        $delete_sql = "DELETE FROM $table WHERE id = :id";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        $result = $delete_stmt->execute();
        error_log("Delete execution result: " . ($result ? 'true' : 'false'));
        
        if (!$result) {
            throw new Exception('Failed to delete record: ' . implode(', ', $delete_stmt->errorInfo()));
        }
    } catch (PDOException $e) {
        error_log("PDO Error during delete: " . $e->getMessage());
        throw new Exception('Database error: ' . $e->getMessage());
    }

    error_log("Delete successful");
    echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
} catch (Exception $e) {
    error_log("Error in delete_employee.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 