<?php
// Simple version with minimal code and dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');

try {
    // Connect to database using direct PDO to avoid any potential issues with the Database class
    $host = 'localhost'; // Update these credentials as needed
    $dbname = 'warehouse_db';
    $username = 'root';
    $password = '';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Get filter parameters
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : null;
    
    // Simple query without joins or complex logic
    $query = "SELECT * FROM wastings WHERE 1=1";
    $params = [];
    
    if ($startDate) {
        $query .= " AND DATE(date) >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND DATE(date) <= ?";
        $params[] = $endDate;
    }
    
    $query .= " ORDER BY date DESC LIMIT 10";
    
    // Prepare and execute
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $results,
        'query' => $query,
        'params' => $params,
        'count' => count($results)
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} 