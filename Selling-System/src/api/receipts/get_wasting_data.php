<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/get_wasting_data_errors.log');

// Log the start of the script
error_log("Starting get_wasting_data.php script");

require_once '../../config/database.php';

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
    
    // Get filter parameters
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : null;
    $search = isset($_POST['search']) ? $_POST['search'] : null;
    $recordsPerPage = isset($_POST['records_per_page']) ? intval($_POST['records_per_page']) : 10;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    
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
    
    // Add filters
    if ($startDate) {
        $query .= " WHERE DATE(w.date) >= :start_date";
        $params[':start_date'] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND DATE(w.date) <= :end_date";
        $params[':end_date'] = $endDate;
    }
    
    if ($search) {
        $query .= " AND (p.name LIKE :search OR w.notes LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Group by to avoid duplicates
    $query .= " GROUP BY w.id";
    
    // Get total count for pagination
    $countQuery = str_replace("w.*, GROUP_CONCAT(CONCAT(p.name, ' (', wi.quantity, ' ', CASE wi.unit_type WHEN 'piece' THEN 'دانە' WHEN 'box' THEN 'کارتۆن' WHEN 'set' THEN 'سێت' END, ')') SEPARATOR ', ') as products_list, SUM(wi.total_price) as total_amount", "COUNT(DISTINCT w.id) as total", $query);
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRecords = isset($result['total']) ? intval($result['total']) : 0;
    
    // Add pagination
    $query .= " ORDER BY w.date DESC LIMIT " . intval($recordsPerPage) . " OFFSET " . intval($offset);
    
    // Execute the main query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $wastings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $recordsPerPage) : 0;
    $startRecord = $totalRecords > 0 ? $offset + 1 : 0;
    $endRecord = $totalRecords > 0 ? min($offset + $recordsPerPage, $totalRecords) : 0;
    
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
    // Log the error
    error_log("Error in get_wasting_data.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString()
        ]
    ]);
} 