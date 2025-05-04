<?php
// Include files for permission checking
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Permission.php';

// Initialize permission check 
$hasPermission = [];

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create permission model
    $permissionModel = new Permission($db);
    
    // Check common permissions
    $hasPermission['view_products'] = $permissionModel->userHasPermission($user_id, 'view_products');
    $hasPermission['add_product'] = $permissionModel->userHasPermission($user_id, 'add_product');
    $hasPermission['manage_accounts'] = $permissionModel->userHasPermission($user_id, 'manage_accounts');
    $hasPermission['view_sales'] = $permissionModel->userHasPermission($user_id, 'view_sales');
    $hasPermission['add_sale'] = $permissionModel->userHasPermission($user_id, 'add_sale');
    $hasPermission['view_purchases'] = $permissionModel->userHasPermission($user_id, 'view_purchases');
    $hasPermission['view_customers'] = $permissionModel->userHasPermission($user_id, 'view_customers');
    $hasPermission['view_suppliers'] = $permissionModel->userHasPermission($user_id, 'view_suppliers');
    $hasPermission['view_reports'] = $permissionModel->userHasPermission($user_id, 'view_reports');
    $hasPermission['view_employees'] = $permissionModel->userHasPermission($user_id, 'view_employees');
} else if (isset($_SESSION['admin_id'])) {
    // Admin has all permissions
    $hasPermission['view_products'] = true;
    $hasPermission['add_product'] = true;
    $hasPermission['manage_accounts'] = true;
    $hasPermission['view_sales'] = true;
    $hasPermission['add_sale'] = true;
    $hasPermission['view_purchases'] = true;
    $hasPermission['view_customers'] = true;
    $hasPermission['view_suppliers'] = true;
    $hasPermission['view_reports'] = true;
    $hasPermission['view_employees'] = true;
}
?>
<!-- Sidebar -->
<link rel="stylesheet" href="../../css/shared/sidebar.css">
<div class="sidebar">
    <div class="sidebar-wrapper">
        <!-- Sidebar Header -->
        <div class="sidebar-header" onclick="window.location.href='../../Views/admin/dashboard.php'">
            <a href="dashboard.php" class="">
                <div class="dash-cont">
                    <img src="../../assets/icons/dashboard.svg" alt="" class="dash-icon">
                </div>
                <span>بەشی سەرەکی</span>
            </a>
        </div>

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <?php if ($hasPermission['view_products'] || $hasPermission['add_product']): ?>
            <!-- Products -->
            <li class="menu-item">
                <a href="#productsSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/product.svg" alt="">
                    </div>
                    <span>کاڵاکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="productsSubmenu">
                    <?php if ($hasPermission['add_product']): ?>
                    <li><a href="addProduct.php">زیادکردنی کاڵا</a></li>
                    <?php endif; ?>
                    <?php if ($hasPermission['view_products']): ?>
                    <li><a href="products.php">لیستی کاڵاکان</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($hasPermission['manage_accounts'] || $hasPermission['view_employees']): ?>
            <!-- Staff -->
            <li class="menu-item">
                <a href="#staffSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/accounts.svg" alt="">
                    </div>
                    <span>هەژمارەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="staffSubmenu">
                    <?php if ($hasPermission['manage_accounts']): ?>
                    <li><a href="manage_users.php">بەڕێوەبردنی بەکارهێنەران</a></li>
                    <?php endif; ?>
                    <?php if ($hasPermission['view_employees']): ?>
                    <li><a href="addStaff.php">زیادکردنی هەژمار</a></li>
                    <li><a href="staff.php">لیستی هەژمارەکان</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($hasPermission['view_sales'] || $hasPermission['add_sale'] || $hasPermission['view_purchases']): ?>
            <!-- Sales -->
            <li class="menu-item">
                <a href="#salesSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/owes.svg" alt="">
                    </div>
                    <span>پسوڵەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="salesSubmenu">
                    <?php if ($hasPermission['add_sale']): ?>
                    <li><a href="addReceipt.php">زیادکردنی پسوڵە</a></li>
                    <?php endif; ?>
                    <?php if ($hasPermission['view_sales']): ?>
                    <li><a href="receiptList.php">پسووڵەکانی فرۆشتن</a></li>
                    <?php endif; ?>
                    <?php if ($hasPermission['view_purchases']): ?>
                    <li><a href="purchaseList.php">پسووڵەکانی کڕین</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Expenses -->
            <li class="menu-item">
                <a href="#expensesSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/spending.svg" alt="">
                    </div>
                    <span>خەرجییەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="expensesSubmenu">
                    <li><a href="employeePayment.php">زیادکردنی خەرجی</a></li>
                    <li><a href="expensesHistory.php">لیستی خەرجییەکان</a></li>
                    <li><a href="cash_management.php">دەخیلە</a></li>
                </ul>
            </li>

            <?php if ($hasPermission['view_customers'] || $hasPermission['view_suppliers']): ?>
            <!-- Debts -->
            <li class="menu-item">
                <a href="#deptsSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/spending.svg" alt="">
                    </div>
                    <span>مامەڵەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="deptsSubmenu">
                    <?php if ($hasPermission['view_customers']): ?>
                    <li><a href="customers.php">کڕیارەکان</a></li>
                    <?php endif; ?>
                    <?php if ($hasPermission['view_suppliers']): ?>
                    <li><a href="suppliers.php">دابینکەرەکان</a></li>
                    <?php endif; ?>
                    <?php if ($hasPermission['view_customers'] && $hasPermission['view_suppliers']): ?>
                    <li><a href="business_partners.php">کڕیار و دابینکەر</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if ($hasPermission['view_reports']): ?>
            <!-- Reports -->
            <li class="menu-item">
                <a href="report.php" class="item-link">
                    <div class="icon-cont">
                        <img src="../../assets/icons/report.svg" alt="">
                    </div>
                    <span>ڕاپۆرتەکان</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Overlay for mobile -->
<div class="overlay"></div>

<!-- Add jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Add custom JavaScript for sidebar functionality -->
<script>
$(document).ready(function() {
    // Handle dropdown toggles
    $('.item-link[data-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        $('.submenu').not(target).collapse('hide');
        target.collapse('toggle');
        
        // Toggle active class
        $(this).parent().toggleClass('active');
    });

    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.menu-item').length) {
            $('.submenu').collapse('hide');
            $('.menu-item').removeClass('active');
        }
    });

    // Handle mobile menu
    $('.sidebar-toggle').on('click', function() {
        $('.sidebar').toggleClass('active');
        $('.overlay').toggleClass('active');
    });

    // Close sidebar when clicking overlay
    $('.overlay').on('click', function() {
        $('.sidebar').removeClass('active');
        $('.overlay').removeClass('active');
    });
});
</script> 