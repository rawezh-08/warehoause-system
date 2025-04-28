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
    $stmt = $conn->query("SELECT COUNT(*) as total FROM purchases");
    $response['recordsTotal'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $response['recordsFiltered'] = $response['recordsTotal'];
    
    // Build query for filtered results
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    $query = "
        SELECT p.*, s.name as supplier_name
        FROM purchases p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
    ";
    
    // Apply search filter if provided
    if (!empty($search)) {
        $query .= " WHERE (
            p.invoice_number LIKE :search OR
            s.name LIKE :search
        )";
        
        // Get filtered count
        $stmt = $conn->prepare("
            SELECT COUNT(*) as filtered
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.invoice_number LIKE :search OR
                  s.name LIKE :search
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
    
    $columns = ['p.invoice_number', 'p.date', 's.name', 'p.payment_type', 'p.total_amount'];
    $orderBy = $columns[$orderColumn] ?? 'p.date';
    
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
        
        // Calculate total amount
        $totalAmount = $row['total_amount'] ?? 0;
        
        // Actions buttons
        $actions = '
            <div class="btn-group btn-group-sm">
                <a href="supplierProfile.php?id='.$row['supplier_id'].'&tab=purchases" class="btn btn-info" title="نیشاندان">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="../receipt/purchase_receipt.php?id='.$row['id'].'" target="_blank" class="btn btn-primary" title="چاپکردن">
                    <i class="fas fa-print"></i>
                </a>
            </div>
        ';
        
        $response['data'][] = [
            'invoice_number' => $row['invoice_number'],
            'date' => date('Y-m-d', strtotime($row['date'])),
            'supplier_name' => $row['supplier_name'] ?? 'دابینکەری نەناسراو',
            'payment_type' => $paymentType,
            'total_amount' => number_format($totalAmount) . ' دینار',
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