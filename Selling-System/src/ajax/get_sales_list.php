<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Initialize response
    $response = [
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ];
    
    // Get total records count
    $stmt = $conn->query("SELECT COUNT(*) as total FROM sales WHERE is_draft = 0");
    $response['recordsTotal'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $response['recordsFiltered'] = $response['recordsTotal'];
    
    // Build query for filtered results
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    $query = "
        SELECT s.*, c.name as customer_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.is_draft = 0
    ";
    
    // Apply search filter if provided
    if (!empty($search)) {
        $query .= " AND (
            s.invoice_number LIKE :search OR
            c.name LIKE :search
        )";
        
        // Get filtered count
        $stmt = $conn->prepare("
            SELECT COUNT(*) as filtered
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.is_draft = 0
            AND (
                s.invoice_number LIKE :search OR
                c.name LIKE :search
            )
        ");
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();
        $response['recordsFiltered'] = $stmt->fetch(PDO::FETCH_ASSOC)['filtered'];
    }
    
    // Apply order
    $orderColumn = 0;
    $orderDir = 'DESC';
    
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        $orderColumn = intval($_POST['order'][0]['column']);
        $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    }
    
    $columns = ['s.invoice_number', 's.date', 'c.name', 's.payment_type', 's.total_amount'];
    $orderBy = $columns[$orderColumn] ?? 's.date';
    
    $query .= " ORDER BY $orderBy $orderDir LIMIT :start, :limit";
    
    // Execute query
    $stmt = $conn->prepare($query);
    
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam);
    }
    
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    // Format data for DataTables
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format payment type
        $paymentType = $row['payment_type'] === 'cash' ? 'نەقد' : 'قەرز';
        
        // Actions buttons
        $actions = '
            <div class="btn-group btn-group-sm">
                <a href="saleDetails.php?id='.$row['id'].'" class="btn btn-info" title="نیشاندان">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="../receipt/sale_receipt.php?id='.$row['id'].'" target="_blank" class="btn btn-primary" title="چاپکردن">
                    <i class="fas fa-print"></i>
                </a>
            </div>
        ';
        
        $response['data'][] = [
            'invoice_number' => $row['invoice_number'],
            'date' => date('Y-m-d', strtotime($row['date'])),
            'customer_name' => $row['customer_name'] ?? 'کڕیاری ئاسایی',
            'payment_type' => $paymentType,
            'total_amount' => number_format($row['total_amount']) . ' دینار',
            'actions' => $actions
        ];
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
} 