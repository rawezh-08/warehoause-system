<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Get date range from request
$startDate = $_POST['start_date'] ?? date('Y-m-d');
$endDate = $_POST['end_date'] ?? date('Y-m-d');

// Function to get total count from a table with date filter
function getCountWithDate($table, $dateColumn, $startDate, $endDate) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE $dateColumn BETWEEN :start_date AND :end_date");
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Function to get sum of a column with date filter
function getSumWithDate($table, $column, $dateColumn, $startDate, $endDate) {
    global $conn;
    $stmt = $conn->prepare("SELECT COALESCE(SUM($column), 0) as total FROM $table WHERE $dateColumn BETWEEN :start_date AND :end_date");
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

// Get sales data for the date range
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(si.total_price), 0) as total_sales,
        COALESCE(SUM(CASE WHEN s.payment_type = 'cash' THEN si.total_price ELSE 0 END), 0) as total_cash_sales,
        COALESCE(SUM(CASE WHEN s.payment_type = 'credit' THEN si.total_price ELSE 0 END), 0) as total_credit_sales
    FROM 
        sales s
    JOIN 
        sale_items si ON s.id = si.sale_id
    WHERE 
        s.date BETWEEN :start_date AND :end_date
");
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);
$stmt->execute();
$salesData = $stmt->fetch(PDO::FETCH_ASSOC);

// Get purchases data for the date range
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(pi.total_price), 0) as total_purchases,
        COALESCE(SUM(CASE WHEN p.payment_type = 'cash' THEN pi.total_price ELSE 0 END), 0) as total_cash_purchases,
        COALESCE(SUM(CASE WHEN p.payment_type = 'credit' THEN pi.total_price ELSE 0 END), 0) as total_credit_purchases
    FROM 
        purchases p
    JOIN 
        purchase_items pi ON p.id = pi.purchase_id
    WHERE 
        p.date BETWEEN :start_date AND :end_date
");
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);
$stmt->execute();
$purchasesData = $stmt->fetch(PDO::FETCH_ASSOC);

// Get expenses for the date range
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_expenses 
    FROM 
        expenses 
    WHERE 
        expense_date BETWEEN :start_date AND :end_date
");
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);
$stmt->execute();
$expensesData = $stmt->fetch(PDO::FETCH_ASSOC);

// Get employee expenses for the date range
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_employee_expenses 
    FROM 
        employee_payments 
    WHERE 
        payment_date BETWEEN :start_date AND :end_date
");
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);
$stmt->execute();
$employeeExpensesData = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate net profit
$netProfit = $salesData['total_sales'] - $purchasesData['total_purchases'] - $expensesData['total_expenses'] - $employeeExpensesData['total_employee_expenses'];

// Calculate available cash
$availableCash = $salesData['total_cash_sales'] - $purchasesData['total_cash_purchases'] - $expensesData['total_expenses'] - $employeeExpensesData['total_employee_expenses'];

// Get monthly profit data for the date range
$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(s.date, '%Y-%m') as month,
        COALESCE(SUM(si.total_price), 0) as sales_revenue,
        (
            SELECT COALESCE(SUM(pi.total_price), 0)
            FROM purchases pu
            JOIN purchase_items pi ON pu.id = pi.purchase_id
            WHERE DATE_FORMAT(pu.date, '%Y-%m') = DATE_FORMAT(s.date, '%Y-%m')
        ) as purchase_cost,
        (
            SELECT COALESCE(SUM(amount), 0)
            FROM expenses
            WHERE DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(s.date, '%Y-%m')
        ) as expenses,
        (
            SELECT COALESCE(SUM(amount), 0)
            FROM employee_payments
            WHERE DATE_FORMAT(payment_date, '%Y-%m') = DATE_FORMAT(s.date, '%Y-%m')
        ) as employee_expenses
    FROM
        sales s
    JOIN
        sale_items si ON s.id = si.sale_id
    WHERE
        s.date BETWEEN :start_date AND :end_date
    GROUP BY
        DATE_FORMAT(s.date, '%Y-%m')
    ORDER BY
        month ASC
");
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);
$stmt->execute();
$monthlyProfitData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category sales data for the date range
$stmt = $conn->prepare("
    SELECT
        c.name as category_name,
        COUNT(DISTINCT si.id) as sale_count,
        SUM(si.total_price) as total_sales,
        ROUND(SUM(si.total_price) / (SELECT SUM(total_price) FROM sale_items si2 JOIN sales s2 ON si2.sale_id = s2.id WHERE s2.date BETWEEN :start_date AND :end_date) * 100, 1) as percentage
    FROM
        categories c
    JOIN
        products p ON c.id = p.category_id
    JOIN
        sale_items si ON p.id = si.product_id
    JOIN
        sales s ON si.sale_id = s.id
    WHERE
        s.date BETWEEN :start_date AND :end_date
    GROUP BY
        c.id, c.name
    ORDER BY
        total_sales DESC
");
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);
$stmt->execute();
$categorySalesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare response data
$response = [
    'total_products' => getCountWithDate('products', 'created_at', $startDate, $endDate),
    'total_inventory_value' => getSumWithDate('products', 'current_quantity * purchase_price', 'created_at', $startDate, $endDate),
    'total_sales' => $salesData['total_sales'],
    'total_cash_sales' => $salesData['total_cash_sales'],
    'total_credit_sales' => $salesData['total_credit_sales'],
    'total_purchases' => $purchasesData['total_purchases'],
    'total_cash_purchases' => $purchasesData['total_cash_purchases'],
    'total_credit_purchases' => $purchasesData['total_credit_purchases'],
    'net_profit' => $netProfit,
    'available_cash' => $availableCash,
    'monthly_profit' => [
        'revenue' => array_column($monthlyProfitData, 'sales_revenue'),
        'expenses' => array_map(function($item) {
            return $item['purchase_cost'] + $item['expenses'] + $item['employee_expenses'];
        }, $monthlyProfitData),
        'profit' => array_map(function($item) {
            return $item['sales_revenue'] - $item['purchase_cost'] - $item['expenses'] - $item['employee_expenses'];
        }, $monthlyProfitData)
    ],
    'category_sales' => [
        'categories' => array_column($categorySalesData, 'category_name'),
        'percentages' => array_column($categorySalesData, 'percentage')
    ]
];

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 