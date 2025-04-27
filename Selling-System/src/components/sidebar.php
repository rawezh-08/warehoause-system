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
                <span>بەش</span>
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
                    <li><a href="addProduct.php">زیادکردنی کاڵا</a></li>
                    <li><a href="products.php">لیستی کاڵاکان</a></li>
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
                    <li><a href="addStaff.php">زیادکردنی هەژمار</a></li>
                    <li><a href="staff.php">لیستی هەژمارەکان</a></li>
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
                    <li><a href="addReceipt.php">زیادکردنی پسوڵە</a></li>
                    <li><a href="receiptList.php">لیستی پسوڵەکان</a></li>
                    <li><a href="draftAndWithdrawal.php">ڕەشنووسەکان و بەفیڕۆچوو</a></li>
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
                    <li><a href="employeePayment.php">زیادکردنی خەرجی</a></li>
                    <li><a href="expensesHistory.php">لیستی خەرجییەکان</a></li>
                </ul>
            </li>

            <!-- Debts -->
            <li class="menu-item">
                <a href="#deptsSubmenu" class="item-link" data-toggle="collapse">
                    <div class="icon-cont">
                        <img src="../../assets/icons/spending.svg" alt="">
                    </div>
                    <span>قەرزەکان</span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                <ul class="submenu collapse" id="deptsSubmenu">
                    <li><a href="customerProfile.php">قەرزی کڕیاڕ لە ئێمە</a></li>
                    <li><a href="suppliers.php">قەرزی ئێمە لە دابینکەر</a></li>
                </ul>
            </li>

            <!-- Reports -->
            <li class="menu-item">
                <a href="report.php" class="item-link">
                    <div class="icon-cont">
                        <img src="../../assets/icons/report.svg" alt="">
                    </div>
                    <span>ڕاپۆرتەکان</span>
                </a>
            </li>
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