<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/filter_wastings_errors.log');

// Log the start of the script
error_log("Starting filter_wastings.php script");

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Log POST data
error_log("POST data received: " . print_r($_POST, true));

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        error_log("Failed to establish database connection");
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }
    
    error_log("Database connection established successfully");

    // Get filter parameters with default values
    $startDate = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $search = isset($_POST['search']) && !empty($_POST['search']) ? $_POST['search'] : null;
    $recordsPerPage = isset($_POST['records_per_page']) ? intval($_POST['records_per_page']) : 10;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;

    // Log the processed parameters
    error_log("Processed parameters: " . json_encode([
        'start_date' => $startDate,
        'end_date' => $endDate,
        'search' => $search,
        'records_per_page' => $recordsPerPage,
        'page' => $page
    ]));

    // Calculate offset
    $offset = ($page - 1) * $recordsPerPage;

    // Build the base query
    $query = "SELECT w.*, 
                     GROUP_CONCAT(
                         CONCAT(p.name, ' (', wi.quantity, ' ', 
                         CASE wi.unit_type 
                             WHEN 'piece' THEN 'دانە'
                             WHEN 'box' THEN 'کارتۆن'
                             WHEN 'set' THEN 'سێت'
                         END, ')')
                     SEPARATOR ', ') as products_list,
                     SUM(wi.total_price) as total_amount
              FROM wastings w
              LEFT JOIN wasting_items wi ON w.id = wi.wasting_id
              LEFT JOIN products p ON wi.product_id = p.id";

    $params = [];
    $whereConditions = [];

    // Add date filters
    if ($startDate) {
        $whereConditions[] = "DATE(w.date) >= :start_date";
        $params[':start_date'] = $startDate;
    }

    if ($endDate) {
        $whereConditions[] = "DATE(w.date) <= :end_date";
        $params[':end_date'] = $endDate;
    }

    // Add search filter
    if ($search) {
        $whereConditions[] = "(p.name LIKE :search OR w.notes LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Add WHERE clause if there are conditions
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }

    // Group by to avoid duplicates
    $query .= " GROUP BY w.id";

    // Log the final query
    error_log("Final query: " . $query);
    error_log("Query parameters: " . json_encode($params));

    // Get total count for pagination
    $countQuery = str_replace("w.*, GROUP_CONCAT(CONCAT(p.name, ' (', wi.quantity, ' ', CASE wi.unit_type WHEN 'piece' THEN 'دانە' WHEN 'box' THEN 'کارتۆن' WHEN 'set' THEN 'سێت' END, ')') SEPARATOR ', ') as products_list, SUM(wi.total_price) as total_amount", "COUNT(DISTINCT w.id) as total", $query);
    $stmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRecords = isset($result['total']) ? intval($result['total']) : 0;

    // Add pagination
    $query .= " ORDER BY w.date DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $recordsPerPage;
    $params[':offset'] = $offset;

    // Execute the main query
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $wastings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate pagination info
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $recordsPerPage) : 0;
    $startRecord = $totalRecords > 0 ? $offset + 1 : 0;
    $endRecord = $totalRecords > 0 ? min($offset + $recordsPerPage, $totalRecords) : 0;

    // Log the results
    error_log("Query results: " . json_encode([
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'records_found' => count($wastings)
    ]));

    // Return the response
    echo json_encode([
        'success' => true,
        'data' => $wastings,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'start_record' => $startRecord,
            'end_record' => $endRecord
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in filter_wastings.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
} 