<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('کێشە هەیە لە پەیوەندی بە داتابەیسەوە');
    }

    // Get filter parameters
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $search = $_POST['search'] ?? null;
    $recordsPerPage = intval($_POST['records_per_page'] ?? 10);
    $page = intval($_POST['page'] ?? 1);
    $offset = ($page - 1) * $recordsPerPage;

    // Build the query
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

    $where = [];
    $params = [];

    // Add filters
    if ($startDate) {
        $where[] = "DATE(w.date) >= ?";
        $params[] = $startDate;
    }
    if ($endDate) {
        $where[] = "DATE(w.date) <= ?";
        $params[] = $endDate;
    }
    if ($search) {
        $where[] = "(p.name LIKE ? OR w.notes LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Add WHERE clause if there are conditions
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    // Group by
    $query .= " GROUP BY w.id";

    // Get total count
    $countQuery = str_replace(
        "w.*, GROUP_CONCAT(CONCAT(p.name, ' (', wi.quantity, ' ', CASE wi.unit_type WHEN 'piece' THEN 'دانە' WHEN 'box' THEN 'کارتۆن' WHEN 'set' THEN 'سێت' END, ')') SEPARATOR ', ') as products_list, COALESCE(SUM(wi.total_price), 0) as total_amount",
        "COUNT(DISTINCT w.id) as total",
        $query
    );

    $stmt = $conn->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Add pagination
    $query .= " ORDER BY w.date DESC LIMIT " . intval($recordsPerPage) . " OFFSET " . intval($offset);

    // Execute main query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $wastings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate pagination info
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $recordsPerPage) : 0;
    $startRecord = $totalRecords > 0 ? $offset + 1 : 0;
    $endRecord = $totalRecords > 0 ? min($offset + $recordsPerPage, $totalRecords) : 0;

    // Return response
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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 