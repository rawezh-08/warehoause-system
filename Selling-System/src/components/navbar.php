<?php
// Navbar Component for ASHKAN system
require_once '../includes/auth.php';
require_once '../config/database.php';

// Connect to database
$db = new Database();
$conn = $db->getConnection();

// Get today's date
$today = date('Y-m-d');

// Get low stock items (inventory quantity less than minimum quantity)
$lowStockQuery = "SELECT p.name, i.quantity, p.min_quantity 
                 FROM inventory i 
                 JOIN products p ON i.product_id = p.id 
                 WHERE i.quantity <= p.min_quantity AND i.quantity > 0";
$lowStockStmt = $conn->prepare($lowStockQuery);
$lowStockStmt->execute();
$lowStockItems = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
$lowStockCount = count($lowStockItems);

// Get out of stock items (quantity = 0)
$outOfStockQuery = "SELECT COUNT(*) as count 
                   FROM products 
                   WHERE current_quantity = 0";
$outOfStockStmt = $conn->prepare($outOfStockQuery);
$outOfStockStmt->execute();
$outOfStockCount = $outOfStockStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get today's sales count
$salesQuery = "SELECT COUNT(DISTINCT s.id) as count, 
               SUM(si.total_price) as total 
               FROM sales s 
               JOIN sale_items si ON s.id = si.sale_id
               WHERE DATE(s.date) = :today";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->bindParam(':today', $today);
$salesStmt->execute();
$todaySales = $salesStmt->fetch(PDO::FETCH_ASSOC);

// Get today's purchases count
$purchasesQuery = "SELECT COUNT(DISTINCT p.id) as count, 
                  SUM(pi.total_price) as total 
                  FROM purchases p 
                  JOIN purchase_items pi ON p.id = pi.purchase_id
                  WHERE DATE(p.date) = :today";
$purchasesStmt = $conn->prepare($purchasesQuery);
$purchasesStmt->bindParam(':today', $today);
$purchasesStmt->execute();
$todayPurchases = $purchasesStmt->fetch(PDO::FETCH_ASSOC);

// Calculate total notifications
$totalNotifications = $lowStockCount + ($outOfStockCount > 0 ? 1 : 0) + ($todaySales['count'] > 0 ? 1 : 0) + ($todayPurchases['count'] > 0 ? 1 : 0);
?>
<link rel="stylesheet" href="../../css/shared/navbar.css">
<nav class="navbar" style="border-radius: 50px; margin: 8px; margin-top:10px; height: 80px;">
    <div class="container-fluid">
        <!-- Sidebar Toggle Button -->
     

        <!-- Brand/logo -->
        <!-- <a class="navbar-brand" href="index.php">
            <span class="navbar-logo">
                <i class="fas fa-box"></i>
            </span>
            <span class="navbar-title">ASHKAN</span>
        </a> -->

        <!-- Right navbar items -->
        <div class="ms-auto d-flex align-items-center" style="gap: 20px;">
            <!-- Notifications -->
            <div style="position: relative; padding: 8px;">
                <a href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="display: block; position: relative;">
                    <img src="../../assets/icons/notification.svg" alt="Notifications" width="28" height="28">
                    <?php if ($totalNotifications > 0): ?>
                    <span style="position: absolute; top: -10px; right: -10px; background-color: #dc3545; color: white; font-size: 11px; min-width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; border-radius: 50%; padding: 0 4px; font-weight: bold;">
                        <?php echo $totalNotifications; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width: 320px; max-height: 400px; overflow-y: auto;" aria-labelledby="notificationDropdown">
                    <div class="p-3 bg-light border-bottom">
                        <h6 class="m-0 text-center">ئاگادارکردنەوە</h6>
                    </div>
                    <div>
                        <?php if ($lowStockCount > 0): ?>
                        <div class="p-3 border-bottom" style="transition: background-color 0.2s;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                <span class="fw-bold"><?php echo $lowStockCount; ?> کاڵا بڕەکەی کەمە</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($outOfStockCount > 0): ?>
                        <div class="p-3 border-bottom" style="transition: background-color 0.2s;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                <span class="fw-bold"><?php echo $outOfStockCount; ?> کاڵا نەماوە لە کۆگادا</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($todaySales['count'] > 0): ?>
                        <div class="p-3 border-bottom" style="transition: background-color 0.2s;">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-shopping-cart text-success me-2"></i>
                                <span class="fw-bold">فرۆشتن لە ئەمڕۆدا</span>
                            </div>
                            <div>
                                <p class="mb-0 small"><?php echo $todaySales['count']; ?> فرۆشتن بە بڕی <?php echo number_format($todaySales['total'] ?? 0, 0, '.', ','); ?> دینار</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($todayPurchases['count'] > 0): ?>
                        <div class="p-3 border-bottom" style="transition: background-color 0.2s;">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-truck text-primary me-2"></i>
                                <span class="fw-bold">کڕین لە ئەمڕۆدا</span>
                            </div>
                            <div>
                                <p class="mb-0 small"><?php echo $todayPurchases['count']; ?> کڕین بە بڕی <?php echo number_format($todayPurchases['total'] ?? 0, 0, '.', ','); ?> دینار</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($totalNotifications == 0): ?>
                        <div class="p-3">
                            <p class="text-center mb-0">هیچ ئاگادارکردنەوەیەک نییە</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div>
                <img src="../../assets/img/profile.png" alt="User Avatar" class="dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer; width: 40px; height: 40px; border-radius: 50%;">
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="../../src/includes/logout.php">چوونە دەرەوە</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
/* Dropdown menu styling */
.dropdown-menu {
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

/* Hover effect for notification items */
.dropdown-menu .p-3:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

/* Responsive styling for mobile devices */
@media screen and (max-width: 768px) {
    .dropdown-menu {
        position: fixed !important;
        top: 90px !important;  /* Increased this value to move it lower */
        right: 10px !important;
        left: 10px !important;
        width: calc(100% - 20px) !important;
        max-height: calc(100vh - 100px) !important;
        transform: none !important;
        z-index: 1050 !important;
    }

    .navbar {
        margin: 4px !important;
        height: 70px !important;
    }

    .ms-auto {
        padding-right: 8px;
    }
}
</style>

<script>
    // Make sure Bootstrap JS is loaded for dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });

        // Initialize notification dropdown
        var notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationDropdown) {
            new bootstrap.Dropdown(notificationDropdown);
        }
    });
</script>