<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Permission.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Create permission model
$permissionModel = new Permission($db);

// Get current user ID from session
$current_user_id = $_SESSION['user_id'] ?? 0;

// Helper function to check permission
function hasPermission($permission_code) {
    global $permissionModel, $current_user_id;
    if ($current_user_id) {
        return $permissionModel->userHasPermission($current_user_id, $permission_code);
    }
    return false;
}

// For admin users, we'll show everything
$is_admin = hasPermission('manage_accounts') && hasPermission('manage_roles');
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
            <!-- Products - requires view_products permission -->
            <?php if ($is_admin || hasPermission('view_products')): ?>
            <li class="menu-item">
                <a href="#productsSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/product.svg" alt="">
                    </div>
                    <span>کاڵاکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="productsSubmenu">
                    <?php if ($is_admin || hasPermission('add_product')): ?>
                    <li><a href="addProduct.php">زیادکردنی کاڵا</a></li>
                    <?php endif; ?>
                    <li><a href="products.php">لیستی کاڵاکان</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Staff - requires manage_accounts permission -->
            <?php if ($is_admin || hasPermission('manage_accounts')): ?>
            <li class="menu-item">
                <a href="#staffSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/accounts.svg" alt="">
                    </div>
                    <span>هەژمارەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="staffSubmenu">
                    <li><a href="addStaff.php">زیادکردنی هەژمار</a></li>
                    <li><a href="staff.php">لیستی هەژمارەکان</a></li>
                    <li><a href="manage_users.php">بەڕێوەبردنی بەکارهێنەران</a></li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Sales - requires view_sales or view_purchases permission -->
            <?php if ($is_admin || hasPermission('view_sales') || hasPermission('view_purchases')): ?>
            <li class="menu-item">
                <a href="#salesSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/owes.svg" alt="">
                    </div>
                    <span>پسوڵەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="salesSubmenu">
                    <?php if ($is_admin || hasPermission('add_sale')): ?>
                    <li><a href="addReceipt.php">زیادکردنی پسوڵە</a></li>
                    <?php endif; ?>
                    
                    <?php if ($is_admin || hasPermission('view_sales')): ?>
                    <li><a href="receiptList.php">پسووڵەکانی فرۆشتن</a></li>
                    <?php endif; ?>
                    
                    <?php if ($is_admin || hasPermission('view_purchases')): ?>
                    <li><a href="purchaseList.php">پسووڵەکانی کڕین</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Expenses - requires financial permissions -->
            <?php if ($is_admin || hasPermission('view_financial_reports')): ?>
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
            <?php endif; ?>

            <!-- Debts - requires customer or supplier permissions -->
            <?php if ($is_admin || hasPermission('view_customers') || hasPermission('view_suppliers')): ?>
            <li class="menu-item">
                <a href="#deptsSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/spending.svg" alt="">
                    </div>
                    <span>مامەڵەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="deptsSubmenu">
                    <?php if ($is_admin || hasPermission('view_customers')): ?>
                    <li><a href="customers.php">کڕیارەکان</a></li>
                    <?php endif; ?>
                    
                    <?php if ($is_admin || hasPermission('view_suppliers')): ?>
                    <li><a href="suppliers.php">دابینکەرەکان</a></li>
                    <?php endif; ?>
                    
                    <?php if ($is_admin || (hasPermission('view_customers') && hasPermission('view_suppliers'))): ?>
                    <li><a href="business_partners.php">کڕیار و دابینکەر</a></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Reports - requires view_reports permission -->
            <?php if ($is_admin || hasPermission('view_reports')): ?>
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