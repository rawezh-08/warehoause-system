<?php
// Include database connection
require_once '../../config/database.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get filter period from request
$filterPeriod = isset($_GET['period']) ? $_GET['period'] : 'today';

// Define date ranges based on filter period
$currentDate = date('Y-m-d');

switch($filterPeriod) {
    case 'today':
        $startDate = $currentDate;
        break;
    case 'month':
        $startDate = date('Y-m-01'); // First day of current month
        break;
    case 'year':
        $startDate = date('Y-01-01'); // First day of current year
        break;
    default:
        $startDate = $currentDate;
}

try {
    // Fetch cash sales
    $cashSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                       FROM sales s 
                       JOIN sale_items si ON s.id = si.sale_id 
                       WHERE s.payment_type = 'cash'
                       AND DATE(s.date) >= :startDate";
    $stmt = $conn->prepare($cashSalesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $cashSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch credit sales
    $creditSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                        FROM sales s 
                        JOIN sale_items si ON s.id = si.sale_id 
                        WHERE s.payment_type = 'credit'
                        AND DATE(s.date) >= :startDate";
    $stmt = $conn->prepare($creditSalesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $creditSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch total purchases
    $purchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                       FROM purchases p 
                       JOIN purchase_items pi ON p.id = pi.purchase_id 
                       WHERE DATE(p.date) >= :startDate";
    $stmt = $conn->prepare($purchasesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $totalPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch total expenses
    $expensesQuery = "SELECT COALESCE(SUM(amount), 0) as total 
                      FROM expenses 
                      WHERE DATE(expense_date) >= :startDate";
    $stmt = $conn->prepare($expensesQuery);
    $stmt->execute(['startDate' => $startDate]);
    $totalExpenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Return data as JSON
    echo json_encode([
        'cashSales' => $cashSales,
        'creditSales' => $creditSales,
        'totalPurchases' => $totalPurchases,
        'totalExpenses' => $totalExpenses
    ]);

} catch (PDOException $e) {
    // Return error message
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 