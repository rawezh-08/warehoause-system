<?php


// Set error handling for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    error_reporting(0);
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    });
}

try {
    // Define the root path and include database configuration
    require_once dirname(__DIR__) . '/config/database.php';

    // Pagination settings
    $records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;

    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : '';
    $unit_id = isset($_GET['unit']) ? (int)$_GET['unit'] : '';

    // Build base query
    $query = "SELECT p.*, c.name as category_name, u.name as unit_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              LEFT JOIN units u ON p.unit_id = u.id 
              WHERE 1=1";

    $params = array();

    // Add search conditions
    if (!empty($search)) {
        $query .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if (!empty($category_id)) {
        $query .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    if (!empty($unit_id)) {
        $query .= " AND p.unit_id = ?";
        $params[] = $unit_id;
    }

    // Get total count
    $count_query = str_replace("p.*, c.name as category_name, u.name as unit_name", "COUNT(*) as total", $query);
    $stmt = $conn->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Add pagination
    $query .= " ORDER BY p.id DESC LIMIT $offset, $records_per_page";

    // Get products
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return data as JSON if AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode([
            'success' => true,
            'data' => [
                'products' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_records' => $total_records,
                    'records_per_page' => $records_per_page
                ]
            ]
        ]);
        exit;
    }

    // Get categories for filter (only if not AJAX)
    $categories_query = "SELECT id, name FROM categories ORDER BY name";
    $categories = $conn->query($categories_query)->fetchAll(PDO::FETCH_ASSOC);

    // Get units for filter (only if not AJAX)
    $units_query = "SELECT id, name FROM units ORDER BY name";
    $units = $conn->query($units_query)->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode([
            'success' => false,
            'message' => 'کێشەیەک ڕوویدا: ' . $e->getMessage()
        ]);
        exit;
    }
    // If not AJAX, rethrow the exception
    throw $e;
}
?> 