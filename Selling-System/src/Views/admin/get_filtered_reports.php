<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Get filter parameters
$dateRange = $_POST['dateRange'] ?? '';
$reportType = $_POST['reportType'] ?? 'all';
$category = $_POST['category'] ?? 'all';
$paymentType = $_POST['paymentType'] ?? 'all';
$supplier = $_POST['supplier'] ?? 'all';
$customer = $_POST['customer'] ?? 'all';

// Parse date range
$dates = explode(' - ', $dateRange);
$startDate = $dates[0] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $dates[1] ?? date('Y-m-d');

// Build base query conditions
$conditions = [];
$params = [];

if ($startDate && $endDate) {
    $conditions[] = "s.date BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $startDate;
    $params[':end_date'] = $endDate;
}

if ($category !== 'all') {
    $conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $category;
}

if ($paymentType !== 'all') {
    $conditions[] = "s.payment_type = :payment_type";
    $params[':payment_type'] = $paymentType;
}

if ($supplier !== 'all') {
    $conditions[] = "p.supplier_id = :supplier_id";
    $params[':supplier_id'] = $supplier;
}

if ($customer !== 'all') {
    $conditions[] = "s.customer_id = :customer_id";
    $params[':customer_id'] = $customer;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get Monthly Profit/Loss data
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
    $whereClause
    GROUP BY
        DATE_FORMAT(s.date, '%Y-%m')
    ORDER BY
        month DESC
    LIMIT 12
");
$stmt->execute($params);
$monthlyProfitLoss = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Category Sales data
$stmt = $conn->prepare("
    SELECT
        c.name as category_name,
        COUNT(DISTINCT si.id) as sale_count,
        SUM(si.total_price) as total_sales,
        ROUND(SUM(si.total_price) / (SELECT SUM(total_price) FROM sale_items) * 100, 1) as percentage
    FROM
        categories c
    JOIN
        products p ON c.id = p.category_id
    JOIN
        sale_items si ON p.id = si.product_id
    JOIN
        sales s ON si.sale_id = s.id
    $whereClause
    GROUP BY
        c.id, c.name
    ORDER BY
        total_sales DESC
");
$stmt->execute($params);
$categorySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Sales Forecast data
$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(s.date, '%Y-%m') as month,
        COALESCE(SUM(si.total_price), 0) as monthly_sales
    FROM
        sales s
    JOIN
        sale_items si ON s.id = si.sale_id
    $whereClause
    GROUP BY
        DATE_FORMAT(s.date, '%Y-%m')
    ORDER BY
        month ASC
    LIMIT 3
");
$stmt->execute($params);
$recentMonthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate forecast
$totalRecentSales = 0;
foreach ($recentMonthlySales as $data) {
    $totalRecentSales += $data['monthly_sales'];
}
$averageMonthlySales = count($recentMonthlySales) > 0 ? $totalRecentSales / count($recentMonthlySales) : 0;

// Create 3 month forecast
$salesForecast = [];
$growthRate = 1.05; // 5% growth assumption
$forecastAmount = $averageMonthlySales;

$currentMonth = date('m');
$currentYear = date('Y');

for ($i = 1; $i <= 3; $i++) {
    $forecastMonth = ($currentMonth + $i) > 12 ? ($currentMonth + $i - 12) : ($currentMonth + $i);
    $forecastYear = ($currentMonth + $i) > 12 ? ($currentYear + 1) : $currentYear;
    $forecastAmount = $forecastAmount * $growthRate;
    
    $salesForecast[] = [
        "month" => date('F Y', mktime(0, 0, 0, $forecastMonth, 1, $forecastYear)),
        "forecast" => round($forecastAmount)
    ];
}

// Get Cash Flow data
$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(source_date, '%Y-%m') as month,
        SUM(incoming) as total_incoming,
        SUM(outgoing) as total_outgoing,
        SUM(incoming - outgoing) as net_cash
    FROM (
        SELECT 
            s.date as source_date,
            s.paid_amount as incoming,
            0 as outgoing
        FROM 
            sales s
        WHERE 
            s.payment_type = 'cash'
        
        UNION ALL
        
        SELECT 
            dt.created_at as source_date,
            dt.amount as incoming,
            0 as outgoing
        FROM 
            debt_transactions dt
        WHERE 
            dt.transaction_type = 'payment'
        
        UNION ALL
        
        SELECT 
            p.date as source_date,
            0 as incoming,
            p.paid_amount as outgoing
        FROM 
            purchases p
        WHERE 
            p.payment_type = 'cash'
        
        UNION ALL
        
        SELECT 
            e.expense_date as source_date,
            0 as incoming,
            e.amount as outgoing
        FROM 
            expenses e
        
        UNION ALL
        
        SELECT 
            ep.payment_date as source_date,
            0 as incoming,
            ep.amount as outgoing
        FROM 
            employee_payments ep
    ) as cash_flow
    WHERE
        source_date BETWEEN :start_date AND :end_date
    GROUP BY
        DATE_FORMAT(source_date, '%Y-%m')
    ORDER BY
        month ASC
");
$stmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate
]);
$cashFlowData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Low Stock data
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.name,
        p.code,
        p.image,
        p.current_quantity,
        p.min_quantity,
        c.name as category_name,
        ROUND((p.current_quantity / p.min_quantity) * 100) as stock_percentage
    FROM 
        products p
    JOIN
        categories c ON p.category_id = c.id
    WHERE 
        p.current_quantity <= p.min_quantity * 1.5
    ORDER BY 
        stock_percentage ASC
    LIMIT 15
