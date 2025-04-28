<?php
// Include authentication check
require_once '../../includes/auth.php';

// Include the dashboard logic file
require_once '../../process/dashboard_logic.php';
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


    
    <!-- <link rel="stylesheet" href="../../assets/css/custom.css"> -->
    <!-- <link rel="stylesheet" href="../../css/dashboard.css"> -->
    <!-- <link rel="stylesheet" href="../../css/global.css"> -->
    <!-- <link rel="stylesheet" href="../../css/dashboard_styles.css"> -->
    
    <link rel="stylesheet" href="../../test/main.css">
    <style>
        .filter-buttons {
            margin-bottom: 20px;
            text-align: right;
        }
        .filter-buttons .btn {
            padding: 8px 15px;
            font-size: 14px;
            margin: 0 5px;
            border-radius: 20px;
        }
    </style>
</head>

<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>
        <div class="blure-shape"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="dashboard-container" id="content" style="margin: 0; margin-top: 65px">
                <div class="container-fluid p-0">
                    <!-- Quick Access Section -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <div class="quick-access-section d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
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
           
                    
                    <!-- Period Filter -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <div class="filter-buttons">
                                <a href="?period=today" class="btn <?php echo (!isset($_GET['period']) || $_GET['period'] == 'today') ? 'btn-primary' : 'btn-outline-primary'; ?>">ئەمڕۆ</a>
                                <a href="?period=month" class="btn <?php echo (isset($_GET['period']) && $_GET['period'] == 'month') ? 'btn-primary' : 'btn-outline-primary'; ?>">ئەم مانگە</a>
                                <a href="?period=year" class="btn <?php echo (isset($_GET['period']) && $_GET['period'] == 'year') ? 'btn-primary' : 'btn-outline-primary'; ?>">ئەم ساڵ</a>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Cards Section -->
                    <div class="row g-3 mb-3">
                        <!-- KPI Card 1 -->
 

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

                        <!-- KPI Card 5 - Customer Debt -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">کۆی پارە لە قەرزدا</h3>
                                    <div class="kpi-icon purple">
                                    <img src="../../assets/icons/money-owe.svg" alt="">   
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($totalCustomerDebt, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $customerDebtPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $customerDebtPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($customerDebtPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- KPI Card 6 - Supplier Debt -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">قەرزی دابینکەر لەسەر ئێمە</h3>
                                    <div class="kpi-icon purple">
                                        <img src="../../assets/icons/seller-money.svg" alt="">
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($totalSupplierDebt, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $supplierDebtPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $supplierDebtPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($supplierDebtPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Card 7 - Total Expenses -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">کۆی مەسروفات</h3>
                                    <div class="kpi-icon red">
                                        <img src="../../assets/icons/spending-icon.svg" alt="">
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($totalExpenses, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $expensesPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $expensesPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($expensesPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Card 8 - Total Profit -->
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
                            <div class="kpi-card h-100">
                                <div class="kpi-icon-wrapper">
                                    <h3 class="kpi-title">کۆی قازانج</h3>
                                    <div class="kpi-icon green">
                                        <img src="../../assets/icons/profit-icon.svg" alt="">
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value"><?php echo number_format($totalProfit, 0, '.', ','); ?> <span class="currency">د.ع</span></div>
                                    <div class="kpi-comparison <?php echo $profitPercentage >= 0 ? 'positive' : 'negative'; ?>">
                                        <i class="fas fa-arrow-<?php echo $profitPercentage >= 0 ? 'up' : 'down'; ?>"></i> <?php echo abs($profitPercentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Sections -->
                    <div class="row g-3 mb-3">
                        <!-- Sales Chart -->
                        <div class="col-lg-8 col-md-12 mb-4">
                            <div class="card chart-card h-100">
                                <div class="card-header bg-transparent border-0">
                                    <div class="chart-header d-flex justify-content-between align-items-center flex-wrap">
                                        <h5 class="chart-title mb-2 mb-md-0">شیکاری فرۆش و کڕین</h5>
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
                    <div class="row g-3">
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
                                                                if (!empty($product['image'])) {
                                                                    // Extract just the filename from the image path
                                                                    $filename = basename($product['image']);
                                                                    // Use our new API endpoint with absolute path
                                                                    $imagePath = "../../api/product_image.php?filename=" . urlencode($filename);
                                                                } else {
                                                                    $imagePath = "../../assets/img/pro-1.png";
                                                                }
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
                                                                if (!empty($product['image'])) {
                                                                    // Extract just the filename from the image path
                                                                    $filename = basename($product['image']);
                                                                    // Use our new API endpoint with absolute path
                                                                    $imagePath = "../../api/product_image.php?filename=" . urlencode($filename);
                                                                } else {
                                                                    $imagePath = "../../assets/img/pro-1.png";
                                                                }
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



    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js - Using a specific version for compatibility -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- Component loading script -->
    <script src="../../js/include-components.js"></script>
    <!-- Dashboard Charts Script -->
    <script src="../../js/dashboard_charts.js"></script>
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
</body>

</html>