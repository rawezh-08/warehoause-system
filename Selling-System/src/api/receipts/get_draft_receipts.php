<?php
// Enable error reporting
ini_set('display_errors', 0); // Disable display of errors
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/get_draft_receipts_errors.log');

// Log the start of the script
error_log("Starting get_draft_receipts.php script");

// Set headers for JSON response
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';

    // Log POST data
    error_log("POST data received: " . print_r($_POST, true));

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
    $customer = isset($_POST['customer']) ? $_POST['customer'] : null;
    $search = isset($_POST['search']) ? $_POST['search'] : null;
    $recordsPerPage = isset($_POST['records_per_page']) ? intval($_POST['records_per_page']) : 10;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    
    // Calculate offset
    $offset = ($page - 1) * $recordsPerPage;
    
    // Build the base query
    $query = "SELECT s.*, 
                     c.name as customer_name,
                     COALESCE(SUM(si.total_price), 0) as subtotal,
                     (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount
              FROM sales s
              LEFT JOIN customers c ON s.customer_id = c.id
              LEFT JOIN sale_items si ON s.id = si.sale_id
              WHERE s.is_draft = 1";
    
    $params = [];
    
    // Add filters
    if ($startDate) {
        $query .= " AND DATE(s.date) >= :start_date";
        $params[':start_date'] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND DATE(s.date) <= :end_date";
        $params[':end_date'] = $endDate;
    }
    
    if ($customer) {
        $query .= " AND c.name LIKE :customer";
        $params[':customer'] = "%$customer%";
    }
    
    if ($search) {
        $query .= " AND (s.invoice_number LIKE :search OR c.name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Group by to avoid duplicates
    $query .= " GROUP BY s.id";
    
    // Get total count for pagination
    $countQuery = str_replace("s.*, c.name as customer_name, COALESCE(SUM(si.total_price), 0) as subtotal, (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount", "COUNT(DISTINCT s.id) as total", $query);
    $stmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Add pagination
    $query .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
    
    // Execute the main query
    $stmt = $conn->prepare($query);
    
    // Bind all previous parameters
    $paramIndex = 1;
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind limit and offset parameters
    $stmt->bindValue($paramIndex++, $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $drafts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $startRecord = $offset + 1;
    $endRecord = min($offset + $recordsPerPage, $totalRecords);
    
    // Return the response
    echo json_encode([
        'success' => true,
        'data' => $drafts,
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
    error_log("Error in get_draft_receipts.php: " . $e->getMessage());
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
} catch (Error $e) {
    // Log the error
    error_log("PHP Error in get_draft_receipts.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەکی سیستەمی ڕوویدا',
        'debug_info' => [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString()
        ]
    ]);
} 