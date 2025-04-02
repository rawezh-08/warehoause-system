<?php
// You can add PHP logic here if needed
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-wrapper">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-box"></i>
                <span>ASHKAN</span>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <!-- Dashboard -->
            <li class="menu-item">
                <a href="index.php">
                    <i class="fas fa-home"></i>
                    <span>داشبۆرد</span>
                </a>
            </li>

            <!-- Products -->
            <li class="menu-item">
                <a href="#productsSubmenu">
                    <i class="fas fa-box"></i>
                    <span>کاڵاکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="productsSubmenu">
                    <li><a href="addProduct.php">زیادکردنی کاڵا</a></li>
                    <li><a href="products.php">لیستی کاڵاکان</a></li>
                    <li><a href="categories.php">جۆرەکان</a></li>
                </ul>
            </li>

            <!-- Staff -->
            <li class="menu-item">
                <a href="#staffSubmenu">
                    <i class="fas fa-users"></i>
                    <span>هاوکارەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="staffSubmenu">
                    <li><a href="addStaff.php">زیادکردنی هاوکار</a></li>
                    <li><a href="staff.php">لیستی هاوکارەکان</a></li>
                </ul>
            </li>

            <!-- Sales -->
            <li class="menu-item">
                <a href="#salesSubmenu">
                    <i class="fas fa-shopping-cart"></i>
                    <span>فرۆشتنەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="salesSubmenu">
                    <li><a href="addSale.php">فرۆشتنی نوێ</a></li>
                    <li><a href="sales.php">لیستی فرۆشتنەکان</a></li>
                </ul>
            </li>
            
            <!-- Expenses -->
            <li class="menu-item">
                <a href="#expensesSubmenu">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>مەسروفات</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="expensesSubmenu">
                    <li><a href="employeePayment.php">زیادکارنی مەسروفات</a></li>
                    <li><a href="expensesHistory.php">پیشاندانەوەی مەسروفات</a></li>
                   
                </ul>
            </li>

            <!-- Reports -->
            <li class="menu-item">
                <a href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>ڕاپۆرتەکان</span>
                </a>
            </li>

            <!-- Settings -->
            <li class="menu-item">
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>ڕێکخستنەکان</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Overlay for mobile -->
<div class="overlay"></div> 