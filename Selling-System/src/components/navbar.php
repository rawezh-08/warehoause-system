<?php
// Include the navbar logic file that contains all the PHP code
require_once '../includes/navbar_logic.php';
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
                    <li><a class="dropdown-item" href="../../../../logout.php">چوونە دەرەوە</a></li>
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