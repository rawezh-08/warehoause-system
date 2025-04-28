function loadSidebar() {
    fetch('/inventory/Selling-System/src/components/sidebar.php')
        .then(response => response.text())
        .then(html => {
            const sidebarContainer = document.getElementById('sidebar-container');
            if (sidebarContainer) {
                sidebarContainer.innerHTML = html;
                
                // Set active menu item based on current page
                const currentPath = window.location.pathname;
                const menuItems = document.querySelectorAll('.sidebar-menu a');
                
                menuItems.forEach(item => {
                    if (item.getAttribute('href') === currentPath) {
                        item.classList.add('active');
                        const parentLi = item.closest('.menu-item');
                        if (parentLi) {
                            parentLi.classList.add('open');
                        }
                    }
                });
                
                // Initialize sidebar events
                initSidebarEvents();
                // Initialize submenu functionality
                initSubmenuToggle();
            }
        })
        .catch(error => {
            console.error('Error loading sidebar:', error);
        });
}

function initSidebarEvents() {
    // Initialize sidebar toggle
    const toggleBtn = document.querySelector('.sidebar-toggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            document.getElementById('wrapper').classList.toggle('sidebar-collapsed');
            // For mobile
            document.body.classList.toggle('sidebar-active');
        });
    }
}

function initSubmenuToggle() {
    // Get all dropdown menu items
    const dropdownItems = document.querySelectorAll('.sidebar-menu .menu-item > a[href^="#"]');
    
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const submenuId = this.getAttribute('href');
            const submenu = document.querySelector(submenuId);
            
            if (submenu) {
                // Toggle current submenu
                submenu.classList.toggle('show');
                
                // Toggle dropdown icon
                const dropdownIcon = this.querySelector('.dropdown-icon');
                if (dropdownIcon) {
                    dropdownIcon.classList.toggle('rotate');
                }
            }
        });
    });
}

// Load sidebar when DOM is ready
document.addEventListener('DOMContentLoaded', loadSidebar);
