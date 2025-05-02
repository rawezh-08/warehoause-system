<!-- report test -->

<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Temporarily disable ONLY_FULL_GROUP_BY to fix SQL errors
$conn->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

// Get date filter from request (default to all-time if not specified)
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';

// Prepare date condition based on filter
$dateCondition = '';
switch ($dateFilter) {
    case 'today':
        $dateCondition = "WHERE date = CURDATE()";
        $expenseCondition = "WHERE expense_date = CURDATE()";
        $employeePaymentCondition = "WHERE payment_date = CURDATE()";
        break;
    case 'this_week':
        $dateCondition = "WHERE YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
        $expenseCondition = "WHERE YEARWEEK(expense_date, 1) = YEARWEEK(CURDATE(), 1)";
        $employeePaymentCondition = "WHERE YEARWEEK(payment_date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'this_month':
        $dateCondition = "WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())";
        $expenseCondition = "WHERE YEAR(expense_date) = YEAR(CURDATE()) AND MONTH(expense_date) = MONTH(CURDATE())";
        $employeePaymentCondition = "WHERE YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE())";
        break;
    case 'this_year':
        $dateCondition = "WHERE YEAR(date) = YEAR(CURDATE())";
        $expenseCondition = "WHERE YEAR(expense_date) = YEAR(CURDATE())";
        $employeePaymentCondition = "WHERE YEAR(payment_date) = YEAR(CURDATE())";
        break;
    default:
        $dateCondition = '';
        $expenseCondition = '';
        $employeePaymentCondition = '';
}

// Function to get total count from a table
function getCount($table)
{
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Function to get sum of a column
function getSum($table, $column)
{
    global $conn;
    $stmt = $conn->prepare("SELECT COALESCE(SUM($column), 0) as total FROM $table");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

// Get product, category, and supplier counts
$totalProducts = getCount('products');
$totalCategories = getCount('categories');
$totalSuppliers = getCount('suppliers');

// Get sales data - Using correct column names from the database
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(si.total_price), 0) as total_sales 
    FROM 
        sales s
    JOIN 
        sale_items si ON s.id = si.sale_id
    WHERE 
        s.is_draft = 0
        " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : '') . "
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalSales = $result['total_sales'];

// Get cash and credit sales
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(si.total_price), 0) as total_cash_sales 
    FROM 
        sales s
    JOIN 
        sale_items si ON s.id = si.sale_id
    WHERE 
        s.payment_type = 'cash'
        AND s.is_draft = 0
    " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : '')
);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCashSales = $result['total_cash_sales'];

$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(si.total_price), 0) as total_credit_sales 
    FROM 
        sales s
    JOIN 
        sale_items si ON s.id = si.sale_id
    WHERE 
        s.payment_type = 'credit'
        AND s.is_draft = 0
    " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : '')
);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCreditSales = $result['total_credit_sales'];

// Get purchases data
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(pi.total_price), 0) as total_purchases 
    FROM 
        purchases p
    JOIN 
        purchase_items pi ON p.id = pi.purchase_id
    " . $dateCondition . "
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalPurchases = $result['total_purchases'];

// Get cash and credit purchases
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(pi.total_price), 0) as total_cash_purchases 
    FROM 
        purchases p
    JOIN 
        purchase_items pi ON p.id = pi.purchase_id
    WHERE 
        p.payment_type = 'cash'
    " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : '')
);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCashPurchases = $result['total_cash_purchases'];

$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(pi.total_price), 0) as total_credit_purchases 
    FROM 
        purchases p
    JOIN 
        purchase_items pi ON p.id = pi.purchase_id
    WHERE 
        p.payment_type = 'credit'
    " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : '')
);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCreditPurchases = $result['total_credit_purchases'];

// Calculate discounts, expenses, and other financial data
$stmt = $conn->prepare("SELECT COALESCE(SUM(discount), 0) as total_sale_discounts FROM sales WHERE is_draft = 0 " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : ''));
$stmt->execute();
$saleDiscounts = $stmt->fetch(PDO::FETCH_ASSOC)['total_sale_discounts'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(discount), 0) as total_purchase_discounts FROM purchases " . $dateCondition);
$stmt->execute();
$purchaseDiscounts = $stmt->fetch(PDO::FETCH_ASSOC)['total_purchase_discounts'];

$totalDiscounts = $saleDiscounts + $purchaseDiscounts;

// Get employee expenses
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_employee_expenses FROM employee_payments " . $employeePaymentCondition);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$employeeExpenses = $result['total_employee_expenses'];

// Get warehouse expenses (from expenses table)
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_warehouse_expenses FROM expenses " . $expenseCondition);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$warehouseExpenses = $result['total_warehouse_expenses'];

// Get warehouse losses - Assuming there's no separate category in expenses table
$warehouseLosses = 0;

// Calculate the cost of goods sold
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(si.pieces_count * p.purchase_price), 0) as total_cost_of_goods_sold
    FROM 
        sale_items si
    JOIN 
        products p ON si.product_id = p.id
    JOIN 
        sales s ON si.sale_id = s.id
    WHERE 
        s.is_draft = 0
        " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : '') . "
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$costOfGoodsSold = $result['total_cost_of_goods_sold'];

