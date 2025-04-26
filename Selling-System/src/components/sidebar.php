<?php
// You can add PHP logic here if needed
?>
<!-- Sidebar -->
<link rel="stylesheet" href="../../css/shared/sidebar.css">
<div class="sidebar">
    <div class="sidebar-wrapper">
        <!-- Sidebar Header -->
                    <div class="sidebar-header" onclick="window.location.href='../../views/admin/dashboard.php'">
                       
        <a href="dashboard.php" class="">
                    <div class="dash-cont">
                        <img src="../../assets/icons/dashboard.svg" alt="" class="dash-icon">
                    </div>
                    <span>بەشی سەرەتا</span>
                </a>
        </div>

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <!-- Dashboard -->
           

            <!-- Products -->
            <li class="menu-item">
                <a href="#productsSubmenu" class="item-link">
                <div class="icon-cont">
                        <img src="../../assets/icons/product.svg" alt="">
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
                        <img src="../../assets/icons/accounts.svg" alt="">
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
                        <img src="../../assets/icons/owes.svg" alt="">
                    </div>                    <span>پسوڵەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="salesSubmenu">
                    <li><a href="addReceipt.php" > زیادکردنی پسوڵە</a></li>
        
                    <li><a href="receiptList.php">لیستی پسوڵەکان</a></li>
                    <li><a href="draftAndWithdrawal.php">ڕەشنووسەکان و بەفیڕۆچوو</a></li>
                </ul>
            </li>
            
            <!-- Expenses -->
            <li class="menu-item">
                <a href="#expensesSubmenu" class="item-link">
                <div class="icon-cont">
                        <img src="../../assets/icons/spending.svg" alt="">
                    </div> 
                    <span>خەرجییەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="expensesSubmenu">
                    <li><a href="employeePayment.php"> زیادکردنی خەرجی</a></li>
                    <li><a href="expensesHistory.php">لیستی خەرجییەکان</a></li>
                   
                </ul>
            </li>
             <!-- Expenses -->
             <li class="menu-item">
                <a href="#deptsSubmenu" class="item-link">
                <div class="icon-cont">
                        <img src="../../assets/icons/spending.svg" alt="">
                    </div> 
                    <span>قەرزەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="deptsSubmenu">
                    <li><a href="customerProfile.php">قەرزی کڕیاڕ لە ئێمە</a></li>
                    <li><a href="suppliers.php">قەرزی ئێمە لە دابینکەر</a></li>
                   
                </ul>
            </li>


            <!-- Reports -->
         

            <!-- Balances 
            <li class="menu-item">
                <a href="#balancesSubmenu" class="item-link">
                <div class="icon-cont">
                        <img src="../../assets/icons/balance.svg" alt="">
                    </div>                    
                    <span>باڵانسەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu" id="balancesSubmenu">
                    <li><a href="bank.php">باڵانسی دابینکەرەکان</a></li>
                    <li><a href="customerBalances.php">باڵانسی کڕیارەکان</a></li>
                </ul>
            </li>
            -->
            <li class="menu-item">
                <a href="report.php" class="item-link">
                <div class="icon-cont">
                        <img src="../../assets/icons/report.svg" alt="">
                    </div>                    <span>ڕاپۆرتەکان</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Overlay for mobile -->
<div class="overlay"></div> 