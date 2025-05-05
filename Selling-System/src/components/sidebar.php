<?php
// Include necessary files for permission checking
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user has permission without redirecting
function userHasPermission($permission_code) {
    // Admin always has permission
    if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        return true;
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Create database connection and permission model
    $database = new Database();
    $db = $database->getConnection();
    $permissionModel = new Permission($db);
    
    // Check permission
    return $permissionModel->userHasPermission($_SESSION['user_id'], $permission_code);
}

// Function to render menu link based on permission
function renderMenuLink($href, $text, $permission_code = null) {
    if ($permission_code === null || userHasPermission($permission_code)) {
        // User has permission - render normal link
        return '<a href="' . $href . '">' . $text . '</a>';
    } else {
        // User does not have permission - render locked link
        return '<span class="locked-menu-item">' . $text . ' <i class="fas fa-lock text-secondary"></i></span>';
    }
}
?>
<!-- Sidebar -->
<link rel="stylesheet" href="../../css/shared/sidebar.css">
<style>
    .locked-menu-item {
        color: #6c757d;
        cursor: not-allowed;
        display: block;
        padding: 0.5rem 1rem;
        opacity: 0.7;
    }
    .submenu li {
        position: relative;
    }
    .fas.fa-lock {
        font-size: 0.8em;
        margin-right: 5px;
    }
</style>
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
                    <li><?php echo renderMenuLink("addProduct.php", "زیادکردنی کاڵا", "manage_products"); ?></li>
                    <li><?php echo renderMenuLink("products.php", "لیستی کاڵاکان", "view_products"); ?></li>
                </ul>
            </li>

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
                    <li><?php echo renderMenuLink("addStaff.php", "زیادکردنی هەژمار", "manage_employees"); ?></li>
                    <li><?php echo renderMenuLink("staff.php", "لیستی هەژمارەکان", "view_employees"); ?></li>
                </ul>
            </li>

            <!-- Users -->
            <li class="menu-item">
                <a href="#usersSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/accounts.svg" alt="">
                    </div>
                    <span>بەکارهێنەران</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="usersSubmenu">
                    <li><?php echo renderMenuLink("manage_users.php", "بەڕێوەبردنی بەکارهێنەران", "manage_users"); ?></li>
                    <li><?php echo renderMenuLink("manage_roles.php", "بەڕێوەبردنی ڕۆڵەکان", "manage_roles"); ?></li>
                    <li><?php echo renderMenuLink("add_user.php", "زیادکردنی بەکارهێنەر", "manage_users"); ?></li>
                </ul>
            </li>

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
                    <li><?php echo renderMenuLink("addReceipt.php", "زیادکردنی پسوڵە", "manage_receipts"); ?></li>
                    <li><?php echo renderMenuLink("receiptList.php", "پسووڵەکانی فرۆشتن", "view_sales"); ?></li>
                    <li><?php echo renderMenuLink("purchaseList.php", "پسووڵەکانی کڕین", "view_purchases"); ?></li>
                </ul>
            </li>

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
                    <li><?php echo renderMenuLink("employeePayment.php", "زیادکردنی خەرجی", "manage_expenses"); ?></li>
                    <li><?php echo renderMenuLink("expensesHistory.php", "لیستی خەرجییەکان", "view_expenses"); ?></li>
                    <li><?php echo renderMenuLink("cash_management.php", "دەخیلە", "manage_cash"); ?></li>
                </ul>
            </li>

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
                    <li><?php echo renderMenuLink("customers.php", "کڕیارەکان", "manage_customers"); ?></li>
                    <li><?php echo renderMenuLink("suppliers.php", "دابینکەرەکان", "manage_suppliers"); ?></li>
                    <li><?php echo renderMenuLink("business_partners.php", "کڕیار و دابینکەر", "view_business_partners"); ?></li>
                </ul>
            </li>

            <!-- Reports -->
            <li class="menu-item">
                <?php if (userHasPermission("view_reports")): ?>
                <a href="report.php" class="item-link">
                    <div class="icon-cont">
                        <img src="../../assets/icons/report.svg" alt="">
                    </div>
                    <span>ڕاپۆرتەکان</span>
                </a>
                <?php else: ?>
                <span class="item-link locked-item">
                    <div class="icon-cont">
                        <img src="../../assets/icons/report.svg" alt="">
                    </div>
                    <span>ڕاپۆرتەکان</span>
                    <i class="fas fa-lock"></i>
                </span>
                <?php endif; ?>
            </li>
        </ul>
    </div>
</div>

<!-- Overlay for mobile -->
<div class="overlay"></div>

<!-- Add jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<!-- Permissions JS -->
<script src="../../assets/js/permissions.js"></script>

<!-- Add custom JavaScript for sidebar functionality -->
<script>
$(document).ready(function() {
    // Apply permissions to UI elements
    applyPermissionsToUI();
    
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