");
$stmt->execute();
$lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Best Selling Products data
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.name,
        p.code,
        p.image,
        SUM(si.pieces_count) as total_quantity,
        SUM(si.total_price) as total_sales,
        COALESCE(SUM(si.total_price) - SUM(si.pieces_count * p.purchase_price), 0) as total_profit
    FROM 
        sale_items si
    JOIN 
        products p ON si.product_id = p.id
    JOIN
        sales s ON si.sale_id = s.id
    $whereClause
    GROUP BY 
        p.id, p.name, p.code, p.image
    ORDER BY 
        total_sales DESC
    LIMIT 10
");
$stmt->execute($params);
$bestSelling = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Customer Debt data
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.name,
        c.phone1,
        c.debit_on_business as debt_amount,
        DATEDIFF(NOW(), MAX(s.date)) as days_since_last_purchase
    FROM 
        customers c
    LEFT JOIN 
        sales s ON c.id = s.customer_id
    WHERE 
        c.debit_on_business > 0
    GROUP BY 
        c.id, c.name, c.phone1, c.debit_on_business
    ORDER BY 
        c.debit_on_business DESC
    LIMIT 10
");
$stmt->execute();
$customerDebt = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Customer Behavior data
$stmt = $conn->prepare("
    SELECT
        c.id,
        c.name,
        COUNT(DISTINCT s.id) as purchase_count,
        SUM(si.total_price) as total_spent,
        ROUND(AVG(s.remaining_amount), 0) as avg_remaining,
        MAX(s.date) as last_purchase_date,
        DATEDIFF(NOW(), MAX(s.date)) as days_since_last_purchase
    FROM
        customers c
    JOIN
        sales s ON c.id = s.customer_id
    JOIN
        sale_items si ON s.id = si.sale_id
    $whereClause
    GROUP BY
        c.id, c.name
    HAVING
        COUNT(DISTINCT s.id) > 1
    ORDER BY
        total_spent DESC
    LIMIT 10
");
$stmt->execute($params);
$customerBehavior = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare response data
$response = [
    'monthlyProfit' => [
        'revenue' => array_column($monthlyProfitLoss, 'sales_revenue'),
        'expenses' => array_column($monthlyProfitLoss, 'purchase_cost'),
        'profit' => array_map(function($item) {
            return $item['sales_revenue'] - $item['purchase_cost'] - $item['expenses'] - $item['employee_expenses'];
        }, $monthlyProfitLoss)
    ],
    'categorySales' => [
        'categories' => array_column($categorySales, 'category_name'),
        'percentages' => array_column($categorySales, 'percentage')
    ],
    'salesForecast' => [
        'months' => array_column($salesForecast, 'month'),
        'forecast' => array_column($salesForecast, 'forecast')
    ],
    'cashFlow' => [
        'months' => array_column($cashFlowData, 'month'),
        'incoming' => array_column($cashFlowData, 'total_incoming'),
        'outgoing' => array_column($cashFlowData, 'total_outgoing'),
        'net' => array_column($cashFlowData, 'net_cash')
    ],
    'lowStock' => $lowStock,
    'bestSelling' => $bestSelling,
    'customerDebt' => $customerDebt,
    'customerBehavior' => $customerBehavior
];

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 