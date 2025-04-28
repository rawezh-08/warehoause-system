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
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }

    // Get filter parameters with default values
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $search = !empty($_POST['search']) ? $_POST['search'] : null;
    $recordsPerPage = !empty($_POST['records_per_page']) ? intval($_POST['records_per_page']) : 10;
    $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;

    // Validate dates if provided
    if ($startDate && !strtotime($startDate)) {
        throw new Exception('بەرواری دەستپێک نادروستە');
    }
    if ($endDate && !strtotime($endDate)) {
        throw new Exception('بەرواری کۆتایی نادروستە');
    }

    // Calculate offset
    $offset = ($page - 1) * $recordsPerPage;

    // Build the base query
    $query = "SELECT 
                w.*,
                GROUP_CONCAT(
                    CONCAT(
                        p.name, ' (',
                        wi.quantity, ' ',
                        CASE wi.unit_type 
                            WHEN 'piece' THEN 'دانە'
                            WHEN 'box' THEN 'کارتۆن'
                            WHEN 'set' THEN 'سێت'
                        END,
                        ')'
                    )
                    SEPARATOR ', '
                ) as products_list,
                COALESCE(SUM(wi.total_price), 0) as total_amount
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

    // Get total count for pagination
    $countQuery = str_replace(
        "w.*, GROUP_CONCAT(CONCAT(p.name, ' (', wi.quantity, ' ', CASE wi.unit_type WHEN 'piece' THEN 'دانە' WHEN 'box' THEN 'کارتۆن' WHEN 'set' THEN 'سێت' END, ')') SEPARATOR ', ') as products_list, COALESCE(SUM(wi.total_price), 0) as total_amount",
        "COUNT(DISTINCT w.id) as total",
        $query
    );

    $stmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

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
        'message' => $e->getMessage()
    ]);
} 