<?php


require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Create database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Get filter parameters
    $startDate = $_GET['startDate'] ?? null;
    $endDate = $_GET['endDate'] ?? null;
    $employeeName = $_GET['employeeName'] ?? null;

    // Base query
    $query = "SELECT 
                ep.id,
                e.name as employee_name,
                ep.payment_date,
                ep.amount,
                ep.payment_type,
                ep.notes,
                ep.created_at
              FROM employee_payments ep
              JOIN employees e ON ep.employee_id = e.id
              WHERE 1=1";
    
    $params = [];

    // Add date filters if provided
    if ($startDate && $endDate) {
        $query .= " AND ep.payment_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }

    // Add employee name filter if provided
    if ($employeeName) {
        $query .= " AND e.name LIKE ?";
        $params[] = "%$employeeName%";
    }

    // Add sorting
    $query .= " ORDER BY ep.payment_date DESC, ep.id DESC";

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the payment type in Kurdish
    foreach ($payments as &$payment) {
        switch ($payment['payment_type']) {
            case 'salary':
                $payment['payment_type_kurdish'] = 'مووچە';
                $payment['badge_class'] = 'bg-success';
                break;
            case 'bonus':
                $payment['payment_type_kurdish'] = 'پاداشت';
                $payment['badge_class'] = 'bg-info';
                break;
            case 'overtime':
                $payment['payment_type_kurdish'] = 'ئۆڤەرتایم';
                $payment['badge_class'] = 'bg-warning text-dark';
                break;
            default:
                $payment['payment_type_kurdish'] = $payment['payment_type'];
                $payment['badge_class'] = 'bg-secondary';
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $payments
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'هەڵەیەک ڕوویدا لە کاتی گەڕانەوەی زانیاریەکان: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn = null; // Close connection 