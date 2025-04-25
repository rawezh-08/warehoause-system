<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Temporarily disable ONLY_FULL_GROUP_BY to fix SQL errors
$conn->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

// Function to get total count from a table
function getCount($table) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Function to get sum of a column
function getSum($table, $column) {
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
");
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
");
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
");
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
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCreditPurchases = $result['total_credit_purchases'];

// Calculate discounts, expenses, and other financial data
$stmt = $conn->prepare("SELECT COALESCE(SUM(discount), 0) as total_sale_discounts FROM sales");
$stmt->execute();
$saleDiscounts = $stmt->fetch(PDO::FETCH_ASSOC)['total_sale_discounts'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(discount), 0) as total_purchase_discounts FROM purchases");
$stmt->execute();
$purchaseDiscounts = $stmt->fetch(PDO::FETCH_ASSOC)['total_purchase_discounts'];

$totalDiscounts = $saleDiscounts + $purchaseDiscounts;

// Get employee expenses
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_employee_expenses FROM employee_payments");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$employeeExpenses = $result['total_employee_expenses'];

// Get warehouse expenses (from expenses table)
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_warehouse_expenses FROM expenses");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$warehouseExpenses = $result['total_warehouse_expenses'];

// Get warehouse losses - Assuming there's no separate category in expenses table
$warehouseLosses = 0;

// Calculate net profit (simple calculation)
$netProfit = $totalSales - $totalPurchases - $warehouseExpenses - $employeeExpenses - $warehouseLosses;

// Estimate available cash (from sales minus expenses and purchases)
$availableCash = $totalCashSales - $totalCashPurchases - $warehouseExpenses - $employeeExpenses;

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
$stmt = $conn->prepare("SELECT COALESCE(SUM(pieces_count), 0) as total FROM sale_items");
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
        COALESCE(SUM(p.current_quantity * p.purchase_price), 0) as total_inventory_value 
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
        ROUND(SUM(si.total_price) / (SELECT SUM(total_price) FROM sale_items) * 100, 1) as percentage
    FROM
        categories c
    JOIN
        products p ON c.id = p.category_id
    JOIN
        sale_items si ON p.id = si.product_id
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

// 8. Cash flow statistics
$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(source_date, '%Y-%m') as month,
        SUM(incoming) as total_incoming,
        SUM(outgoing) as total_outgoing,
        SUM(incoming - outgoing) as net_cash
    FROM (
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
    <link rel="stylesheet" href="../../components/assets/css/custom.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/dashboard_styles.css">
    <link rel="stylesheet" href="../../test/main.css">
    
    <style>
        /* Custom Scrollbar Styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Sidebar Active State */
        body.sidebar-active .main-content {
            margin-right: 260px;
        }

        /* Hover Effects */
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(115, 128, 236, 0.1);
        }

        /* Active State for Sidebar */
        #sidebar.active {
            width: 260px;
        }

        #sidebar.active ~ .main-content {
            margin-right: 260px;
        }

        /* Table Hover Effects */
        .report-table tbody tr:hover {
            background-color: rgba(115, 128, 236, 0.04);
        }

        .report-table tbody tr:hover td {
            transform: translateX(3px);
        }

        /* Button Hover Effects */
        .btn:hover {
            transform: translateY(-2px);
        }

        /* Card Hover Effects */
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(115, 128, 236, 0.1);
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
                        <div class="col-md-6">
                            <h3 class="page-title mb-0">ڕاپۆرتەکان</h3>
                            <p class="text-muted mb-0">ڕاپۆرتی هەموو چالاکییەکانی کۆگا</p>
                        </div>
                        <div class="col-md-6 d-flex justify-content-md-end mt-3 mt-md-0">
                            <div class="d-flex gap-3">
                                <div class="filter-dropdown">
                                    <div class="date-filter" id="dateRangePicker">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>ئەمڕۆ</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary" onclick="window.print()">
                                    <i class="fas fa-print me-2"></i> چاپکردن
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <!-- Products Count -->
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-primary-light">
                                            <i class="fas fa-box text-primary"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="products.php">بینینی کاڵاکان</a></li>
                                                <li><a class="dropdown-item" href="addProduct.php">زیادکردنی کاڵا</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">کۆی کاڵاکان</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalProducts); ?></h3>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 12.5%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Warehouse Value -->
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="stat-icon bg-warning-light">
                                            <i class="fas fa-warehouse text-warning"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="products.php">بینینی کاڵاکان</a></li>
                                                <li><a class="dropdown-item" href="#">ڕاپۆرتی کۆگا</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">بەهای کۆگا</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalInventoryValue); ?> د.ع</h3>
                                    <div class="stat-change positive mt-2">
                                        <i class="fas fa-arrow-up"></i> 9.3%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Sales -->
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
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
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
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
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card <?php echo $netProfit >= 0 ? 'bg-success-light' : 'bg-danger-light'; ?>">
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
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card <?php echo $availableCash >= 0 ? 'bg-primary-light' : 'bg-danger-light'; ?>">
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
                    </div>
                    
                    <!-- Second Row of Cards -->
                    <div class="row mb-4">
                        <!-- Suppliers -->
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
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
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
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
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
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
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
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
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
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
                        <div class="col-xl-2 col-md-6 mb-4">
                            <div class="card report-card">
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
                    
                    <!-- Tabs for Different Reports -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card report-card">
                                <div class="card-body">
                                    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="top-products-tab" data-bs-toggle="tab" data-bs-target="#top-products" type="button" role="tab" aria-controls="top-products" aria-selected="true">باشترین کاڵاکان</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="low-stock-tab" data-bs-toggle="tab" data-bs-target="#low-stock" type="button" role="tab" aria-controls="low-stock" aria-selected="false">کاڵای کەم</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="false">دوا مامەڵەکان</button>
                                        </li>
                                    </ul>
                                    <div class="tab-content" id="reportTabsContent">
                                        <!-- Top Products Tab -->
                                        <div class="tab-pane fade show active" id="top-products" role="tabpanel" aria-labelledby="top-products-tab">
                                            <div class="table-responsive">
                                                <table class="table report-table">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ناوی کاڵا</th>
                                                            <th>ژمارەی فرۆشراو</th>
                                                            <th>بەهای فرۆشتن</th>
                                                            <th>کردار</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($topProducts as $index => $product): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                            <td><?php echo number_format($product['quantity']); ?></td>
                                                            <td><?php echo number_format($product['amount']); ?> د.ع</td>
                                                            <td>
                                                                <a href="#" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i> بینین
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="text-center mt-4">
                                                <a href="#" class="btn btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-2"></i> بینینی هەموو کاڵاکان
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Low Stock Products Tab -->
                                        <div class="tab-pane fade" id="low-stock" role="tabpanel" aria-labelledby="low-stock-tab">
                                            <div class="table-responsive">
                                                <table class="table report-table">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>ناوی کاڵا</th>
                                                            <th>بڕی ئێستا</th>
                                                            <th>کەمترین بڕ</th>
                                                            <th>ئاستی کۆگا</th>
                                                            <th>کردار</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($lowStockProducts as $index => $product): 
                                                            $percentage = min(100, ($product['current'] / $product['min']) * 100);
                                                            $stockClass = $percentage <= 30 ? 'critical' : ($percentage <= 60 ? 'warning' : 'good');
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                            <td><?php echo number_format($product['current']); ?></td>
                                                            <td><?php echo number_format($product['min']); ?></td>
                                                            <td>
                                                                <div class="stock-indicator">
                                                                    <div class="stock-level <?php echo $stockClass; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <a href="#" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-shopping-cart"></i> داواکردن
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="text-center mt-4">
                                                <a href="#" class="btn btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-2"></i> بینینی هەموو مامەڵەکان
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Recent Transactions Tab -->
                                        <div class="tab-pane fade" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                                            <div class="table-responsive">
                                                <table class="table report-table">
                                                    <thead>
                                                        <tr>
                                                            <th>ڕێکەوت</th>
                                                            <th>جۆر</th>
                                                            <th>بڕی پارە</th>
                                                            <th>دۆخ</th>
                                                            <th>کردار</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($recentTransactions as $transaction): 
                                                            $statusClass = $transaction['type'] === 'فرۆشتن' ? 'success' : 'info';
                                                        ?>
                                                        <tr>
                                                            <td><?php echo date('Y/m/d', strtotime($transaction['date'])); ?></td>
                                                            <td>
                                                                <span class="badge rounded-pill bg-<?php echo $statusClass; ?>-light text-<?php echo $statusClass; ?>">
                                                                    <?php echo htmlspecialchars($transaction['type']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo number_format($transaction['amount']); ?> د.ع</td>
                                                            <td>
                                                                <span class="table-status bg-success-light text-success">
                                                                    <?php echo htmlspecialchars($transaction['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <a href="#" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i> بینین
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="text-center mt-4">
                                                <a href="#" class="btn btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-2"></i> بینینی هەموو مامەڵەکان
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export & Report Options -->
                    <div class="row mb-4">
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card report-card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">دەرهێنانی داتا</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary">
                                                    <i class="fas fa-file-excel me-2"></i> دەرهێنان بۆ Excel
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary">
                                                    <i class="fas fa-file-pdf me-2"></i> دەرهێنان بۆ PDF
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary">
                                                    <i class="fas fa-file-csv me-2"></i> دەرهێنان بۆ CSV
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary">
                                                    <i class="fas fa-print me-2"></i> چاپکردن
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card report-card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">ڕاپۆرتەکانی تر</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary">
                                                    <i class="fas fa-chart-line me-2"></i> قازانج و زیان
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary">
                                                    <i class="fas fa-boxes me-2"></i> بارودۆخی کۆگا
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary">
                                                    <i class="fas fa-users me-2"></i> چالاکی کارمەندان
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary">
                                                    <i class="fas fa-coins me-2"></i> پوختەی دارایی
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- New Report Sections -->
                    
                    <!-- Low Stock Alert -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title">ئاگادارکردنەوەی کەمبوونی بەرهەمەکان</h5>
                                        <span class="badge bg-danger"><?php echo $criticalStockCount; ?> بەرهەم لە خوار سنووری کەمترین بڕ</span>
                </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover report-table">
                                            <thead>
                                                <tr>
                                                    <th>ناو</th>
                                                    <th>کۆد</th>
                                                    <th>کاتەگۆری</th>
                                                    <th>بڕی ئێستا</th>
                                                    <th>کەمترین بڕ</th>
                                                    <th>دۆخ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lowStockAlert as $product): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($product['image']): ?>
                                                                <img src="../../<?php echo $product['image']; ?>" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                                            <?php else: ?>
                                                                <div class="me-2" style="width: 40px; height: 40px; background-color: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-box text-muted"></i></div>
                                                            <?php endif; ?>
                                                            <span><?php echo $product['name']; ?></span>
            </div>
                                                    </td>
                                                    <td><?php echo $product['code']; ?></td>
                                                    <td><?php echo $product['category_name']; ?></td>
                                                    <td><?php echo $product['current_quantity']; ?></td>
                                                    <td><?php echo $product['min_quantity']; ?></td>
                                                    <td>
                                                        <div class="stock-indicator">
                                                            <div class="stock-level <?php echo $product['stock_percentage'] < 50 ? 'critical' : ($product['stock_percentage'] < 100 ? 'warning' : 'good'); ?>" style="width: <?php echo min($product['stock_percentage'], 100); ?>%;"></div>
                                                        </div>
                                                        <div class="small mt-1 text-<?php echo $product['stock_percentage'] < 50 ? 'danger' : ($product['stock_percentage'] < 100 ? 'warning' : 'success'); ?>">
                                                            <?php echo $product['stock_percentage']; ?>%
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <a href="products.php" class="btn btn-sm btn-outline-primary">بینینی هەموو بەرهەمەکان</a>
                                    </div>
                                </div>
                            </div>
        </div>
    </div>

                    <!-- Best Selling Products and Customer Debt Analysis -->
                    <div class="row mb-4">
                        <!-- Best Selling Products -->
                        <div class="col-md-7 mb-4 mb-md-0">
                            <div class="card report-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title">بەرهەمە باشفرۆشەکان</h5>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary active" id="sortBySales">بەپێی فرۆشتن</button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="sortByProfit">بەپێی قازانج</button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover report-table" id="bestSellingTable">
                                            <thead>
                                                <tr>
                                                    <th>بەرهەم</th>
                                                    <th>کۆد</th>
                                                    <th>دانە</th>
                                                    <th>کۆی فرۆشتن</th>
                                                    <th>قازانج</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bestSellingProducts as $product): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($product['image']): ?>
                                                                <img src="../../<?php echo $product['image']; ?>" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                                            <?php else: ?>
                                                                <div class="me-2" style="width: 40px; height: 40px; background-color: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-box text-muted"></i></div>
                                                            <?php endif; ?>
                                                            <span><?php echo $product['name']; ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $product['code']; ?></td>
                                                    <td><?php echo number_format($product['total_quantity']); ?></td>
                                                    <td><?php echo number_format($product['total_sales']); ?> د.ع</td>
                                                    <td><?php echo number_format($product['total_profit']); ?> د.ع</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Debt Analysis -->
                        <div class="col-md-5">
                            <div class="card report-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title">شیکاری قەرز</h5>
                                        <span class="badge bg-primary">کۆی قەرز: <?php echo number_format($totalCustomerDebt); ?> د.ع</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover report-table">
                                            <thead>
                                                <tr>
                                                    <th>کڕیار</th>
                                                    <th>ژمارە</th>
                                                    <th>بڕی قەرز</th>
                                                    <th>ڕۆژی دوایین</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($topDebtCustomers as $customer): ?>
                                                <tr>
                                                    <td><?php echo $customer['name']; ?></td>
                                                    <td><?php echo $customer['phone1']; ?></td>
                                                    <td><?php echo number_format($customer['debt_amount']); ?> د.ع</td>
                                                    <td><?php echo isset($customer['days_since_last_purchase']) ? $customer['days_since_last_purchase'] : '-'; ?> ڕۆژ</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <a href="#" class="btn btn-sm btn-outline-primary">هەموو قەرزەکان</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Profit/Loss Analysis and Category Sales -->
                    <div class="row mb-4">
                        <!-- Monthly Profit/Loss Analysis -->
                        <div class="col-md-8 mb-4 mb-md-0">
                            <div class="card report-card">
                                <div class="card-body">
                                    <h5 class="card-title">شیکاری قازانج و زەرەر بەپێی مانگ</h5>
                                    <div id="monthlyProfitChart" style="height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category Sales Analysis -->
                        <div class="col-md-4">
                            <div class="card report-card">
                                <div class="card-body">
                                    <h5 class="card-title">فرۆشتن بەپێی کاتەگۆری</h5>
                                    <div id="categorySalesChart" style="height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sales Forecast and Cash Flow -->
                    <div class="row mb-4">
                        <!-- Sales Forecast -->
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="card report-card">
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
                            <div class="card report-card">
                                <div class="card-body">
                                    <h5 class="card-title">شیکاری پارەی گەڕاو</h5>
                                    <div id="cashFlowChart" style="height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Behavior Analysis -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card report-card">
                                <div class="card-body">
                                    <h5 class="card-title">شیکاری هەڵسوکەوتی کڕیارەکان</h5>
                                    <div class="table-responsive">
                                        <table class="table table-hover report-table">
                                            <thead>
                                                <tr>
                                                    <th>کڕیار</th>
                                                    <th>ژمارەی کڕین</th>
                                                    <th>کۆی خەرجکردن</th>
                                                    <th>تێکڕای قەرز</th>
                                                    <th>دوایین کڕین</th>
                                                    <th>دەستکاری</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($customerBehaviorAnalysis as $customer): ?>
                                                <tr>
                                                    <td><?php echo $customer['name']; ?></td>
                                                    <td><?php echo $customer['purchase_count']; ?></td>
                                                    <td><?php echo number_format($customer['total_spent']); ?> د.ع</td>
                                                    <td><?php echo number_format($customer['avg_remaining']); ?> د.ع</td>
                                                    <td><?php echo date('Y-m-d', strtotime($customer['last_purchase_date'])); ?> (<?php echo $customer['days_since_last_purchase']; ?> ڕۆژ)</td>
                                                    <td>
                                                        <a href="customerProfile.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-primary">پڕۆفایل</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Monthly Sales Chart
            const monthlySalesOptions = {
            series: [{
                name: 'فرۆشتن',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['sales']; }, $monthlySales)); ?>]
            }],
            chart: {
                    height: 300,
                    type: 'area',
                    fontFamily: 'rabar_021, sans-serif',
                toolbar: {
                    show: false
                }
            },
                    dataLabels: {
                    enabled: false,
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#7380ec'],
                xaxis: {
                    categories: [<?php echo "'" . implode("','", array_map(function($item) { return $item['month']; }, $monthlySales)) . "'"; ?>],
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif'
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
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
                        gradientToColors: ['rgba(115, 128, 236, 0.2)'],
                        inverseColors: false,
                        opacityFrom: 0.7,
                        opacityTo: 0.2,
                        stops: [0, 100]
                    }
                },
                legend: {
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                }
            };
            
            const monthlySalesChart = new ApexCharts(document.querySelector("#monthlySalesChart"), monthlySalesOptions);
            monthlySalesChart.render();
            
            // Monthly Profit/Loss Chart
            const monthlyProfitOptions = {
                series: [{
                    name: 'داهات',
                    type: 'column',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['revenue']; }, $monthlyProfitData)); ?>]
                }, {
                    name: 'خەرجی',
                    type: 'column',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['expenses']; }, $monthlyProfitData)); ?>]
                }, {
                    name: 'قازانج',
                    type: 'line',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['profit']; }, $monthlyProfitData)); ?>]
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    fontFamily: 'rabar_021, sans-serif',
                    stacked: false,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 5,
                        columnWidth: '50%',
                    },
                },
                stroke: {
                    width: [0, 0, 3]
                },
                colors: ['#7380ec', '#ff7782', '#41f1b6'],
            xaxis: {
                    categories: [<?php echo "'" . implode("','", array_map(function($item) { return $item['month']; }, $monthlyProfitData)) . "'"; ?>],
                labels: {
                    style: {
                            fontFamily: 'rabar_021, sans-serif'
                        }
                    }
                },
                yaxis: [
                    {
                        axisTicks: {
                            show: true,
                },
                axisBorder: {
                            show: true,
                            color: '#7380ec'
                        },
                        labels: {
                            style: {
                                colors: '#7380ec',
                                fontFamily: 'rabar_021, sans-serif'
                            },
                            formatter: function (val) {
                                return (val / 1000).toFixed(0) + ' هەزار';
                            }
                        },
                        title: {
                            text: "داهات",
                            style: {
                                color: '#7380ec',
                                fontFamily: 'rabar_021, sans-serif'
                            }
                        }
                    },
                    {
                        seriesName: 'خەرجی',
                    show: false
                },
                    {
                        opposite: true,
                axisTicks: {
                            show: true,
            },
                        axisBorder: {
                            show: true,
                            color: '#41f1b6'
                        },
                labels: {
                            style: {
                                colors: '#41f1b6',
                                fontFamily: 'rabar_021, sans-serif'
                            },
                            formatter: function (val) {
                                return (val / 1000).toFixed(0) + ' هەزار';
                            }
                        },
                        title: {
                            text: "قازانج",
                    style: {
                                color: '#41f1b6',
                                fontFamily: 'rabar_021, sans-serif'
                            }
                        }
                    },
                ],
            tooltip: {
                y: {
                        formatter: function (val) {
                        return val.toLocaleString() + ' د.ع';
                    }
                    },
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                legend: {
                    position: 'top',
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                }
            };
            
            const monthlyProfitChart = new ApexCharts(document.querySelector("#monthlyProfitChart"), monthlyProfitOptions);
            monthlyProfitChart.render();
            
            // Category Sales Chart
            const categorySalesOptions = {
                series: [<?php echo implode(',', array_map(function($item) { return $item['percentage']; }, $categorySalesAnalysis)); ?>],
            chart: {
                    type: 'donut',
                height: 350,
                    fontFamily: 'rabar_021, sans-serif',
                },
                labels: [<?php echo "'" . implode("','", array_map(function($item) { return $item['category_name']; }, $categorySalesAnalysis)) . "'"; ?>],
                colors: ['#7380ec', '#41f1b6', '#ffbb55', '#ff7782', '#9a86f3', '#4a3df5', '#f53d3d', '#1ea896', '#ffc107', '#6c757d'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '55%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '22px',
                                    fontWeight: 600,
                                    fontFamily: 'rabar_021, sans-serif'
                                },
                                value: {
                                    show: true,
                                    fontSize: '16px',
                                    fontFamily: 'rabar_021, sans-serif',
                                    formatter: function (val) {
                                        return val + '%';
                                    }
                                },
                                total: {
                                    show: true,
                                    label: 'کۆی گشتی',
                                    fontFamily: 'rabar_021, sans-serif',
                                    formatter: function (w) {
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
                    labels: {
                        useSeriesColors: false,
                        fontFamily: 'rabar_021, sans-serif'
                    },
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
                    y: {
                        formatter: function (val) {
                            return val + '%';
                        }
                    },
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                }
            };
            
            const categorySalesChart = new ApexCharts(document.querySelector("#categorySalesChart"), categorySalesOptions);
            categorySalesChart.render();
            
            // Sales Forecast Chart
            const salesForecastOptions = {
                series: [{
                    name: 'پێشبینی فرۆشتن',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['forecast']; }, $salesForecast)); ?>]
                }],
                chart: {
                    height: 300,
                    type: 'bar',
                    fontFamily: 'rabar_021, sans-serif',
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 10,
            dataLabels: {
                            position: 'top'
                        },
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return (val / 1000).toFixed(0) + ' هەزار';
                    },
                    offsetY: -20,
                style: {
                        fontSize: '12px',
                        colors: ["#304758"],
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                colors: ['#9a86f3'],
                xaxis: {
                    categories: [<?php echo "'" . implode("','", array_map(function($item) { return $item['month']; }, $salesForecast)) . "'"; ?>],
                    position: 'bottom',
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    crosshairs: {
                        fill: {
                            type: 'gradient',
                            gradient: {
                                colorFrom: '#D8E3F0',
                                colorTo: '#BED1E6',
                                stops: [0, 100],
                                opacityFrom: 0.4,
                                opacityTo: 0.5,
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                    }
                },
                yaxis: {
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false,
                    },
                    labels: {
                        show: true,
                        formatter: function (val) {
                            return (val / 1000).toFixed(0) + ' هەزار';
                        },
                        style: {
                            fontFamily: 'rabar_021, sans-serif'
                        }
                }
            },
            tooltip: {
                y: {
                        formatter: function (val) {
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
                },
                legend: {
                    labels: {
                        useSeriesColors: false,
                        fontFamily: 'rabar_021, sans-serif'
                    }
                }
            };
            
            const salesForecastChart = new ApexCharts(document.querySelector("#salesForecastChart"), salesForecastOptions);
            salesForecastChart.render();
            
            // Cash Flow Chart
            const cashFlowOptions = {
                series: [{
                    name: 'پارەی هاتوو',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['incoming']; }, $formattedCashFlow)); ?>]
                }, {
                    name: 'پارەی چوو',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['outgoing']; }, $formattedCashFlow)); ?>]
                }, {
                    name: 'کۆی پارەی ماوە',
                    data: [<?php echo implode(',', array_map(function($item) { return $item['net']; }, $formattedCashFlow)); ?>]
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    fontFamily: 'rabar_021, sans-serif',
                    stacked: false,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 5,
                    },
                },
                dataLabels: {
                    enabled: false,
                    style: {
                        fontFamily: 'rabar_021, sans-serif'
                    }
                },
                stroke: {
                    show: true,
                    width: [0, 0, 3],
                    curve: 'smooth'
                },
                colors: ['#41f1b6', '#ff7782', '#7380ec'],
                    xaxis: {
                    categories: [<?php echo "'" . implode("','", array_map(function($item) { return $item['month']; }, $formattedCashFlow)) . "'"; ?>],
                    labels: {
                        style: {
                            fontFamily: 'rabar_021, sans-serif'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: ''
                    },
                    labels: {
                        formatter: function (val) {
                            return (val / 1000).toFixed(0) + ' هەزار';
                        },
                        style: {
                            fontFamily: 'rabar_021, sans-serif'
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
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
                        fontFamily: 'rabar_021, sans-serif'
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
</body>
</html> 