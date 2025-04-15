<?php
// Include database connection
require_once '../config/db_connect.php';

header('Content-Type: application/json');

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, 
        DB_USER, 
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Prepare and execute query to get all employees
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM employees 
        WHERE active = 1 
        ORDER BY name
    ");
    
    $stmt->execute();
    
    // Fetch all employees
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with employees
    echo json_encode([
        'success' => true,
        'employees' => $employees
    ]);
    
} catch (PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا: ' . $e->getMessage()
    ]);
}
?> 