// Calculate gross profit (Sales - Cost of Goods Sold)
$grossProfit = $totalSales - $costOfGoodsSold;

// Calculate net profit (Gross Profit - Expenses)
$netProfit = $grossProfit - $warehouseExpenses - $employeeExpenses - $warehouseLosses;

// Calculate available cash (from sales minus expenses and purchases)
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_cash 
    FROM cash_management
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$availableCash = $result['total_cash'] ?? 0;

// Add cash from sales
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(si.total_price), 0) as total_cash_sales 
    FROM 
        sales s
    JOIN 
        sale_items si ON s.id = si.sale_id
    WHERE 
        s.payment_type = 'cash'
    " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : '')
);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$availableCash += $result['total_cash_sales'];

// Subtract cash purchases
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(pi.total_price), 0) as total_cash_purchases 
    FROM 
        purchases p
    JOIN 
        purchase_items pi ON p.id = pi.purchase_id
    WHERE 
        p.payment_type = 'cash'
    " . ($dateCondition ? ' AND ' . substr($dateCondition, 6) : '')
);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$availableCash -= $result['total_cash_purchases'];

// Subtract expenses
$availableCash -= $warehouseExpenses;
$availableCash -= $employeeExpenses;

// Get monthly sales data for the past 6 months
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(s.date, '%Y-%m') as month,
        COALESCE(SUM(si.total_price), 0) as sales
    FROM 
        sales s
    JOIN
        sale_items si ON s.id = si.sale_id
    WHERE 
        s.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        AND s.is_draft = 0
    GROUP BY 
        DATE_FORMAT(s.date, '%Y-%m')
    ORDER BY 
        month ASC
    LIMIT 6
");
$stmt->execute();
$monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format monthly data with Kurdish month names
$monthlySales = [];
$kurdishMonths = [
    '01' => 'بەفرانبار',
    '02' => 'ڕەشەمێ',
    '03' => 'نەورۆز',
    '04' => 'گوڵان',
    '05' => 'جۆزەردان',
    '06' => 'پووشپەڕ',
    '07' => 'گەلاوێژ',
    '08' => 'خەرمانان',
    '09' => 'ڕەزبەر',
    '10' => 'گەڵاڕێزان',
    '11' => 'سەرماوەز',
    '12' => 'بەفرانبار'
];

foreach ($monthlyData as $data) {
    $monthNum = substr($data['month'], -2);
    $monthlySales[] = [
        "month" => $kurdishMonths[$monthNum],
        "sales" => $data['sales']
    ];
}

// Get top selling products
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.name,
        SUM(si.pieces_count) as quantity,
        SUM(si.total_price) as amount
    FROM 
        sale_items si
    JOIN 
        products p ON si.product_id = p.id
    JOIN 
        sales s ON si.sale_id = s.id
    WHERE 
        s.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        AND s.is_draft = 0
    GROUP BY 
        p.id, p.name
    ORDER BY 
        amount DESC
    LIMIT 5
");
$stmt->execute();
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock products
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.name,
        p.current_quantity as current,
        p.min_quantity as min
    FROM 
        products p
    WHERE
        p.current_quantity <= p.min_quantity * 1.2
    ORDER BY 
        (p.current_quantity / p.min_quantity) ASC
    LIMIT 5
");
$stmt->execute();
$lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent transactions
$stmt = $conn->prepare("
    (SELECT 
        s.date as date,
        'فرۆشتن' as type,
        (SELECT SUM(si.total_price) FROM sale_items si WHERE si.sale_id = s.id) as amount,
        'سەرکەوتوو' as status
    FROM 
        sales s
    WHERE
        s.is_draft = 0
    ORDER BY 
        s.date DESC
    LIMIT 3)
    
    UNION
    
    (SELECT 
        p.date as date,
        'کڕین' as type,
        (SELECT SUM(pi.total_price) FROM purchase_items pi WHERE pi.purchase_id = p.id) as amount,
        'سەرکەوتوو' as status
    FROM 
        purchases p
    ORDER BY 
        p.date DESC
    LIMIT 2)
    
    ORDER BY 
        date DESC
    LIMIT 5
");
$stmt->execute();
$recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total products sold
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(pieces_count), 0) as total 
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    WHERE s.is_draft = 0
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalProductsSold = $result['total'];

// Get supplier debt information - debt we owe to suppliers
$stmt = $conn->prepare("SELECT COALESCE(SUM(debt_on_myself), 0) as total_debt_to_suppliers FROM suppliers");
$stmt->execute();
$totalDebtToSuppliers = $stmt->fetch(PDO::FETCH_ASSOC)['total_debt_to_suppliers'];

// Get supplier debt information - debt suppliers owe to us
$stmt = $conn->prepare("SELECT COALESCE(SUM(debt_on_supplier), 0) as total_debt_from_suppliers FROM suppliers");
$stmt->execute();
$totalDebtFromSuppliers = $stmt->fetch(PDO::FETCH_ASSOC)['total_debt_from_suppliers'];

// Calculate the total value of inventory in the warehouse
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(
            CASE 
                WHEN p.pieces_per_box > 0 THEN (p.current_quantity / p.pieces_per_box) * p.purchase_price
                ELSE p.current_quantity * p.purchase_price
            END
        ), 0) as total_inventory_value 
    FROM 
        products p
