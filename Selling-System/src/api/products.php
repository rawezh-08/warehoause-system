<?php
// Include database connection
require_once '../config/db_connection.php';

// Set Content-Type header to JSON
header('Content-Type: application/json');

// Get search term if any
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // Number of results per page
$offset = ($page - 1) * $per_page;

// Initialize response array
$response = [
    'products' => [],
    'total_count' => 0
];

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("set names utf8");

    // Base SQL for products
    $sql = "SELECT 
                p.id, 
                p.name, 
                p.code, 
                p.barcode, 
                p.purchase_price, 
                p.selling_price_single, 
                p.selling_price_wholesale, 
                p.current_quantity,
                p.pieces_per_box,
                p.boxes_per_set,
                p.unit_id,
                c.name AS category_name,
                u.name AS unit_name,
                u.is_piece,
                u.is_box,
                u.is_set
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN units u ON p.unit_id = u.id";

    // Add search condition if search term provided
    $whereClauses = [];
    $params = [];

    if (!empty($search)) {
        $whereClauses[] = "(p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?)";
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
                p.id, 
                p.name, 
                p.code, 
                p.barcode, 
                p.purchase_price, 
                p.selling_price_single, 
                p.selling_price_wholesale, 
                p.current_quantity,
                p.pieces_per_box,
                p.boxes_per_set,
                p.unit_id,
                c.name AS category_name,
                u.name AS unit_name,
                u.is_piece,
                u.is_box,
                u.is_set", "SELECT COUNT(*) as total", $sql);
    
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $i => $param) {
        $countStmt->bindValue($i + 1, $param);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $response['total_count'] = (int)$totalCount;

    // Get paginated results
    $sql .= " ORDER BY p.name ASC LIMIT $offset, $per_page";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    $stmt->execute();
    
    // Fetch products and add to response
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['products'][] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'barcode' => $row['barcode'],
            'purchase_price' => $row['purchase_price'],
            'selling_price_single' => $row['selling_price_single'],
            'selling_price_wholesale' => $row['selling_price_wholesale'],
            'current_quantity' => $row['current_quantity'],
            'pieces_per_box' => $row['pieces_per_box'],
            'boxes_per_set' => $row['boxes_per_set'],
            'category_name' => $row['category_name'],
            'unit_name' => $row['unit_name'],
            'unit_id' => $row['unit_id'],
            'is_piece' => $row['is_piece'] == 1,
            'is_box' => $row['is_box'] == 1,
            'is_set' => $row['is_set'] == 1
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