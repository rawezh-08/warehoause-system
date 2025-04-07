<?php
// You can add PHP logic here if needed
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#7380ec">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>ASHKAN Warehouse - سیستەمی بەڕێوەبردنی کۆگا</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
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
        
        /* Enhanced KPI card styles */
        .kpi-card {
            border: 1px solid #dee2e6;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .kpi-title {
            color: #212529;
            font-weight: 600;
        }
        
        .kpi-value {
            color: #000000;
            font-weight: 700;
            font-size: 1.75rem;
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
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
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
            box-shadow: -5px 0 15px rgba(0,0,0,0.1);
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
    <div id="content" >
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main Content Area -->
        <div class="main-content" >
            <div class="dashboard-container" id="content" style="margin: 0px;">
                <div class="container-fluid p-0">
                    <!-- Quick Access Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="quick-access-section d-flex flex-wrap justify-content-center justify-content-md-start">
                                <a href="addProduct.php" class="quick-access-item m-2">
                                    <div class="quick-access-icon blue">
                                        <img src="assets/icons/box.svg" alt="">
                                    </div>
                                    <span class="quick-access-text">زیادکردنی کاڵا</span>
                                </a>
                                <a href="addReceipt.php" class="quick-access-item m-2">
                                    <div class="quick-access-icon purple">
                                        <img src="assets/icons/buy.svg" alt="">
                                    </div>
                                    <span class="quick-access-text"> پسوڵەی نوێ</span>
                                </a>
                                <a href="customers.php" class="quick-access-item m-2">
                                    <div class="quick-access-icon green">
                                        <img src="assets/icons/sell.svg" alt="">
                                    </div>
                                    <span class="quick-access-text"> قەرزارەکان</span>
                                </a>
                                <a href="staff.php" class="quick-access-item m-2">
                                    <div class="quick-access-icon orange">
                                        <img src="assets/icons/users.svg" alt="">
                                    </div>
                                    <span class="quick-access-text">هەژمارەکان</span>
                                </a>
                                <!-- <a href="#" class="quick-access-item m-2">
                                    <div class="quick-access-icon bordered">
                                        <img src="assets/icons/add.svg" alt="">
                                    </div>
                                    <span class="quick-access-text">زیاتر</span>
                                </a> -->
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
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value">$ 24,500</div>
                                    <div class="kpi-comparison positive">
                                        <i class="fas fa-arrow-up"></i> 12.5%
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
                                        <i class="fas fa-box"></i>
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value">157</div>
                                    <div class="kpi-comparison positive">
                                        <i class="fas fa-arrow-up"></i> 8.2%
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
                                        <i class="fas fa-cubes"></i>
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value">2,856</div>
                                    <div class="kpi-comparison negative">
                                        <i class="fas fa-arrow-down"></i> 3.1%
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
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                                <div class="kpi-content">
                                    <div class="kpi-value">78,000</div>
                                    <div class="kpi-comparison positive">
                                        <i class="fas fa-arrow-up"></i> 5.7%
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
                                        <h5 class="chart-title mb-2 mb-md-0">شیکاری فرۆش</h5>
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
                                                    <li><a class="dropdown-item" href="#"><i
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
                                    <h5 class="card-title">شیکاری کۆگا</h5>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <div class="progress-circle-container">
                                        <div class="progress-indicator">
                                            <div class="progress-value">68<span style="font-size: 1.2rem;">%</span></div>
                                            <div class="progress-label">پڕبوونی کۆگا</div>
                                        </div>
                                        <canvas id="inventoryChart"></canvas>
                                    </div>
                                    <div class="progress-legend">
                                        <div class="legend-item">
                                            <span class="legend-color blue"></span>
                                            <span>پڕبوو</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-color light-blue"></span>
                                            <span>بەتاڵ</span>
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
                                        <h5 class="product-title">کاڵا کەم ماوەکان</h5>
                                        <a href="#" class="btn btn-sm btn-light">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="product-table-container">
                                        <table class="product-table table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>کاڵا</th>
                                                    <th>بارکۆد</th>
                                                    <th>ماوە</th>
                                                    <th>باری</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/pro-1.png" class="product-img me-3"
                                                                alt="Product">
                                                            <span>سەماوەر</span>
                                                        </div>
                                                    </td>
                                                    <td>P-1001</td>
                                                    <td><span class="product-count">3</span></td>
                                                    <td><span class="badge bg-danger">کەم</span></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/pro-1.png" class="product-img me-3"
                                                                alt="Product">
                                                            <span>شەرباتخۆر</span>
                                                        </div>
                                                    </td>
                                                    <td>P-1002</td>
                                                    <td><span class="product-count">5</span></td>
                                                    <td><span class="badge bg-warning text-dark">کەم</span></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/pro-1.png" class="product-img me-3"
                                                                alt="Product">
                                                            <span>تلێفزیۆن</span>
                                                        </div>
                                                    </td>
                                                    <td>P-1003</td>
                                                    <td><span class="product-count">8</span></td>
                                                    <td><span class="badge bg-warning text-dark">کەم</span></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/pro-1.png" class="product-img me-3"
                                                                alt="Product">
                                                            <span>مایکرۆویڤ</span>
                                                        </div>
                                                    </td>
                                                    <td>P-1004</td>
                                                    <td><span class="product-count">2</span></td>
                                                    <td><span class="badge bg-danger">کەم</span></td>
                                                </tr>
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
                                        <h5 class="product-title">باشترین فرۆشراوەکان</h5>
                                        <a href="#" class="btn btn-sm btn-light">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="product-table-container">
                                        <table class="product-table table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>کاڵا</th>
                                                    <th>بارکۆد</th>
                                                    <th>فرۆشراو</th>
                                                    <th>وردەکاری</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/pro-1.png" class="product-img me-3"
                                                                alt="Product">
                                                            <span>کمپیوتەر</span>
                                                        </div>
                                                    </td>
                                                    <td>P-2001</td>
                                                    <td><span class="product-count">32</span></td>
                                                    <td><a href="#" class="btn btn-sm btn-outline-primary">بینین</a></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/pro-1.png" class="product-img me-3"
                                                                alt="Product">
                                                            <span>مۆبایل</span>
                                                        </div>
                                                    </td>
                                                    <td>P-2002</td>
                                                    <td><span class="product-count">28</span></td>
                                                    <td><a href="#" class="btn btn-sm btn-outline-primary">بینین</a></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/pro-1.png" class="product-img me-3"
                                                                alt="Product">
                                                            <span>سەلاجە</span>
                                                        </div>
                                                    </td>
                                                    <td>P-2003</td>
                                                    <td><span class="product-count">25</span></td>
                                                    <td><a href="#" class="btn btn-sm btn-outline-primary">بینین</a></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/pro-1.png" class="product-img me-3"
                                                                alt="Product">
                                                            <span>تەلەفزیۆن</span>
                                                        </div>
                                                    </td>
                                                    <td>P-2004</td>
                                                    <td><span class="product-count">20</span></td>
                                                    <td><a href="#" class="btn btn-sm btn-outline-primary">بینین</a></td>
                                                </tr>
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Component loading script -->
    <script src="js/include-components.js"></script>
    <!-- Other custom scripts -->
    <script src="js/dashboard.js"></script>
</body>

</html>