");
$stmt->execute();
$totalInventoryValue = $stmt->fetch(PDO::FETCH_ASSOC)['total_inventory_value'];

// 1. Best selling products (more detailed)
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
    WHERE
        s.is_draft = 0
    GROUP BY 
        p.id, p.name, p.code, p.image
    ORDER BY 
        total_sales DESC
    LIMIT 10
");
$stmt->execute();
$bestSellingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Customer debt analysis
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.name,
        c.phone1,
        c.debit_on_business as debt_amount,
        DATEDIFF(NOW(), MAX(s.date)) as days_since_last_purchase,
        COUNT(DISTINCT s.id) as purchase_count,
        SUM(si.total_price) as total_purchases
    FROM 
        customers c
    LEFT JOIN 
        sales s ON c.id = s.customer_id AND s.is_draft = 0
    LEFT JOIN 
        sale_items si ON s.id = si.sale_id
    WHERE 
        c.debit_on_business > 0
    GROUP BY 
        c.id, c.name, c.phone1, c.debit_on_business
    ORDER BY 
        c.debit_on_business DESC
    LIMIT 10
");
$stmt->execute();
$topDebtCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total customer debt
$stmt = $conn->prepare("SELECT COALESCE(SUM(debit_on_business), 0) as total_customer_debt FROM customers");
$stmt->execute();
$totalCustomerDebt = $stmt->fetch(PDO::FETCH_ASSOC)['total_customer_debt'];

// 3. Low stock alerts (more detailed)
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
$lowStockAlert = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count of products below minimum quantity
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM products
    WHERE current_quantity < min_quantity
");
$stmt->execute();
$criticalStockCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// 4. Profit/Loss analysis by month
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
        s.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        AND s.is_draft = 0
    GROUP BY
        DATE_FORMAT(s.date, '%Y-%m')
    ORDER BY
        month DESC
    LIMIT 12
");
$stmt->execute();
$monthlyProfitLoss = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate monthly profit and format with Kurdish month names
$monthlyProfitData = [];
foreach ($monthlyProfitLoss as $data) {
    $monthNum = substr($data['month'], -2);
    $year = substr($data['month'], 0, 4);
    $profit = $data['sales_revenue'] - $data['purchase_cost'] - $data['expenses'] - $data['employee_expenses'];

    $monthlyProfitData[] = [
        "month" => $kurdishMonths[$monthNum] . ' ' . $year,
        "revenue" => $data['sales_revenue'],
        "expenses" => $data['purchase_cost'] + $data['expenses'] + $data['employee_expenses'],
        "profit" => $profit
    ];
}

