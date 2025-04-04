<?php
// You can add PHP logic here if needed
?>
<!-- Sidebar -->
<link rel="stylesheet" href="css/shared/sidebar.css">
<div class="sidebar">
    <div class="sidebar-wrapper">
        <!-- Sidebar Header -->
        <div class="sidebar-header" onclick="window.location.href='index.php'">
                       
        <a href="index.php" class="">
                    <div class="icon-cont">
                        <img src="assets/icons/Dashboard.svg" alt="" class="dash-icon">
                    </div>
                    <span>بەشی سەرەکی</span>
                </a>
        </div>

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <!-- Dashboard -->
           

            <!-- Products -->
            <li class="menu-item">
                <a href="#productsSubmenu" class="item-link">
                <div class="icon-cont">
                        <img src="assets/icons/product.svg" alt="">
                    </div>                    <span>کاڵاکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="productsSubmenu">
                    <li><a href="addProduct.php">زیادکردنی کاڵا</a></li>
                    <li><a href="products.php">لیستی کاڵاکان</a></li>
                </ul>
            </li>

            <!-- Staff -->
            <li class="menu-item">
                <a href="#staffSubmenu" class="item-link">
                <div class="icon-cont">
                        <img src="assets/icons/accounts.svg" alt="">
                    </div> 
                    <span>هەژمارەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="staffSubmenu">
                    <li><a href="addStaff.php">زیادکردنی هەژمار</a></li>
                    <li><a href="staff.php">لیستی هەژمارەکان</a></li>
                </ul>
            </li>

            <!-- Sales -->
            <li class="menu-item" >
                <a href="#salesSubmenu" class="item-link">
                <div class="icon-cont">
                        <img src="assets/icons/owes.svg" alt="">
                    </div>                    <span>پسوڵەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="salesSubmenu">
                    <li><a href="addReceipt.php" > زیادکردنی پسوڵە</a></li>
                    <li><a href="sales.php">لیستی پسوڵەکان</a></li>
                </ul>
            </li>
            
            <!-- Expenses -->
            <li class="menu-item">
                <a href="#expensesSubmenu" class="item-link">
                <div class="icon-cont">
                        <img src="assets/icons/spending.svg" alt="">
                    </div> 
                    <span>خەرجییەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="expensesSubmenu">
                    <li><a href="employeePayment.php"> زیادکردنی خەرجی</a></li>
                    <li><a href="expensesHistory.php">لیستی خەرجییەکان</a></li>
                   
                </ul>
            </li>

            <!-- Reports -->
            <li class="menu-item">
                <a href="reports.php" class="item-link">
                <div class="icon-cont">
                        <img src="assets/icons/report.svg" alt="">
                    </div>                    <span>ڕاپۆرتەکان</span>
                </a>
            </li>

            <!-- Settings -->
            <li class="menu-item">
                <a href="settings.php" class="item-link">
                <div class="icon-cont">
                        <img src="assets/icons/balance.svg" alt="">
                    </div>                    <span>باڵانسەکان</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Overlay for mobile -->
<div class="overlay"></div> 