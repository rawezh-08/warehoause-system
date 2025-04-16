<?php
// Navbar Component for ASHKAN system
require_once '../includes/auth.php';
// session_start() removed as it's already called in auth.php
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
        <div class="ms-auto d-flex align-items-center">
            <!-- Notifications -->
            <div class="notifications-icon">
                <a href="#" id="notificationToggle">
                    <img src="../../assets/icons/notification.svg" alt="">
                    <span class="badge rounded-pill">3</span>
                </a>
            </div>

            <!-- User Profile -->
            <div class="user-profile ms-3 dropdown">
                <img src="../../assets/img/profile.png" alt="User Avatar" class="dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="../../src/includes/logout.php">چوونە دەرەوە</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
    // Make sure Bootstrap JS is loaded for dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });
</script>