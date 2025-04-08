<?php
// Sample data for charts and reports
$totalProducts = 1580;
$totalCategories = 25;
$totalSuppliers = 42;
$totalSales = 423750;

// Monthly sales data
$monthlySales = [
    ["month" => "بەفرانبار", "sales" => 42000],
    ["month" => "ڕەشەمێ", "sales" => 53000],
    ["month" => "نەورۆز", "sales" => 75000],
    ["month" => "گوڵان", "sales" => 68000],
    ["month" => "جۆزەردان", "sales" => 88000],
    ["month" => "پووشپەڕ", "sales" => 95000],
];

$topProducts = [
    ["id" => 1, "name" => "کاڵای ١", "quantity" => 153, "amount" => 76500],
    ["id" => 2, "name" => "کاڵای ٢", "quantity" => 129, "amount" => 64500],
    ["id" => 3, "name" => "کاڵای ٣", "quantity" => 98, "amount" => 49000],
    ["id" => 4, "name" => "کاڵای ٤", "quantity" => 75, "amount" => 37500],
    ["id" => 5, "name" => "کاڵای ٥", "quantity" => 63, "amount" => 31500]
];

$lowStockProducts = [
    ["id" => 1, "name" => "کاڵای ١", "current" => 8, "min" => 10],
    ["id" => 2, "name" => "کاڵای ٢", "current" => 5, "min" => 15],
    ["id" => 3, "name" => "کاڵای ٣", "current" => 3, "min" => 8],
    ["id" => 4, "name" => "کاڵای ٤", "current" => 2, "min" => 10],
    ["id" => 5, "name" => "کاڵای ٥", "current" => 7, "min" => 20]
];

$recentTransactions = [
    ["date" => "2023-07-15", "type" => "فرۆشتن", "amount" => 12500, "status" => "سەرکەوتوو"],
    ["date" => "2023-07-14", "type" => "کڕین", "amount" => 35000, "status" => "سەرکەوتوو"],
    ["date" => "2023-07-13", "type" => "فرۆشتن", "amount" => 8750, "status" => "سەرکەوتوو"],
    ["date" => "2023-07-12", "type" => "فرۆشتن", "amount" => 15000, "status" => "سەرکەوتوو"],
    ["date" => "2023-07-11", "type" => "کڕین", "amount" => 27500, "status" => "سەرکەوتوو"]
];

