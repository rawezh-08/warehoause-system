<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../process/notification_logic.php';
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
    <title>ئاگادارکردنەوەکان - ASHKAN Warehouse</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/global.css">
    <style>
        .notification-card {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            transition: transform 0.2s;
            overflow: hidden;
        }
        
        .notification-card:hover {
            transform: translateY(-5px);
        }
        
        .notification-header {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            background-color: #f8f9fa;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            display: flex;
            align-items: center;
        }
        
        .notification-header i {
            font-size: 1.5rem;
            margin-left: 10px;
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .notification-body {
            padding: 15px;
        }
        
        .product-table {
            
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #dee2e6;
        }
        
        .product-table th, .product-table td {
        
            padding: 12px 15px;
            text-align: right;
            border: 1px solid #dee2e6;
        }
        
        .product-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .product-table thead tr {
            background-color: #f1f3f9;
            border-bottom: 2px solid #dee2e6;
        }
        
        .product-table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .product-table tbody tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .text-warning {
            color: #ffab00 !important;
        }
        
        .text-danger {
            color: #ff5252 !important;
        }
        
        .text-success {
            color: #4caf50 !important;
        }
        
        .text-primary {
            color: #536dfe !important;
        }
        
        .badge {
            font-size: 0.85rem;
            padding: 6px 10px;
            border-radius: 5px;
            display: inline-block;
            text-align: center;
            min-width: 65px;
        }
        
        .badge-warning {
            background-color: rgba(255, 171, 0, 0.2);
            color: #ff8f00;
        }
        
        .badge-danger {
            background-color: rgba(255, 82, 82, 0.2);
            color: #d32f2f;
        }
        
        .bg-success {
            background-color: rgba(76, 175, 80, 0.2) !important;
            color: #2e7d32 !important;
            border: 1px solid rgba(76, 175, 80, 0.4);
        }
        
        .bg-warning {
            background-color: rgba(255, 171, 0, 0.2) !important;
            color: #ff8f00 !important;
            border: 1px solid rgba(255, 171, 0, 0.4);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
            border-radius: 4px;
        }
        
        .btn-primary, .btn-info {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }
        
        .btn-primary:hover, .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        
        .section-title {
            margin: 30px 0 15px;
            font-size: 1.5rem;
            position: relative;
            padding-right: 15px;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 20px;
            background-color: #536dfe;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 0.5rem;
            }
            .notification-header h3 {
                font-size: 1rem;
            }
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
            <div class="container-fluid" style="margin-top: 80px;">
                <div class="row">
                    <div class="col-12">
                        <h1 class="mb-4">ئاگادارکردنەوەکان</h1>
                    </div>
                </div>

                <!-- Low Stock Section -->
                <?php if(count($lowStockItems) > 0): ?>
                <h2 class="section-title" id="low-stock">کاڵا کەم ماوەکان</h2>
                <div class="row">
                    <div class="col-12">
                        <div class="notification-card">
                            <div class="notification-header">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <h3><?php echo count($lowStockItems); ?> کاڵا لە بڕی خۆی کەمترە</h3>
                            </div>
                            <div class="notification-body">
                                <div class="table-responsive">
                                    <table class="product-table" >
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>ناوی کاڵا</th>
                                                <th>بڕی ئێستا</th>
                                                <th>کەمترین بڕی پێویست</th>
                                                <th>دۆخ</th>
                                                <th>کردار</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($lowStockItems as $index => $item): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><?php echo $item['min_quantity']; ?></td>
                                                <td>
                                                    <span class="badge badge-warning">بڕی کەمە</span>
                                                </td>
                                                <td>
                                                    <a href="addReceipt.php?product_id=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-plus-circle"></i> هێنانی کاڵا
                                                    </a>
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
                <?php endif; ?>

                <!-- Out of Stock Section -->
                <?php if(count($outOfStockItems) > 0): ?>
                <h2 class="section-title" id="out-of-stock">کاڵا نەماوەکان</h2>
                <div class="row">
                    <div class="col-12">
                        <div class="notification-card">
                            <div class="notification-header">
                                <i class="fas fa-exclamation-circle text-danger"></i>
                                <h3><?php echo count($outOfStockItems); ?> کاڵا نەماوە لە کۆگادا</h3>
                            </div>
                            <div class="notification-body">
                                <div class="table-responsive">
                                    <table class="product-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>ناوی کاڵا</th>
                                                <th>کۆد</th>
                                                <th>دۆخ</th>
                                                <th>کردار</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($outOfStockItems as $index => $item): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['code']); ?></td>
                                                <td>
                                                    <span class="badge badge-danger">نەماوە</span>
                                                </td>
                                                <td>
                                                    <a href="addReceipt.php?product_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-plus-circle"></i> هێنانی کاڵا
                                                    </a>
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
                <?php endif; ?>

                <!-- Today's Sales Section -->
                <?php if($todaySales['count'] > 0): ?>
                <h2 class="section-title" id="today-sales">فرۆشتنەکانی ئەمڕۆ</h2>
                <div class="row">
                    <div class="col-12">
                        <div class="notification-card">
                            <div class="notification-header">
                                <i class="fas fa-shopping-cart text-success"></i>
                                <h3>
                                    <?php echo $todaySales['count']; ?> فرۆشتن بە بڕی 
                                    <?php echo number_format($todaySales['total'] ?? 0, 0, '.', ','); ?> دینار
                                </h3>
                            </div>
                            <div class="notification-body">
                                <div class="table-responsive">
                                    <table class="product-table">
                                        <thead>
                                            <tr>
                                                <th>ناوی کڕیار</th>
                                                <th>ژمارەی فاکتور</th>
                                                <th>بڕی پارە</th>
                                                <th>جۆری فرۆشتن</th>
                                                <th>کردار</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($todaySalesDetails as $sale): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                                <td><?php echo number_format($sale['total'], 0, '.', ','); ?> دینار</td>
                                                <td>
                                                    <?php if($sale['payment_type'] == 'cash'): ?>
                                                    <span class="badge bg-success">نەقد</span>
                                                    <?php elseif($sale['payment_type'] == 'credit'): ?>
                                                    <span class="badge bg-warning">قەرز</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="receiptList.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> بینین
                                                    </a>
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
                <?php endif; ?>

                <!-- Today's Purchases Section -->
                <?php if($todayPurchases['count'] > 0): ?>
                <h2 class="section-title" id="today-purchases">کڕینەکانی ئەمڕۆ</h2>
                <div class="row">
                    <div class="col-12">
                        <div class="notification-card">
                            <div class="notification-header">
                                <i class="fas fa-truck text-primary"></i>
                                <h3>
                                    <?php echo $todayPurchases['count']; ?> کڕین بە بڕی 
                                    <?php echo number_format($todayPurchases['total'] ?? 0, 0, '.', ','); ?> دینار
                                </h3>
                            </div>
                            <div class="notification-body">
                                <div class="table-responsive">
                                    <table class="product-table">
                                        <thead>
                                            <tr>
                                                <th>ناوی دابینکەر</th>
                                                <th>ژمارەی فاکتور</th>
                                                <th>بڕی پارە</th>
                                                <th>جۆری کڕین</th>
                                                <th>کردار</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($todayPurchasesDetails as $purchase): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($purchase['supplier_name']); ?></td>
                                                <td><?php echo htmlspecialchars($purchase['invoice_number']); ?></td>
                                                <td><?php echo number_format($purchase['total'], 0, '.', ','); ?> دینار</td>
                                                <td>
                                                    <?php if($purchase['payment_type'] == 'cash'): ?>
                                                    <span class="badge bg-success">نەقد</span>
                                                    <?php elseif($purchase['payment_type'] == 'credit'): ?>
                                                    <span class="badge bg-warning">قەرز</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="receiptList.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> بینین
                                                    </a>
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
                <?php endif; ?>
                
                <?php if(!count($lowStockItems) && !count($outOfStockItems) && !$todaySales['count'] && !$todayPurchases['count']): ?>
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                        <h3 class="text-muted">هیچ ئاگادارکردنەوەیەک نییە</h3>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Include Components Script -->
    <script src="../../js/include-components.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to appropriate section if hash exists in URL
            if (window.location.hash) {
                const targetSection = document.querySelector(window.location.hash);
                if (targetSection) {
                    // Add a small delay to ensure the page is fully loaded
                    setTimeout(function() {
                        window.scrollTo({
                            top: targetSection.offsetTop - 100, // Offset for fixed header
                            behavior: 'smooth'
                        });
                    }, 300);
                }
            }
            
            // Any page-specific JavaScript can go here
        });
    </script>
</body>

</html> 