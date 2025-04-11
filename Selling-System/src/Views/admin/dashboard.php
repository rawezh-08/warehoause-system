<?php
// Database connection
require_once '../../config/database.php';

try {


    // Get current date and previous period date
    $currentDate = date('Y-m-d');
    $previousPeriodStart = date('Y-m-d', strtotime('-30 days'));

    // Fetch current period cash sales
    $currentCashSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                             FROM sales s 
                             JOIN sale_items si ON s.id = si.sale_id 
                             WHERE s.payment_type = 'cash'
                             AND s.date >= :previousPeriodStart";
    $stmt = $conn->prepare($currentCashSalesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $cashSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period cash sales
    $previousCashSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                              FROM sales s 
                              JOIN sale_items si ON s.id = si.sale_id 
                              WHERE s.payment_type = 'cash'
                              AND s.date < :previousPeriodStart
                              AND s.date >= DATE_SUB(:previousPeriodStart, INTERVAL 30 DAY)";
    $stmt = $conn->prepare($previousCashSalesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $previousCashSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate cash sales percentage change
    $cashSalesPercentage = $previousCashSales > 0 ?
        round((($cashSales - $previousCashSales) / $previousCashSales) * 100, 1) : 0;

    // Fetch current period credit sales
    $currentCreditSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                               FROM sales s 
                               JOIN sale_items si ON s.id = si.sale_id 
                               WHERE s.payment_type = 'credit'
                               AND s.date >= :previousPeriodStart";
    $stmt = $conn->prepare($currentCreditSalesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $creditSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period credit sales
    $previousCreditSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                                FROM sales s 
                                JOIN sale_items si ON s.id = si.sale_id 
                                WHERE s.payment_type = 'credit'
                                AND s.date < :previousPeriodStart
                                AND s.date >= DATE_SUB(:previousPeriodStart, INTERVAL 30 DAY)";
    $stmt = $conn->prepare($previousCreditSalesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $previousCreditSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate credit sales percentage change
    $creditSalesPercentage = $previousCreditSales > 0 ?
        round((($creditSales - $previousCreditSales) / $previousCreditSales) * 100, 1) : 0;

    // Fetch current period cash purchases
    $currentCashPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                 FROM purchases p 
                                 JOIN purchase_items pi ON p.id = pi.purchase_id 
                                 WHERE p.payment_type = 'cash'
                                 AND p.date >= :previousPeriodStart";
    $stmt = $conn->prepare($currentCashPurchasesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $cashPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period cash purchases
    $previousCashPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                  FROM purchases p 
                                  JOIN purchase_items pi ON p.id = pi.purchase_id 
                                  WHERE p.payment_type = 'cash'
                                  AND p.date < :previousPeriodStart
                                  AND p.date >= DATE_SUB(:previousPeriodStart, INTERVAL 30 DAY)";
    $stmt = $conn->prepare($previousCashPurchasesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $previousCashPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate cash purchases percentage change
    $cashPurchasesPercentage = $previousCashPurchases > 0 ?
        round((($cashPurchases - $previousCashPurchases) / $previousCashPurchases) * 100, 1) : 0;

    // Fetch current period credit purchases
    $currentCreditPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                   FROM purchases p 
                                   JOIN purchase_items pi ON p.id = pi.purchase_id 
                                   WHERE p.payment_type = 'credit'
                                   AND p.date >= :previousPeriodStart";
    $stmt = $conn->prepare($currentCreditPurchasesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $creditPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch previous period credit purchases
    $previousCreditPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                                    FROM purchases p 
                                    JOIN purchase_items pi ON p.id = pi.purchase_id 
                                    WHERE p.payment_type = 'credit'
                                    AND p.date < :previousPeriodStart
                                    AND p.date >= DATE_SUB(:previousPeriodStart, INTERVAL 30 DAY)";
    $stmt = $conn->prepare($previousCreditPurchasesQuery);
    $stmt->execute(['previousPeriodStart' => $previousPeriodStart]);
    $previousCreditPurchases = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate credit purchases percentage change
    $creditPurchasesPercentage = $previousCreditPurchases > 0 ?
        round((($creditPurchases - $previousCreditPurchases) / $previousCreditPurchases) * 100, 1) : 0;

    // Fetch low stock products with unit information
    $lowStockQuery = "SELECT p.*, c.name as category_name, u.name as unit_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      JOIN units u ON p.unit_id = u.id
                      WHERE p.current_quantity <= p.min_quantity 
                      ORDER BY p.current_quantity ASC 
                      LIMIT 4";
    $lowStockProducts = $conn->query($lowStockQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Fetch top selling products with unit information
    $topSellingQuery = "SELECT p.*, c.name as category_name, u.name as unit_name,
                        SUM(si.quantity) as total_sold,
                        si.unit_type
                        FROM products p 
                        JOIN categories c ON p.category_id = c.id 
                        JOIN units u ON p.unit_id = u.id
                        JOIN sale_items si ON p.id = si.product_id 
                        GROUP BY p.id, si.unit_type
                        ORDER BY total_sold DESC 
                        LIMIT 4";
    $topSellingProducts = $conn->query($topSellingQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Calculate warehouse occupancy percentage
    $totalProductsQuery = "SELECT COUNT(*) as total FROM products";
    $totalProducts = $conn->query($totalProductsQuery)->fetch(PDO::FETCH_ASSOC)['total'];

    $lowStockCountQuery = "SELECT COUNT(*) as count FROM products WHERE current_quantity <= min_quantity";
    $lowStockCount = $conn->query($lowStockCountQuery)->fetch(PDO::FETCH_ASSOC)['count'];

    $warehouseOccupancy = $totalProducts > 0 ? round(($lowStockCount / $totalProducts) * 100) : 0;

    // Calculate total sales and purchases for chart
    $totalSalesQuery = "SELECT COALESCE(SUM(si.total_price), 0) as total 
                        FROM sales s 
                        JOIN sale_items si ON s.id = si.sale_id";
    $totalSales = $conn->query($totalSalesQuery)->fetch(PDO::FETCH_ASSOC)['total'];

    $totalPurchasesQuery = "SELECT COALESCE(SUM(pi.total_price), 0) as total 
                           FROM purchases p 
                           JOIN purchase_items pi ON p.id = pi.purchase_id";
    $totalPurchases = $conn->query($totalPurchasesQuery)->fetch(PDO::FETCH_ASSOC)['total'];

    $totalTransactions = $totalSales + $totalPurchases;
    $salesPercentage = $totalTransactions > 0 ? round(($totalSales / $totalTransactions) * 100) : 0;
    $purchasesPercentage = $totalTransactions > 0 ? round(($totalPurchases / $totalTransactions) * 100) : 0;

    // Calculate monthly sales and purchases data for chart
    $monthlySalesQuery = "SELECT 
            DATE_FORMAT(s.date, '%Y-%m') as month,
            SUM(si.total_price) as total
        FROM 
            sales s
        JOIN 
            sale_items si ON s.id = si.sale_id
        WHERE 
            s.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY 
            DATE_FORMAT(s.date, '%Y-%m')
        ORDER BY 
            month ASC";

    $monthlyPurchasesQuery = "SELECT 
            DATE_FORMAT(p.date, '%Y-%m') as month,
            SUM(pi.total_price) as total
        FROM 
            purchases p
        JOIN 
            purchase_items pi ON p.id = pi.purchase_id
        WHERE 
            p.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY 
            DATE_FORMAT(p.date, '%Y-%m')
        ORDER BY 
            month ASC";

    $salesData = $conn->query($monthlySalesQuery)->fetchAll(PDO::FETCH_ASSOC);
    $purchasesData = $conn->query($monthlyPurchasesQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Format the data for the chart
    $chartMonths = [];
    $chartSales = [];
    $chartPurchases = [];

    // Get the last 6 months
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[] = date('Y-m', strtotime("-$i month"));
    }

    // Initialize arrays with zeros
    foreach ($months as $month) {
        $chartMonths[] = date('M Y', strtotime($month));
        $chartSales[$month] = 0;
        $chartPurchases[$month] = 0;
    }

    // Fill in actual data where it exists
    foreach ($salesData as $data) {
        if (isset($chartSales[$data['month']])) {
            $chartSales[$data['month']] = (float)$data['total'];
        }
    }

    foreach ($purchasesData as $data) {
        if (isset($chartPurchases[$data['month']])) {
            $chartPurchases[$data['month']] = (float)$data['total'];
        }
    }

    // Convert to simple arrays for the chart
    $salesValues = array_values($chartSales);
    $purchasesValues = array_values($chartPurchases);

    // JSON encode for JavaScript use with proper error handling
    $chartMonthsJson = json_encode(array_values($chartMonths)) ?: '[]';
    $salesValuesJson = json_encode($salesValues) ?: '[]';
    $purchasesValuesJson = json_encode($purchasesValues) ?: '[]';
    
    // Check if JSON encoding failed and provide fallback values
    if (json_last_error() !== JSON_ERROR_NONE) {
        $chartMonthsJson = '[]';
        $salesValuesJson = '[]';
        $purchasesValuesJson = '[]';
        $jsonError = json_last_error_msg();
        error_log("JSON encoding error: " . $jsonError);
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#7380ec">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>ASHKAN Warehouse - سیستەمی بەڕێوەبردنی کۆگا</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <style>
        /* Enhanced Color Contrast Styles */
        :root {
            /* Darker text colors for better contrast */
            --text-primary: #212529;
            --text-secondary: #495057;
            --text-muted: #6c757d;

            /* Stronger colors for status indicators */
            --success-color: #198754;
            --warning-color: #cc8800;
            --danger-color: #dc3545;
            --info-color: #0d6efd;
        }

        /* Table cell alignment fixes */
        .product-table td {
            vertical-align: middle !important;
        }

        .product-table .product-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }

        .product-table .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-table .quantity-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .product-table .quantity-info .product-count {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .product-table .quantity-info .unit-type {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Currency alignment fix */
        .kpi-value {
            direction: ltr;
            text-align: right;
            color: #000000;
            font-weight: 700;
            font-size: 1.75rem;
        }

        .kpi-value .currency {
            margin-right: 4px;
        }

        /* Enhanced KPI card styles */
        

        .kpi-title {
            color: #212529;
            font-weight: 600;
        }

        .kpi-comparison.positive {
            color: #198754;
            font-weight: 600;
        }

        .kpi-comparison.negative {
            color: #dc3545;
            font-weight: 600;
        }

        /* Enhanced quick access items */
        .quick-access-text {
            color: #212529;
            font-weight: 500;
        }

        /* Enhanced chart card styles */
        .chart-card {
            border: 1px solid #dee2e6;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .chart-title {
            color: #212529;
            font-weight: 600;
        }

        /* Enhanced product tables */
        .product-table thead th {
            background-color: #f8f9fa;
            color: #212529;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .product-table tbody td {
            color: #212529;
        }

        .product-count {
            background-color: #e9ecef;
            color: #212529;
            font-weight: 600;
        }

        /* Badge enhancements */
        .badge.bg-danger {
            background-color: #dc3545 !important;
            color: white !important;
            font-weight: 600;
        }

        .badge.bg-warning.text-dark {
            background-color: #cc8800 !important;
            color: white !important;
            font-weight: 600;
        }

        /* Button enhancements */
        .btn-outline-primary {
            color: #0d6efd;
            border-color: #0d6efd;
            font-weight: 500;
        }

        .btn-outline-primary:hover {
            background-color: #0d6efd;
            color: white;
        }

        /* Notification panel enhancements */
        .notification-content h4 {
            color: #212529;
            font-weight: 600;
        }

        .notification-content p {
            color: #495057;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .notification-time {
            color: #6c757d;
            font-weight: 500;
        }

        .notification-panel {
            border-left: 1px solid #dee2e6;
            background-color: #ffffff;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
        }

        .panel-header {
            border-bottom: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .panel-title {
            color: #212529;
            font-weight: 600;
        }

        .notification-item {
            border-bottom: 1px solid #f1f1f1;
        }

        .notification-item.unread {
            background-color: #f0f7ff;
        }

        .notification-icon.warning {
            background-color: #fff6e5;
            color: #cc8800;
        }

        .notification-icon.success {
            background-color: #e8f8f2;
            color: #198754;
        }

        .notification-icon.info {
            background-color: #e6f2ff;
            color: #0d6efd;
        }

        /* Progress chart enhancements */
        .progress-circle-container {
            padding: 15px;
        }

        .progress-value {
            color: #000000;
            font-weight: 700;
            font-size: 2rem;
        }

        .progress-label {
            color: #212529;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .progress-legend {
            margin-top: 15px;
        }

        .legend-item {
            font-weight: 500;
            color: #212529;
        }

        .legend-color.blue {
            background-color: #0d6efd;
        }

        .legend-color.light-blue {
            background-color: #a8c7fa;
        }
    </style>
</head>

<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="dashboard-container" id="content" style="margin: 0px;">
                <div class="container-fluid p-0">
                    <!-- Quick Access Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="quick-access-section d-flex flex-wrap justify-content-center justify-content-md-start">
                                <a href="addProduct.php" class="quick-access-item m-2">
                                    <div class="quick-access-icon blue">
                                        <img src="../../assets/icons/box.svg" alt="">
                                    </div>
                                    <span class="quick-access-text">زیادکردنی کاڵا</span>
                                </a>
                                <a href="addReceipt.php" class="quick-access-item m-2">
                                    <div class="quick-access-icon purple">
                                        <img src="../../assets/icons/buy.svg" alt="">
                                    </div>
                                    <span class="quick-access-text"> پسوڵەی نوێ</span>
                                </a>
                                <a href="customers.php" class="quick-access-item m-2">
                                    <div class="quick-access-icon green">
                                        <img src="../../assets/icons/sell.svg" alt="">
                                    </div>
                                    <span class="quick-access-text"> قەرزارەکان</span>
                                </a>
                                <a href="staff.php" class="quick-access-item m-2">
                                    <div class="quick-access-icon orange">
                                        <img src="../../assets/icons/users.svg" alt="">
                                    </div>
                                    <span class="quick-access-text">هەژمارەکان</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Cards Section -->
                    <div class="row mb-4">
                        <!-- KPI Card 1 -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">کۆی فرۆشتن بە نەقد</h3>
                                    <div class="kpi-icon blue">
                                    <img src="../../assets/icons/sell-cash.svg" alt="">   
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($cashSales, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $cashSalesPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $cashSalesPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($cashSalesPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Card 2 -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">کۆی فرۆشتن بە قەرز</h3>
                                    <div class="kpi-icon purple">
                                        <img src="../../assets/icons/sell-owe.svg" alt="">
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($creditSales, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $creditSalesPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $creditSalesPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($creditSalesPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Card 3 -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">کۆی کڕین بە نەقد</h3>
                                    <div class="kpi-icon green">
                                    <img src="../../assets/icons/buy-cash.svg" alt="">                                       </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($cashPurchases, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $cashPurchasesPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $cashPurchasesPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($cashPurchasesPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Card 4 -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">کۆی کڕین بە قەرز</h3>
                                    <div class="kpi-icon purple">
<img src="../../assets/icons/buy-owe.svg" alt="">                                       </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($creditPurchases, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $creditPurchasesPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $creditPurchasesPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($creditPurchasesPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Card 5 -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">کۆی پارە لە قەرزدا</h3>
                                    <div class="kpi-icon purple">
                                    <img src="../../assets/icons/money-owe.svg" alt="">   
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($creditPurchases, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $creditPurchasesPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $creditPurchasesPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($creditPurchasesPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- KPI Card ٦ -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">قەرزی دابینکەر لەسەر ئێمە</h3>
                                    <div class="kpi-icon purple">
                                    <img src="../../assets/icons/seller-money.svg" alt="">                                       </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($creditPurchases, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $creditPurchasesPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $creditPurchasesPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($creditPurchasesPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Sections -->
                    <div class="row mb-4">
                        <!-- Sales Chart -->
                        <div class="col-lg-8 col-md-12 mb-4">
                            <div class="card chart-card h-100">
                                <div class="card-header bg-transparent border-0">
                                    <div class="chart-header d-flex justify-content-between align-items-center flex-wrap">
                                        <h5 class="chart-title mb-2 mb-md-0">شیکاری فرۆش و کڕین</h5>
                                        <div class="chart-actions">
                                            <button class="btn btn-sm btn-outline-primary me-2">
                                                <i class="fas fa-download"></i> <span class="d-none d-md-inline">داگرتن</span>
                                            </button>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-light" type="button" id="chartOptionsDropdown"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="chartOptionsDropdown">
                                                    <li><a class="dropdown-item" href="#"><i class="fas fa-sync me-2"></i>
                                                            نوێکردنەوە</a></li>
                                                    <li><a class="dropdown-item" href="#"><i
                                                                class="fas fa-share-alt me-2"></i> هاوبەشکردن</a></li>
                                                    <li><a class="dropdown-item" href="#" id="changeChartType"><i
                                                                class="fas fa-chart-pie me-2"></i> گۆڕینی جۆری چارت</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-content">
                                        <canvas id="salesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Circle Chart -->
                        <div class="col-lg-4 col-md-12 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-transparent border-0">
                                    <h5 class="card-title">شیکاری فرۆشتن و کڕین</h5>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <div class="progress-circle-container">
                                        <canvas id="inventoryChart"></canvas>
                                    </div>
                                    <div class="progress-legend mt-3">
                                        <div class="legend-item">
                                            <span class="legend-color blue"></span>
                                            <span>فرۆشتن (<?php echo $salesPercentage; ?>%)</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-color light-blue"></span>
                                            <span>کڕین (<?php echo $purchasesPercentage; ?>%)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div class="row">
                        <!-- Low Stock Products -->
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card product-card low-stock h-100">
                                <div class="card-header bg-transparent">
                                    <div class="product-header">
                                        <h5 class="">کاڵا کەم ماوەکان</h5>
                                        <a href="products.php" class="btn btn-sm btn-light">
                                             <img src="../../assets/icons/product-sold-out.svg" alt="">
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="product-table-container">
                                        <table class="product-table table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>کاڵا</th>
                                                    <th>کۆدی کاڵا</th>
                                                    <th>ماوە</th>
                                                    <th>باری</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lowStockProducts as $product): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="product-info">
                                                                <?php 
                                                                $imagePath = $product['image'] 
                                                                    ? (strpos($product['image'], '/') !== false 
                                                                        ? $product['image'] 
                                                                        : '/warehouse-system/Selling-System/src/uploads/products/' . $product['image'])
                                                                    : '../../assets/img/pro-1.png';
                                                                ?>
                                                                <img src="<?php echo $imagePath; ?>"
                                                                    class="product-img" alt="Product">
                                                                <span><?php echo htmlspecialchars($product['name']); ?></span>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($product['code']); ?></td>
                                                        <td>
                                                            <div class="quantity-info">
                                                                <span class="product-count"><?php echo $product['current_quantity']; ?></span>
                                                                <span class="unit-type"><?php echo htmlspecialchars($product['unit_name']); ?></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $product['current_quantity'] <= $product['min_quantity'] ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                                                کەم
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Selling Products -->
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card product-card top-selling h-100">
                                <div class="card-header bg-transparent">
                                    <div class="product-header">
                                        <h5 class="">باشترین فرۆشراوەکان</h5>
                                        <a href="products.php" class="btn btn-sm btn-light">
                                             <img src="../../assets/icons/best-sell.svg" alt="">
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="product-table-container">
                                        <table class="product-table table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>کاڵا</th>
                                                    <th>کۆدی کاڵا</th>
                                                    <th>فرۆشراو</th>
                                                    <th>وردەکاری</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($topSellingProducts as $product): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="product-info">
                                                                <?php 
                                                                $imagePath = $product['image'] 
                                                                    ? (strpos($product['image'], '/') !== false 
                                                                        ? $product['image'] 
                                                                        : '/warehouse-system/Selling-System/src/uploads/products/' . $product['image'])
                                                                    : '../../assets/img/pro-1.png';
                                                                ?>
                                                                <img src="<?php echo $imagePath; ?>"
                                                                    class="product-img" alt="Product">
                                                                <span><?php echo htmlspecialchars($product['name']); ?></span>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($product['code']); ?></td>
                                                        <td>
                                                            <div class="quantity-info">
                                                                <span class="product-count"><?php echo $product['total_sold']; ?></span>
                                                                <span class="unit-type">
                                                                    <?php
                                                                    $unitType = $product['unit_type'];
                                                                    if ($unitType == 'piece') {
                                                                        echo 'دانە';
                                                                    } elseif ($unitType == 'box') {
                                                                        echo 'کارتۆن';
                                                                    } elseif ($unitType == 'set') {
                                                                        echo 'سێت';
                                                                    }
                                                                    ?>
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td><a href="#" class="btn btn-sm btn-outline-primary">بینین</a></td>
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

    <!-- Notification Panel (Hidden by default) -->
    <div class="notification-panel">
        <div class="panel-header">
            <h3 class="panel-title">ئاگادارکردنەوەکان</h3>
            <button class="btn-close-panel">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="panel-content">
            <div class="notification-list">
                <div class="notification-item unread">
                    <div class="notification-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="notification-content">
                        <h4>کەمبوونەوەی کۆگا</h4>
                        <p>کاناپێی ڕەش تەنها ١ دانە ماوە</p>
                        <span class="notification-time">٣ کاتژمێر لەمەوبەر</span>
                    </div>
                </div>
                <div class="notification-item">
                    <div class="notification-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="notification-content">
                        <h4>فرۆشتنێکی نوێ</h4>
                        <p>کاناپێی زێڕ بە بڕی ٣ دانە فرۆشرا</p>
                        <span class="notification-time">٥ کاتژمێر لەمەوبەر</span>
                    </div>
                </div>
                <div class="notification-item">
                    <div class="notification-icon info">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="notification-content">
                        <h4>زیادکردنی بەرهەم</h4>
                        <p>٥ دانە کاناپێی قاوەیی زیادکرا</p>
                        <span class="notification-time">١ ڕۆژ لەمەوبەر</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js - Using a specific version for compatibility -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- Component loading script -->
    <script src="../../js/include-components.js"></script>
    <!-- Load dashboard.js first -->
    <script src="../../js/dashboard.js"></script>
    <!-- Pass PHP data to JavaScript -->
    <script>
        // Make sure the data is properly formatted
        try {
            window.chartMonths = <?php echo $chartMonthsJson; ?>;
            window.salesData = <?php echo $salesValuesJson; ?>;
            window.purchasesData = <?php echo $purchasesValuesJson; ?>;
            window.salesPercentage = <?php echo (int)$salesPercentage; ?>;
            window.purchasesPercentage = <?php echo (int)$purchasesPercentage; ?>;

            // Debug data
            console.log('Chart Months:', window.chartMonths);
            console.log('Sales Data:', window.salesData);
            console.log('Purchases Data:', window.purchasesData);
            
            // Reset chart instances to ensure proper initialization
            window.salesChart = null;
            window.inventoryChart = null;
            
            // If Chart.js is available, run any deferred initializations
            if (typeof Chart !== 'undefined' && typeof runDeferredInit === 'function') {
                setTimeout(runDeferredInit, 100);
            }
        } catch (e) {
            console.error('Error parsing chart data:', e);
            // Provide fallback data
            window.chartMonths = [];
            window.salesData = [];
            window.purchasesData = [];
            window.salesPercentage = 0;
            window.purchasesPercentage = 0;
        }
    </script>
    <!-- Debugging script to help troubleshoot chart issues -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.info('DOMContentLoaded fired');
            
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded. Adding it dynamically.');
                
                // Create fallback indicators for charts
                const charts = ['salesChart', 'inventoryChart'];
                charts.forEach(chartId => {
                    const chartEl = document.getElementById(chartId);
                    if (chartEl) {
                        chartEl.insertAdjacentHTML('afterend', 
                            '<div class="text-center text-danger my-3">چارتەکە نەتوانرا بارکرێت - Chart.js نەدۆزرایەوە</div>');
                    }
                });
                
                // Try to load Chart.js again
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
                script.onload = function() {
                    console.info('Chart.js loaded successfully via fallback');
                    
                    // Run deferred initialization
                    if (typeof runDeferredInit === 'function') {
                        runDeferredInit();
                    }
                };
                script.onerror = function() {
                    console.error('Failed to load Chart.js via fallback');
                };
                document.head.appendChild(script);
            } else {
                console.info('Chart.js is loaded correctly');
            }
        });
    </script>
</body>

</html>