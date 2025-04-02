// Component Loading and Dynamic Includes
// For ASHKAN Warehouse Management System

document.addEventListener('DOMContentLoaded', function() {
    // Load components
    loadComponent('navbar-container', 'components/navbar.php');
    loadComponent('sidebar-container', 'components/sidebar.php');
    
    // Initialize sidebar
    initSidebar();
    
    // Initialize notifications panel (which is in index.php)
    initNotifications();
});

/**
 * Load component into container
 * @param {string} containerId - ID of the container element
 * @param {string} componentPath - Path to the component file
 */
function loadComponent(containerId, componentPath) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn(`Container ${containerId} not found`);
        return;
    }
    
    fetch(componentPath)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to load component: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
            // After loading sidebar, initialize sidebar functionality
            if (containerId === 'sidebar-container') {
                initSidebarMenu();
            }
            // After loading navbar, initialize navbar functionality
            if (containerId === 'navbar-container') {
                initSidebarToggle(); // Ensure toggle is initialized
            }
        })
        .catch(error => {
            console.error('Error loading component:', error);
            container.innerHTML = `<div class="alert alert-danger">خطا لە بارکردنی پێکهاتە: ${componentPath}</div>`;
        });
}

/**
 * Initialize sidebar menu expand/collapse
 */
function initSidebarMenu() {
    // Get all sidebar menu items with submenu
    const menuItems = document.querySelectorAll('.sidebar-menu .menu-item > a[href*="#"]');
    if (!menuItems.length) return;
    
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Toggle submenu visibility
            const submenuId = this.getAttribute('href');
            const submenu = document.querySelector(submenuId);
            
            if (submenu) {
                submenu.classList.toggle('show');
                // Toggle dropdown icon rotation
                const dropdownIcon = this.querySelector('.dropdown-icon');
                if (dropdownIcon) {
                    dropdownIcon.classList.toggle('rotate');
                }
            }
        });
    });
    
    // Set active menu item based on current page
    setActiveMenuItem();
}

/**
 * Set active menu item based on current URL
 */
function setActiveMenuItem() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    
    // Find and set active menu item
    const menuLinks = document.querySelectorAll('.sidebar-menu a[href]');
    if (!menuLinks.length) return;
    
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        
        if (href === currentPage) {
            // Set active class
            link.classList.add('active');
            
            // If in submenu, expand parent
            const submenu = link.closest('.submenu');
            if (submenu) {
                submenu.classList.add('show');
                const parentLink = document.querySelector(`a[href="#${submenu.id}"]`);
                if (parentLink) {
                    const parentItem = parentLink.closest('.menu-item');
                    if (parentItem) {
                        parentItem.classList.add('active');
                    }
                    const dropdownIcon = parentLink.querySelector('.dropdown-icon');
                    if (dropdownIcon) {
                        dropdownIcon.classList.add('rotate');
                    }
                }
            }
        }
    });
}

/**
 * Initialize responsive sidebar behavior
 */
function initSidebar() {
    const overlay = document.querySelector('.overlay');
    
    // Create overlay if it doesn't exist
    if (!overlay) {
        const newOverlay = document.createElement('div');
        newOverlay.className = 'overlay';
        document.body.appendChild(newOverlay);
    }
    
    // Close sidebar when clicking overlay
    document.addEventListener('click', function(e) {
        if (e.target.matches('.overlay')) {
            document.body.classList.remove('sidebar-active');
        }
    });
    
    // Close sidebar on window resize if screen is large
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            document.body.classList.remove('sidebar-active');
        }
    });
}

/**
 * Initialize sidebar toggle button
 */
function initSidebarToggle() {
    const sidebarToggle = document.createElement('button');
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.innerHTML = '<img src="assets/icons/menu.svg" alt="Menu" class="menu-icon">';
    document.body.appendChild(sidebarToggle);

    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        document.body.classList.toggle('sidebar-active');
        document.getElementById('wrapper').classList.toggle('sidebar-collapsed');
        
        // Change icon between bars and X
       
        
        // Create overlay if it doesn't exist
        let overlay = document.querySelector('.overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'overlay';
            document.body.appendChild(overlay);
            
            // Add click event to close sidebar when overlay is clicked
          
        }
    });
}

/**
 * Initialize notification panel
 */
function initNotifications() {
    // Wait a moment to ensure the DOM is fully loaded with components
    setTimeout(() => {
        const notificationToggle = document.getElementById('notificationToggle');
        const notificationPanel = document.querySelector('.notification-panel');
        const closePanel = document.querySelector('.btn-close-panel');
        
        if (notificationToggle && notificationPanel) {
            notificationToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                notificationPanel.classList.toggle('show');
            });
        }
        
        if (closePanel && notificationPanel) {
            closePanel.addEventListener('click', function() {
                notificationPanel.classList.remove('show');
            });
            
            // Close panel when clicking outside
            document.addEventListener('click', function(e) {
                if (notificationPanel.classList.contains('show') && 
                    !notificationPanel.contains(e.target) && 
                    e.target !== notificationToggle && 
                    !notificationToggle.contains(e.target)) {
                    notificationPanel.classList.remove('show');
                }
            });
        }
    }, 500);
} 