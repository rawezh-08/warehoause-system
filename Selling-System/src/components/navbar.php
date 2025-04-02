<?php
// Navbar Component for ASHKAN system
?>
<nav class="navbar" style="border-radius: 0px; margin: 0px;">
    <div class="container-fluid">
        <!-- Sidebar Toggle Button -->
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>


        <!-- Right navbar items -->
        <div class="ms-auto d-flex align-items-center">
            <!-- Notifications -->
            <div class="notifications-icon">
                <a href="#" id="notificationToggle">
                    <i class="fas fa-bell"></i>
                    <span class="badge rounded-pill">3</span>
                </a>
            </div>

            <!-- User Profile -->
            <div class="user-profile ms-3" >
                <img src="assets/img/profile.png" alt="User Avatar">
            </div>
        </div>
    </div>
</nav> 