// 5. Category sales analysis
$stmt = $conn->prepare("
    SELECT
        c.id,
        c.name as category_name,
        COUNT(DISTINCT si.id) as sale_count,
        SUM(si.total_price) as total_sales,
        ROUND(SUM(si.total_price) / (
            SELECT SUM(total_price) 
            FROM sale_items si2
            JOIN sales s2 ON si2.sale_id = s2.id
            WHERE s2.is_draft = 0
        ) * 100, 1) as percentage
    FROM
        categories c
    JOIN
        products p ON c.id = p.category_id
    JOIN
        sale_items si ON p.id = si.product_id
    JOIN
        sales s ON si.sale_id = s.id
    WHERE
        s.is_draft = 0
    GROUP BY
        c.id, c.name
    ORDER BY
        total_sales DESC
");
$stmt->execute();
$categorySalesAnalysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Sales forecast (simple 3-month projection based on previous 3 months)
$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(s.date, '%Y-%m') as month,
        COALESCE(SUM(si.total_price), 0) as monthly_sales
    FROM
        sales s
    JOIN
        sale_items si ON s.id = si.sale_id
    WHERE
        s.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        AND s.is_draft = 0
    GROUP BY
        DATE_FORMAT(s.date, '%Y-%m')
    ORDER BY
        month ASC
");
$stmt->execute();
$recentMonthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average monthly sales for forecast
$totalRecentSales = 0;
foreach ($recentMonthlySales as $data) {
    $totalRecentSales += $data['monthly_sales'];
}
$averageMonthlySales = count($recentMonthlySales) > 0 ? $totalRecentSales / count($recentMonthlySales) : 0;

// Create 3 month forecast with growth rate
$salesForecast = [];
$growthRate = 1.05; // 5% growth assumption
$forecastAmount = $averageMonthlySales;

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

for ($i = 1; $i <= 3; $i++) {
    // Calculate forecast month
    $forecastMonth = ($currentMonth + $i) > 12 ? ($currentMonth + $i - 12) : ($currentMonth + $i);
    $forecastYear = ($currentMonth + $i) > 12 ? ($currentYear + 1) : $currentYear;

    // Apply growth rate
    $forecastAmount = $forecastAmount * $growthRate;

    // Add to forecast array
    $salesForecast[] = [
        "month" => $kurdishMonths[sprintf("%02d", $forecastMonth)] . ' ' . $forecastYear,
        "forecast" => round($forecastAmount)
    ];
}

// 7. Customer purchase behavior analysis
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
    WHERE
        s.is_draft = 0
    GROUP BY
        c.id, c.name
    HAVING
        COUNT(DISTINCT s.id) > 1
    ORDER BY
        total_spent DESC
    LIMIT 10
");
$stmt->execute();
$customerBehaviorAnalysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current cash balance
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_cash 
    FROM cash_management
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCash = $result['total_cash'] ?? 0;

// Get cash flow data
$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(source_date, '%Y-%m') as month,
        SUM(incoming) as total_incoming,
        SUM(outgoing) as total_outgoing,
        SUM(incoming - outgoing) as net_cash
    FROM (
        -- Cash inflow from cash management
        SELECT 
            created_at as source_date,
            CASE 
                WHEN transaction_type IN ('initial_balance', 'deposit') THEN amount
                ELSE 0
            END as incoming,
            CASE 
                WHEN transaction_type IN ('withdrawal') THEN ABS(amount)
                ELSE 0
            END as outgoing
        FROM 
            cash_management
        
        UNION ALL
        
        -- Cash inflow from cash sales
        SELECT 
            s.date as source_date,
            s.paid_amount as incoming,
            0 as outgoing
        FROM 
            sales s
        WHERE 
            s.payment_type = 'cash'
        
        UNION ALL
        
        -- Cash inflow from debt payments
        SELECT 
            dt.created_at as source_date,
            dt.amount as incoming,
            0 as outgoing
        FROM 
            debt_transactions dt
        WHERE 
            dt.transaction_type = 'payment'
        
        UNION ALL
        
        -- Cash outflow from purchases
        SELECT 
            p.date as source_date,
            0 as incoming,
            p.paid_amount as outgoing
        FROM 
            purchases p
        WHERE 
            p.payment_type = 'cash'
        
        UNION ALL
        
        -- Cash outflow from expenses
        SELECT 
            e.expense_date as source_date,
            0 as incoming,
            e.amount as outgoing
        FROM 
            expenses e
        
        UNION ALL
        
        -- Cash outflow from employee payments
        SELECT 
            ep.payment_date as source_date,
            0 as incoming,
            ep.amount as outgoing
        FROM 
            employee_payments ep
    ) as cash_flow
    WHERE
        source_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY
        DATE_FORMAT(source_date, '%Y-%m')
    ORDER BY
        month ASC
");
$stmt->execute();
$cashFlowData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format cash flow data with Kurdish month names
$formattedCashFlow = [];
foreach ($cashFlowData as $data) {
    $monthNum = substr($data['month'], -2);
    $year = substr($data['month'], 0, 4);

    $formattedCashFlow[] = [
        "month" => $kurdishMonths[$monthNum] . ' ' . $year,
        "incoming" => $data['total_incoming'],
        "outgoing" => $data['total_outgoing'],
        "net" => $data['net_cash']
    ];
}

// 9. Top debtors
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.name,
        c.phone1,
        c.debit_on_business as debt_amount,
        DATEDIFF(NOW(), MAX(s.date)) as days_since_last_purchase,
        COUNT(DISTINCT s.id) as purchase_count,
        SUM(si.total_price) as total_purchases
    FROM 
        customers c
    LEFT JOIN 
        sales s ON c.id = s.customer_id
    LEFT JOIN 
        sale_items si ON s.id = si.sale_id
    WHERE 
        c.debit_on_business > 0
    GROUP BY 
        c.id, c.name, c.phone1, c.debit_on_business
    ORDER BY 
        c.debit_on_business DESC
    LIMIT 10
");
$stmt->execute();
$topDebtors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆرتەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.40.0/dist/apexcharts.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- DateRangePicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/reports.css">

    <link rel="stylesheet" href="../../test/main.css">
 
    <style>
        .chart-container {
        background: #fff;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
        height: auto;
        min-height: 450px;
        display: flex;
        flex-direction: column;
    }
        .date-filter-btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            margin: 0 2px;
            min-width: 110px;
            text-align: center;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            background: none;
            color: #495057;
            font-weight: 500;
            border: none;
            position: relative;
        }
        
        .date-filter-btn i {
            margin-left: 8px;
            font-size: 0.9rem;
        }
        
        .date-filter-btn.active {
            color: #4361ee;
            background: none;
            border: none;
        }

        .date-filter-btn.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #4361ee;
        }
        
        .date-filter-btn:hover:not(.active) {
            background: none;
            color: #4361ee;
        }
        
        .filter-container {
            background-color: white;
            padding: 16px 20px;
            margin-bottom: 24px;
            border: none;
        }

        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .filter-title {
            color: #495057;
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }

        .filter-title i {
            color: #4361ee;
            margin-left: 8px;
        }

        @media (max-width: 768px) {
            .filter-container .d-flex {
                flex-direction: column;
                gap: 16px;
            }
            
            .btn-group {
                width: 100%;
                justify-content: center;
            }
            
            .date-filter-btn {
                flex: 1;
                min-width: auto;
            }

            .filter-title {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div>
        <!-- Navbar container - populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main Content Wrapper -->
        <div id="content" class="content-wrapper">
            <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-12">
                            <h3 class="page-title mb-0">ڕاپۆرتەکان</h3>
                            <p class="text-muted mb-0">ڕاپۆرتی هەموو چالاکییەکانی کۆگا</p>
                        </div>
                    </div>

                    <!-- Date Filter Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="filter-container">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <h5 class="filter-title"><i class="fas fa-filter"></i> فلتەر بە پێی بەروار</h5>
                                    <div class="btn-group">
                                        <a href="?date_filter=today" class="btn date-filter-btn <?php echo $dateFilter == 'today' ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-day"></i> ئەمڕۆ
                                        </a>
                                        <a href="?date_filter=this_week" class="btn date-filter-btn <?php echo $dateFilter == 'this_week' ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-week"></i> ئەم هەفتە
                                        </a>
                                        <a href="?date_filter=this_month" class="btn date-filter-btn <?php echo $dateFilter == 'this_month' ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-alt"></i> ئەم مانگە
                                        </a>
                                        <a href="?date_filter=this_year" class="btn date-filter-btn <?php echo $dateFilter == 'this_year' ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar"></i> ئەم ساڵ
                                        </a>
                                        <a href="?date_filter=all" class="btn date-filter-btn <?php echo $dateFilter == 'all' || $dateFilter == '' ? 'active' : ''; ?>">
                                            <i class="fas fa-infinity"></i> هەموو کات
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <!-- Products Count -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="report-icon-wrapper">
                                        <h3 class="report-title">کۆی کاڵاکان</h3>
                                        <div class="stat-icon bg-primary-light">
                                            <i class="fas fa-box text-primary"></i>
                                        </div>
                                    </div>
                                    <h3 class="stat-value"><?php echo number_format($totalProducts); ?></h3>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 12.5%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Warehouse Value -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="report-icon-wrapper">
                                        <h3 class="report-title">بەهای کۆگا</h3>
                                        <div class="stat-icon bg-warning-light">
                                            <i class="fas fa-warehouse text-warning"></i>
                                        </div>
                                    </div>
                                    <h3 class="stat-value"><?php echo number_format($totalInventoryValue); ?> د.ع</h3>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 9.3%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Sales -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-success-light">
                                            <i class="fas fa-dollar-sign text-success"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="receiptList.php">بینینی پسوڵەکان</a></li>
                                                <li><a class="dropdown-item" href="#">زیاتر</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">کۆی فرۆشتن</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalSales); ?> د.ع</h3>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="text-success">
                                            <i class="fas fa-money-bill-wave"></i> نەقد
                                            <div class="fw-bold"><?php echo number_format($totalCashSales); ?> د.ع</div>
                                        </div>
                                        <div class="text-primary">
                                            <i class="fas fa-credit-card"></i> قەرز
                                            <div class="fw-bold"><?php echo number_format($totalCreditSales); ?> د.ع</div>
                                        </div>
                                    </div>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 8.3%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Purchases -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-warning-light">
                                            <i class="fas fa-shopping-cart text-warning"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">بینینی کڕینەکان</a></li>
                                                <li><a class="dropdown-item" href="#">زیاتر</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">کۆی کڕین</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalPurchases); ?> د.ع</h3>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="text-warning">
                                            <i class="fas fa-money-bill-wave"></i> نەقد
                                            <div class="fw-bold"><?php echo number_format($totalCashPurchases); ?> د.ع</div>
                                        </div>
                                        <div class="text-primary">
                                            <i class="fas fa-credit-card"></i> قەرز
                                            <div class="fw-bold"><?php echo number_format($totalCreditPurchases); ?> د.ع</div>
                                        </div>
                                    </div>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 5.7%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Net Profit -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card <?php echo $netProfit >= 0 ? 'bg-success-light' : 'bg-danger-light'; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon <?php echo $netProfit >= 0 ? 'bg-success-light' : 'bg-danger-light'; ?>">
                                            <i class="fas fa-chart-line <?php echo $netProfit >= 0 ? 'text-success' : 'text-danger'; ?>"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">بینینی ڕاپۆرتی قازانج</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">قازانجی خاوێن</h6>
                                    <h3 class="stat-value"><?php echo number_format($netProfit); ?> د.ع</h3>
                                    
                                    <div class="mt-2 small">
                                        <div class="text-success mb-1">
                                            <i class="fas fa-arrow-up"></i> قازانجی سەرەتایی: <?php echo number_format($grossProfit); ?> د.ع
                                        </div>
                                        <div class="text-muted mb-1">
                                            <i class="fas fa-minus"></i> خەرجی: <?php echo number_format($warehouseExpenses + $employeeExpenses + $warehouseLosses); ?> د.ع
                                        </div>
                                    </div>
                                    
                                    <div class="stat-status <?php echo $netProfit >= 0 ? 'text-success' : 'text-danger'; ?> fw-bold">
                                        <?php echo $netProfit >= 0 ? 'قازانج' : 'زەرەر'; ?>
                                    </div>
                                    <div class="stat-change <?php echo $netProfit >= 0 ? 'positive' : 'negative'; ?> mt-2">
                                        <i class="fas <?php echo $netProfit >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i> 12.7%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Available Cash -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card <?php echo $availableCash >= 0 ? 'bg-primary-light' : 'bg-danger-light'; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon <?php echo $availableCash >= 0 ? 'bg-primary-light' : 'bg-danger-light'; ?>">
                                            <i class="fas fa-wallet <?php echo $availableCash >= 0 ? 'text-primary' : 'text-danger'; ?>"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">بینینی حیسابات</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title"><?php echo $availableCash >= 0 ? 'پارەی بەردەست' : 'کورتهێنان'; ?></h6>
                                    <h3 class="stat-value"><?php echo number_format(abs($availableCash)); ?> د.ع</h3>
                                    <div class="stat-status <?php echo $availableCash >= 0 ? 'text-primary' : 'text-danger'; ?> fw-bold">
                                        <?php echo $availableCash >= 0 ? 'پارە هەیە' : 'کورتهێنان'; ?>
                                    </div>
                                    <div class="stat-change <?php echo $availableCash >= 0 ? 'positive' : 'negative'; ?> mt-2">
                                        <i class="fas <?php echo $availableCash >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i> 8.9%
                                    </div>
                                </div>
                            </div>
                        </div>
             
                        <!-- Suppliers -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-danger-light">
                                            <i class="fas fa-truck text-danger"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="suppliers.php">بینینی دابینکەرەکان</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">دابینکەرەکان</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalSuppliers); ?></h3>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 2.1%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Debt We Owe to Suppliers -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-danger-light">
                                            <i class="fas fa-hand-holding-usd text-danger"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">بینینی قەرزی دابینکەرەکان</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">قەرزی سەر خۆمان</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalDebtToSuppliers); ?> د.ع</h3>
                                    <div class="stat-change neutral mt-2">
                                        <i class="fas fa-info-circle"></i> قەرزی کۆمپانیا
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Debt Suppliers Owe to Us -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-info-light">
                                            <i class="fas fa-money-bill-wave text-info"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">بینینی قەرزی دابینکەرەکان</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">قەرزی سەر دابینکەر</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalDebtFromSuppliers); ?> د.ع</h3>
                                    <div class="stat-change neutral mt-2">
                                        <i class="fas fa-info-circle"></i> قەرزی دابینکەر
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Discounts -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-primary-light">
                                            <i class="fas fa-percent text-primary"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">زیاتر</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">کۆی داشکاندن</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalDiscounts); ?> د.ع</h3>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 8.3%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Warehouse Expenses -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-warning-light">
                                            <i class="fas fa-file-invoice-dollar text-warning"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">زیاتر</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">دەرکردنی پارە</h6>
                                    <h3 class="stat-value"><?php echo number_format($warehouseExpenses); ?> د.ع</h3>
                                    <div class="stat-change negative mt-2">
                                        <i class="fas fa-arrow-down"></i> 3.1%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Expenses -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-info-light">
                                            <i class="fas fa-user-tie text-info"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="expensesHistory.php">بینینی خەرجییەکان</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">خەرجی کارمەندان</h6>
                                    <h3 class="stat-value"><?php echo number_format($employeeExpenses); ?> د.ع</h3>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 2.4%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="chart-container">
                                <h5 class="card-title mb-4">قازانج و زەرەر بەپێی مانگ</h5>
                                <div id="monthlyProfitChart" style="height: 350px;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="chart-container">
                                <h5 class="card-title mb-4">فرۆشتن بەپێی کاتەگۆری</h5>
                                <div id="categorySalesChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Forecast and Cash Flow -->
                    <div class="row mb-4">
                        <!-- Sales Forecast -->
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="report-card">
                                <div class="card-body">
                                    <h5 class="card-title">پێشبینی فرۆشتن (3 مانگی داهاتوو)</h5>
                                    <div id="salesForecastChart" style="height: 300px;"></div>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i> ئەم پێشبینیە لەسەر بنەمای 5% گەشەی مانگانە دروستکراوە.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cash Flow -->
                        <div class="col-md-6">
                            <div class="report-card">
                                <div class="card-body">
                                    <h5 class="card-title">شیکاری پارەی گەڕاو</h5>
                                    <div id="cashFlowChart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- ApexCharts JS -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.40.0/dist/apexcharts.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <!-- DateRangePicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- Global JS -->
    <script src="../../js/include-components.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Monthly Sales Chart
            const monthlySalesOptions = {
                series: [{
                    name: 'فرۆشتن',
                    data: [<?php echo implode(',', array_map(function ($item) {
                                return $item['sales'];
                            }, $monthlySales)); ?>]
                }],
                chart: {
                    height: 300,
                    type: 'area',
                    fontFamily: 'rabar_021, sans-serif',
                    toolbar: {
                        show: false
                    },
                    background: '#fff',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    lineCap: 'round'
                },
                colors: ['#4361ee'],
                xaxis: {
                    categories: [<?php echo "'" . implode("','", array_map(function ($item) {
                                        return $item['month'];
                                    }, $monthlySales)) . "'"; ?>],
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: true,
                        color: '#e0e0e0'
                    },
                    axisTicks: {
                        show: true,
                        color: '#e0e0e0'
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            return (val / 1000).toFixed(0) + ' هەزار';
                        }
                    },
                    axisBorder: {
                        show: true,
                        color: '#e0e0e0'
                    }
                },
                grid: {
                    borderColor: '#f1f1f1',
                    strokeDashArray: 4,
                    padding: {
                        top: 0,
                        right: 0,
                        bottom: 0,
                        left: 0
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(val) {
                            return val.toLocaleString() + ' د.ع';
                        }
                    },
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        shadeIntensity: 0.5,
                        gradientToColors: ['rgba(67, 97, 238, 0.2)'],
                        inverseColors: false,
                        opacityFrom: 0.8,
                        opacityTo: 0.2,
                        stops: [0, 100]
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                }
            };

            const monthlySalesChart = new ApexCharts(document.querySelector("#monthlySalesChart"), monthlySalesOptions);
            monthlySalesChart.render();

            // Monthly Profit Chart
            const monthlyProfitOptions = {
                series: [{
                    name: 'داهات',
                    type: 'column',
                    data: [<?php echo implode(',', array_map(function ($item) {
                                return $item['revenue'];
                            }, $monthlyProfitData)); ?>]
                }, {
                    name: 'خەرجی',
                    type: 'column',
                    data: [<?php echo implode(',', array_map(function ($item) {
                                return $item['expenses'];
                            }, $monthlyProfitData)); ?>]
                }, {
                    name: 'قازانج',
                    type: 'line',
                    data: [<?php echo implode(',', array_map(function ($item) {
                                return $item['profit'];
                            }, $monthlyProfitData)); ?>]
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    fontFamily: 'rabar_021, sans-serif',
                    stacked: false,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        columnWidth: '55%',
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                stroke: {
                    width: [0, 0, 3],
                    curve: 'smooth',
                    lineCap: 'round'
                },
                colors: ['#4361ee', '#ff6b6b', '#2ecc71'],
                xaxis: {
                    categories: [<?php echo "'" . implode("','", array_map(function ($item) {
                                        return $item['month'];
                                    }, $monthlyProfitData)) . "'"; ?>],
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: true,
                        color: '#e0e0e0'
                    },
                    axisTicks: {
                        show: true,
                        color: '#e0e0e0'
                    }
                },
                yaxis: [{
                    axisTicks: {
                        show: true,
                    },
                    axisBorder: {
                        show: true,
                        color: '#4361ee'
                    },
                    labels: {
                        style: {
                            colors: '#4361ee',
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            return (val / 1000).toFixed(0) + ' هەزار';
                        }
                    },
                    title: {
                        text: "داهات",
                        style: {
                            color: '#4361ee',
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '14px'
                        }
                    }
                }, {
                    seriesName: 'خەرجی',
                    show: false
                }, {
                    opposite: true,
                    axisTicks: {
                        show: true,
                    },
                    axisBorder: {
                        show: true,
                        color: '#2ecc71'
                    },
                    labels: {
                        style: {
                            colors: '#2ecc71',
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            return (val / 1000).toFixed(0) + ' هەزار';
                        }
                    },
                    title: {
                        text: "قازانج",
                        style: {
                            color: '#2ecc71',
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '14px'
                        }
                    }
                }],
                grid: {
                    borderColor: '#f1f1f1',
                    strokeDashArray: 4,
                    padding: {
                        top: 0,
                        right: 0,
                        bottom: 0,
                        left: 0
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(val) {
                            return val.toLocaleString() + ' د.ع';
                        }
                    },
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                }
            };

            // Initialize the monthly profit chart
            if (document.querySelector("#monthlyProfitChart")) {
                const monthlyProfitChart = new ApexCharts(document.querySelector("#monthlyProfitChart"), monthlyProfitOptions);
                monthlyProfitChart.render();
            }

            // Category Sales Chart
            const categorySalesOptions = {
                series: [<?php echo implode(',', array_map(function ($item) {
                                return $item['percentage'];
                            }, $categorySalesAnalysis)); ?>],
                chart: {
                    type: 'donut',
                    height: 350,
                    fontFamily: 'rabar_021, sans-serif',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                labels: [<?php echo "'" . implode("','", array_map(function ($item) {
                                return $item['category_name'];
                            }, $categorySalesAnalysis)) . "'"; ?>],
                colors: ['#4361ee', '#2ecc71', '#ff6b6b', '#f1c40f', '#9b59b6', '#3498db', '#e74c3c', '#1abc9c', '#f39c12', '#34495e'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '22px',
                                    fontWeight: 600,
                                    fontFamily: 'rabar_021, sans-serif',
                                    color: '#2c3e50'
                                },
                                value: {
                                    show: true,
                                    fontSize: '16px',
                                    fontFamily: 'rabar_021, sans-serif',
                                    color: '#2c3e50',
                                    formatter: function(val) {
                                        return val + '%';
                                    }
                                },
                                total: {
                                    show: true,
                                    label: 'کۆی گشتی',
                                    fontSize: '16px',
                                    fontFamily: 'rabar_021, sans-serif',
                                    color: '#2c3e50',
                                    formatter: function(w) {
                                        return '100%';
                                    }
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontFamily: 'rabar_021, sans-serif',
                    fontSize: '12px',
                    labels: {
                        useSeriesColors: false,
                        colors: '#2c3e50'
                    },
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 6
                    },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 5
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            height: 300
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }],
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(val) {
                            return val + '%';
                        }
                    },
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                }
            };

            // Initialize the category sales chart
            if (document.querySelector("#categorySalesChart")) {
                const categorySalesChart = new ApexCharts(document.querySelector("#categorySalesChart"), categorySalesOptions);
                categorySalesChart.render();
            }

            // Sales Forecast Chart
            const salesForecastOptions = {
                series: [{
                    name: 'پێشبینی فرۆشتن',
                    data: [<?php echo implode(',', array_map(function ($item) {
                                return $item['forecast'];
                            }, $salesForecast)); ?>]
                }],
                chart: {
                    height: 300,
                    type: 'bar',
                    fontFamily: 'rabar_021, sans-serif',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 10,
                        dataLabels: {
                            position: 'top'
                        },
                        columnWidth: '60%'
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return (val / 1000).toFixed(0) + ' هەزار';
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ["#2c3e50"],
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                colors: ['#4361ee'],
                xaxis: {
                    categories: [<?php echo "'" . implode("','", array_map(function ($item) {
                                        return $item['month'];
                                    }, $salesForecast)) . "'"; ?>],
                    position: 'bottom',
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: true,
                        color: '#e0e0e0'
                    },
                    axisTicks: {
                        show: true,
                        color: '#e0e0e0'
                    }
                },
                yaxis: {
                    axisBorder: {
                        show: true,
                        color: '#e0e0e0'
                    },
                    axisTicks: {
                        show: true,
                        color: '#e0e0e0'
                    },
                    labels: {
                        show: true,
                        formatter: function(val) {
                            return (val / 1000).toFixed(0) + ' هەزار';
                        },
                        style: {
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        }
                    }
                },
                grid: {
                    borderColor: '#f1f1f1',
                    strokeDashArray: 4,
                    padding: {
                        top: 0,
                        right: 0,
                        bottom: 0,
                        left: 0
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(val) {
                            return val.toLocaleString() + ' د.ع';
                        }
                    },
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: "vertical",
                        shadeIntensity: 0.25,
                        gradientToColors: undefined,
                        inverseColors: true,
                        opacityFrom: 0.85,
                        opacityTo: 0.85,
                        stops: [50, 0, 100]
                    }
                }
            };

            const salesForecastChart = new ApexCharts(document.querySelector("#salesForecastChart"), salesForecastOptions);
            salesForecastChart.render();

            // Cash Flow Chart
            const cashFlowOptions = {
                series: [{
                    name: 'پارەی هاتوو',
                    data: [<?php echo implode(',', array_map(function ($item) {
                                return $item['incoming'];
                            }, $formattedCashFlow)); ?>]
                }, {
                    name: 'پارەی چوو',
                    data: [<?php echo implode(',', array_map(function ($item) {
                                return $item['outgoing'];
                            }, $formattedCashFlow)); ?>]
                }, {
                    name: 'کۆی پارەی ماوە',
                    data: [<?php echo implode(',', array_map(function ($item) {
                                return $item['net'];
                            }, $formattedCashFlow)); ?>]
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    fontFamily: 'rabar_021, sans-serif',
                    stacked: false,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 8,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: [0, 0, 3],
                    curve: 'smooth',
                    lineCap: 'round'
                },
                colors: ['#2ecc71', '#ff6b6b', '#4361ee'],
                xaxis: {
                    categories: [<?php echo "'" . implode("','", array_map(function ($item) {
                                        return $item['month'];
                                    }, $formattedCashFlow)) . "'"; ?>],
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: true,
                        color: '#e0e0e0'
                    },
                    axisTicks: {
                        show: true,
                        color: '#e0e0e0'
                    }
                },
                yaxis: {
                    title: {
                        text: ''
                    },
                    labels: {
                        formatter: function(val) {
                            return (val / 1000).toFixed(0) + ' هەزار';
                        },
                        style: {
                            fontFamily: 'rabar_021, sans-serif',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: true,
                        color: '#e0e0e0'
                    },
                    axisTicks: {
                        show: true,
                        color: '#e0e0e0'
                    }
                },
                grid: {
                    borderColor: '#f1f1f1',
                    strokeDashArray: 4,
                    padding: {
                        top: 0,
                        right: 0,
                        bottom: 0,
                        left: 0
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(val) {
                            return val.toLocaleString() + ' د.ع';
                        }
                    },
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    offsetY: 0,
                    labels: {
                        useSeriesColors: false,
                        fontFamily: 'rabar_021, sans-serif',
                        fontSize: '12px'
                    },
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 6
                    }
                },
                fill: {
                    opacity: 1
                }
            };

            const cashFlowChart = new ApexCharts(document.querySelector("#cashFlowChart"), cashFlowOptions);
            cashFlowChart.render();

            // Sort Best Selling Products
            $('#sortBySales').click(function() {
                $(this).addClass('active');
                $('#sortByProfit').removeClass('active');

                // Sort by sales logic here (if needed - otherwise the default sorting)
            });

            $('#sortByProfit').click(function() {
                $(this).addClass('active');
                $('#sortBySales').removeClass('active');

                // Sort by profit logic here (if needed)
            });
        });
    </script>

    <!-- Add this before the closing </body> tag -->
    <script>
      
    </script>
</body>

</html>