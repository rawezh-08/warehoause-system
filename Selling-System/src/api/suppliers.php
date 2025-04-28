<?php
// Include database connection
require_once '../config/db_connection.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Get search term if any
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = 1; // Client-side pagination will handle this
$per_page = 1000; // Setting a high limit to get all records
$offset = 0;

// Initialize response array
$response = [
    'suppliers' => [],
    'total_count' => 0
];

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8");

    // Base SQL for suppliers
    $sql = "SELECT 
                id, 
                name, 
                phone1, 
                phone2, 
                debt_on_myself,
                notes
            FROM suppliers";

    // Add search condition if search term provided
    $whereClauses = [];
    $params = [];

    if (!empty($search)) {
        $whereClauses[] = "(name LIKE ? OR phone1 LIKE ? OR phone2 LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Add WHERE clause if conditions exist
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    // Count total results
    $countSql = str_replace("SELECT 
                id, 
                name, 
                phone1, 
                phone2, 
                debt_on_myself,
                notes", "SELECT COUNT(*) as total", $sql);
    
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $i => $param) {
        $countStmt->bindValue($i + 1, $param);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $response['total_count'] = (int)$totalCount;

    // Get all results and sort by name
    $sql .= " ORDER BY name ASC";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    $stmt->execute();
    
    // Fetch suppliers and add to response
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['suppliers'][] = [
            'id' => $row['id'],
            'text' => $row['name'], // Using 'text' for select2 compatibility
            'phone1' => $row['phone1'],
            'phone2' => $row['phone2'],
            'debt_on_myself' => $row['debt_on_myself'],
            'notes' => $row['notes']
        ];
    }

} catch(PDOException $e) {
    $response = [
        'error' => true,
        'message' => $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?> 