// Calculate totals
$totalSalesAmount = array_sum(array_column($monthlySales, 'sales'));
$totalProductsSold = array_sum(array_column($topProducts, 'quantity'));
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
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
    
    <style>
        /* Report Page Specific Styles - Enhanced Design */
        :root {
            --chart-primary: #7380ec;
            --chart-success: #41f1b6;
            --chart-warning: #ffbb55;
            --chart-danger: #ff7782;
            --chart-info: #9a86f3;
            --bg-gradient: linear-gradient(135deg, #f8f9fe 0%, #f1f4fd 100%);
        }
        
        body {
            background: var(--bg-gradient);
        }
        
        .report-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            backdrop-filter: blur(5px);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(115, 128, 236, 0.1);
        }
        
        .report-card .card-body {
            padding: 1.8rem;
        }
        
        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-icon::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0.2;
            border-radius: inherit;
            transform: scale(0.85);
            transition: all 0.4s ease;
        }
        
        .report-card:hover .stat-icon::before {
            transform: scale(1);
        }
        
        .stat-icon i {
            font-size: 2rem;
            color: white;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon.bg-primary-light {
            background-color: rgba(115, 128, 236, 0.15);
        }
        
        .stat-icon.bg-primary-light i {
            color: var(--chart-primary);
        }
        
        .stat-icon.bg-primary-light::before {
            background-color: var(--chart-primary);
        }
        
        .stat-icon.bg-success-light {
            background-color: rgba(65, 241, 182, 0.15);
        }
        
        .stat-icon.bg-success-light i {
            color: var(--chart-success);
        }
        
        .stat-icon.bg-success-light::before {
            background-color: var(--chart-success);
        }
        
        .stat-icon.bg-warning-light {
            background-color: rgba(255, 187, 85, 0.15);
        }
        
        .stat-icon.bg-warning-light i {
            color: var(--chart-warning);
        }
        
        .stat-icon.bg-warning-light::before {
            background-color: var(--chart-warning);
        }
        
        .stat-icon.bg-danger-light {
            background-color: rgba(255, 119, 130, 0.15);
        }
        
        .stat-icon.bg-danger-light i {
            color: var(--chart-danger);
        }
        
        .stat-icon.bg-danger-light::before {
            background-color: var(--chart-danger);
        }
        
        .stat-title {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .stat-value {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            line-height: 1.2;
            background: linear-gradient(45deg, var(--text-primary), #4a4a4a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 20px;
            width: fit-content;
        }
        
        .stat-change.positive {
            color: var(--chart-success);
            background-color: rgba(65, 241, 182, 0.1);
        }
        
        .stat-change.negative {
            color: var(--chart-danger);
            background-color: rgba(255, 119, 130, 0.1);
        }
        
        .stat-change i {
            margin-right: 0.35rem;
            font-size: 0.9rem;
        }
        
        .chart-container {
            height: 380px;
            position: relative;
        }
        
        .card-title {
            font-weight: 600;
            margin-bottom: 0;
            font-size: 1.2rem;
            color: #363949;
            position: relative;
        }
        
        .date-filter {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            padding: 0.6rem 1.2rem;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .date-filter:hover {
            border-color: var(--chart-primary);
            box-shadow: 0 3px 12px rgba(115, 128, 236, 0.15);
        }
        
        .date-filter i {
            margin-left: 0.8rem;
            color: var(--chart-primary);
            font-size: 1.1rem;
        }
        
        .filter-dropdown {
            position: relative;
        }
        
        .filter-dropdown .dropdown-menu {
            min-width: 12rem;
            padding: 0.75rem 0;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            border: none;
            border-radius: 12px;
            animation: dropdown-fade 0.2s ease-out;
        }
        
        @keyframes dropdown-fade {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .filter-dropdown .dropdown-item {
            padding: 0.7rem 1.2rem;
            color: var(--text-primary);
            transition: all 0.2s ease;
            position: relative;
        }
        
        .filter-dropdown .dropdown-item:hover {
            background-color: rgba(115, 128, 236, 0.08);
            color: var(--chart-primary);
            transform: translateX(5px);
        }
        
        .filter-dropdown .dropdown-item i {
            margin-left: 0.5rem;
            width: 18px;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }
        
        .filter-dropdown .dropdown-item:hover i {
            color: var(--chart-primary);
        }
        
        .report-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .report-table th {
            font-weight: 600;
            color: var(--text-primary);
            background-color: rgba(246, 246, 249, 0.6);
            padding: 1rem 1.2rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        
        .report-table th:first-child {
            border-top-right-radius: 10px;
        }
        
        .report-table th:last-child {
            border-top-left-radius: 10px;
        }
        
        .report-table td {
            vertical-align: middle;
            padding: 1.2rem 1.2rem;
            border-top: 1px solid rgba(220, 225, 235, 0.5);
            transition: all 0.2s ease;
        }
        
        .report-table tr {
            transition: all 0.2s ease;
        }
        
        .report-table tr:hover {
            background-color: rgba(115, 128, 236, 0.04);
        }
        
        .report-table tr:hover td {
            transform: translateX(3px);
        }
        
        .table-status {
            padding: 0.4rem 0.9rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            display: inline-block;
        }
        
        .stock-indicator {
            width: 100%;
            height: 10px;
            background-color: rgba(220, 225, 235, 0.5);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .stock-level {
            height: 100%;
            border-radius: 8px;
            transition: width 1s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .stock-level.critical {
            background: linear-gradient(90deg, #ff7782, #ff5a67);
            box-shadow: 0 0 15px rgba(255, 119, 130, 0.5);
        }
        
        .stock-level.warning {
            background: linear-gradient(90deg, #ffbb55, #ffa922);
            box-shadow: 0 0 15px rgba(255, 187, 85, 0.5);
        }
        
        .stock-level.good {
            background: linear-gradient(90deg, #41f1b6, #2bd89e);
            box-shadow: 0 0 15px rgba(65, 241, 182, 0.5);
        }
        
        .nav-tabs {
            border-bottom: 2px solid rgba(220, 225, 235, 0.5);
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            color: var(--text-muted);
            padding: 0.8rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--chart-primary);
        }
        
        .nav-tabs .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 2px;
            background-color: var(--chart-primary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-tabs .nav-link:hover::after {
            width: 80%;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--chart-primary);
            border-bottom-color: var(--chart-primary);
            background-color: transparent;
        }
        
        .nav-tabs .nav-link.active::after {
            width: 100%;
        }
        
        .tab-content {
            padding-top: 1.5rem;
        }
        
        .tab-pane {
            animation: fadeIn 0.4s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .btn {
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--chart-primary), #566bf7);
            border: none;
            box-shadow: 0 4px 15px rgba(115, 128, 236, 0.2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a68e4, #7380ec);
            box-shadow: 0 6px 18px rgba(115, 128, 236, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-light {
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .btn-light:hover {
            background-color: #f8f9fa;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-outline-primary {
            color: var(--chart-primary);
            border-color: var(--chart-primary);
            background-color: transparent;
        }
        
        .btn-outline-primary:hover {
            color: white;
            background: linear-gradient(45deg, var(--chart-primary), #566bf7);
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(115, 128, 236, 0.2);
            transform: translateY(-2px);
        }
        
        .card-title {
            position: relative;
            padding-bottom: 0.8rem;
            margin-bottom: 1.2rem !important;
        }
        
        .card-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(45deg, var(--chart-primary), #566bf7);
            border-radius: 3px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(45deg, #363949, #566bf7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Print styles */
        @media print {
            .sidebar, .navbar, .no-print {
                display: none !important;
            }
            
            .content-wrapper {
                margin-right: 0 !important;
                padding: 0 !important;
            }
            
            .report-card {
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none !important;
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
                        <div class="col-xl-3 col-md-6 mb-4">
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
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i> 12.5% لە مانگی پێشوو
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Sales -->
                        <div class="col-xl-3 col-md-6 mb-4">
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
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i> 8.3% لە مانگی پێشوو
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Products Sold -->
                        <div class="col-xl-3 col-md-6 mb-4">
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
                                                <li><a class="dropdown-item" href="#">بینینی هەمووی</a></li>
                                                <li><a class="dropdown-item" href="#">زیاتر</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">کاڵای فرۆشراو</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalProductsSold); ?></h3>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i> 5.7% لە مانگی پێشوو
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Suppliers -->
                        <div class="col-xl-3 col-md-6 mb-4">
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
                                                <li><a class="dropdown-item" href="#">زیاتر</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <h6 class="stat-title">دابینکەرەکان</h6>
                                    <h3 class="stat-value"><?php echo number_format($totalSuppliers); ?></h3>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i> 2.1% لە مانگی پێشوو
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Section -->
                    <div class="row mb-4">
                        <!-- Sales Chart -->
                        <div class="col-xl-8 col-lg-12 mb-4">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0">فرۆشتن بەپێی مانگ</h5>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-filter me-1"></i> فلتەر
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#" data-filter="year"><i class="far fa-calendar-alt"></i> ئەمساڵ</a></li>
                                                <li><a class="dropdown-item" href="#" data-filter="6months"><i class="far fa-calendar-minus"></i> ٦ مانگی ڕابردوو</a></li>
                                                <li><a class="dropdown-item" href="#" data-filter="quarter"><i class="far fa-calendar-check"></i> ئەم چارەکە</a></li>
                                                <li><a class="dropdown-item" href="#" data-filter="month"><i class="far fa-calendar-day"></i> ئەم مانگە</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <div id="salesChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pie Chart -->
                        <div class="col-xl-4 col-lg-12 mb-4">
                            <div class="card report-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0">دابەشکردنی فرۆشتن</h5>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-filter me-1"></i> فلتەر
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#" data-chart-filter="category"><i class="fas fa-tags"></i> بەپێی جۆر</a></li>
                                                <li><a class="dropdown-item" href="#" data-chart-filter="product"><i class="fas fa-box"></i> بەپێی کاڵا</a></li>
                                                <li><a class="dropdown-item" href="#" data-chart-filter="customer"><i class="fas fa-users"></i> بەپێی کڕیار</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <div id="distributionChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
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
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- ApexCharts JS -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.40.0/dist/apexcharts.min.js"></script>
    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <!-- DateRangePicker -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="js/include-components.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize components
        loadNavbar();
        loadSidebar();
        
        // Initialize DateRangePicker
        $('#dateRangePicker').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            locale: {
                format: 'YYYY/MM/DD',
                applyLabel: 'بژاردن',
                cancelLabel: 'پاشگەزبوونەوە',
                customRangeLabel: 'ڕێکەوتی دیاریکراو',
                daysOfWeek: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ه'],
                monthNames: ['کانوونی دووەم', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران', 'تەمموز', 'ئاب', 'ئەیلوول', 'تشرینی یەکەم', 'تشرینی دووەم', 'کانوونی یەکەم'],
                firstDay: 6
            }
        });
        
        // Initialize Sales Chart
        const salesChartOptions = {
            series: [{
                name: 'فرۆشتن',
                data: [<?php echo implode(',', array_column($monthlySales, 'sales')); ?>]
            }],
            chart: {
                height: 350,
                type: 'bar',
                fontFamily: 'Rabar, sans-serif',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toLocaleString() + ' د.ع';
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ['#304758']
                }
            },
            xaxis: {
                categories: [<?php echo "'" . implode("','", array_column($monthlySales, 'month')) . "'"; ?>],
                position: 'bottom',
                labels: {
                    style: {
                        fontFamily: 'Rabar, sans-serif'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return val.toLocaleString() + ' د.ع';
                    },
                    style: {
                        fontFamily: 'Rabar, sans-serif'
                    }
                }
            },
            colors: ['#7380ec'],
            grid: {
                borderColor: '#f1f1f1',
                row: {
                    colors: ['#f8f9fa', 'transparent']
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString() + ' د.ع';
                    }
                }
            }
        };
        
        const salesChart = new ApexCharts(document.querySelector('#salesChart'), salesChartOptions);
        salesChart.render();
        
        // Initialize Distribution Chart
        const distributionChartOptions = {
            series: [25, 35, 20, 15, 5],
            chart: {
                width: '100%',
                height: 350,
                type: 'pie',
                fontFamily: 'Rabar, sans-serif'
            },
            labels: ['جۆری ١', 'جۆری ٢', 'جۆری ٣', 'جۆری ٤', 'جۆری ٥'],
            colors: ['#7380ec', '#41f1b6', '#ffbb55', '#ff7782', '#9a86f3'],
            legend: {
                position: 'bottom',
                fontFamily: 'Rabar, sans-serif'
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 300
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            dataLabels: {
                style: {
                    fontFamily: 'Rabar, sans-serif'
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + '%';
                    }
                }
            }
        };
        
        const distributionChart = new ApexCharts(document.querySelector('#distributionChart'), distributionChartOptions);
        distributionChart.render();
        
        // Filter Sales Chart by period
        document.querySelectorAll('[data-filter]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter');
                
                // This is where you would normally fetch new data based on the filter
                // For demonstration, we'll just show a different set of data
                let newData;
                let newCategories;
                
                switch (filter) {
                    case 'year':
                        newData = [42000, 53000, 75000, 68000, 88000, 95000, 82000, 75000, 93000, 86000, 91000, 79000];
                        newCategories = ['کانوون', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران', 'تەمموز', 'ئاب', 'ئەیلوول', 'تشرین ١', 'تشرین ٢', 'کانوون'];
                        break;
                    case '6months':
                        newData = [68000, 88000, 95000, 82000, 75000, 93000];
                        newCategories = ['ئایار', 'حوزەیران', 'تەمموز', 'ئاب', 'ئەیلوول', 'تشرین ١'];
                        break;
                    case 'quarter':
                        newData = [82000, 75000, 93000];
                        newCategories = ['ئاب', 'ئەیلوول', 'تشرین ١'];
                        break;
                    case 'month':
                        newData = [21000, 25000, 18000, 29000];
                        newCategories = ['هەفتەی ١', 'هەفتەی ٢', 'هەفتەی ٣', 'هەفتەی ٤'];
                        break;
                    default:
                        newData = [42000, 53000, 75000, 68000, 88000, 95000];
                        newCategories = ['کانوون', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران'];
                }
                
                salesChart.updateOptions({
                    xaxis: {
                        categories: newCategories
                    }
                });
                salesChart.updateSeries([{
                    name: 'فرۆشتن',
                    data: newData
                }]);
            });
        });
        
        // Filter Distribution Chart by category
        document.querySelectorAll('[data-chart-filter]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-chart-filter');
                
                // This is where you would normally fetch new data based on the filter
                let newData;
                let newLabels;
                
                switch (filter) {
                    case 'category':
                        newData = [25, 35, 20, 15, 5];
                        newLabels = ['جۆری ١', 'جۆری ٢', 'جۆری ٣', 'جۆری ٤', 'جۆری ٥'];
                        break;
                    case 'product':
                        newData = [30, 25, 20, 15, 10];
                        newLabels = ['کاڵای ١', 'کاڵای ٢', 'کاڵای ٣', 'کاڵای ٤', 'کاڵای ٥'];
                        break;
                    case 'customer':
                        newData = [40, 30, 15, 10, 5];
                        newLabels = ['کڕیاری ١', 'کڕیاری ٢', 'کڕیاری ٣', 'کڕیاری ٤', 'کڕیاری ٥'];
                        break;
                    default:
                        newData = [25, 35, 20, 15, 5];
                        newLabels = ['جۆری ١', 'جۆری ٢', 'جۆری ٣', 'جۆری ٤', 'جۆری ٥'];
                }
                
                distributionChart.updateOptions({
                    labels: newLabels
                });
                distributionChart.updateSeries(newData);
            });
        });
        
        // Function to load sidebar
        function loadSidebar() {
            const sidebarContainer = document.getElementById('sidebar-container');
            if (!sidebarContainer) return;
            
            fetch('components/sidebar.php')
                .then(response => response.text())
                .then(data => {
                    sidebarContainer.innerHTML = data;
                    
                    // Activate current page in sidebar
                    const currentPath = window.location.pathname;
                    const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1);
                    
                    const menuItems = document.querySelectorAll('.sidebar .sidebar-menu a');
                    menuItems.forEach(item => {
                        const href = item.getAttribute('href');
                        if (href === currentPage) {
                            item.classList.add('active');
                            
                            // If this is a submenu item, expand its parent
                            const parentLi = item.closest('.menu-item');
                            if (parentLi) {
                                const submenu = parentLi.querySelector('.submenu');
                                if (submenu) {
                                    submenu.style.display = 'block';
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error loading sidebar:', error));
        }
        
        // Function to load navbar
        function loadNavbar() {
            const navbarContainer = document.getElementById('navbar-container');
            if (!navbarContainer) return;
            
            fetch('components/navbar.php')
                .then(response => response.text())
                .then(data => {
                    navbarContainer.innerHTML = data;
                })
                .catch(error => console.error('Error loading navbar:', error));
        }
    });
    </script>
</body>
</html> 