// Component Loading and Dynamic Includes
// For ASHKAN Warehouse Management System
document.addEventListener('DOMContentLoaded', function() {
    // Set base paths for use in components
    window.basePath = '../../';
    
    // Load components
    loadNavbarAndSidebar();
    
    // Initialize sidebar
    initSidebar();
    
    // Initialize the sidebar toggle button
    initSidebarToggle();
    
    // Initialize notifications panel (which is in index.php)
    initNotifications();
    
    // Fix CSS paths after components are loaded
    setTimeout(fixComponentPaths, 200);
});

/**
 * Fix paths in components by replacing relative paths with absolute paths
 */
function fixComponentPaths() {
    // Fix sidebar CSS
    const sidebarCSS = document.querySelector('link[href*="sidebar.css"]');
    if (sidebarCSS) {
        sidebarCSS.href = '../../css/shared/sidebar.css';
    }
    
    // Fix navbar CSS
    const navbarCSS = document.querySelector('link[href*="navbar.css"]');
    if (navbarCSS) {
        navbarCSS.href = '../../css/shared/navbar.css';
    }
    
    // Fix image paths in navbar
    const navbarImages = document.querySelectorAll('.navbar img[src]');
    navbarImages.forEach(img => {
        const src = img.getAttribute('src');
        if (src && src.includes('assets')) {
            if (src.includes('../assets')) {
                img.src = '../../assets' + src.split('assets')[1];
            } else if (src.includes('../../assets')) {
                img.src = '../../assets' + src.split('assets')[1];
            } else if (!src.includes('http')) {
                img.src = '../../' + src;
            }
        }
    });
    
    // Fix image paths in sidebar
    const sidebarImages = document.querySelectorAll('.sidebar img[src]');
    sidebarImages.forEach(img => {
        const src = img.getAttribute('src');
        if (src && src.includes('assets')) {
            if (src.includes('../assets')) {
                img.src = '../../assets' + src.split('assets')[1];
            } else if (src.includes('../../assets')) {
                img.src = '../../assets' + src.split('assets')[1];
            } else if (!src.includes('http')) {
                img.src = '../../' + src;
            }
        }
    });
    
    // Fix product image paths
    const productImages = document.querySelectorAll('img[src]');
    productImages.forEach(img => {
        const src = img.getAttribute('src');
        // Skip if it's already using our product_image.php endpoint
        if (src && src.includes('product_image.php')) {
            return;
        }
        // Fix product images from the upload directory
        if (src && (src.includes('.jpg') || src.includes('.png') || src.includes('.jpeg') || src.includes('.gif'))) {
            // If it's just a filename without a path
            if (!src.includes('/') && !src.includes('http')) {
                img.src = '../../uploads/products/' + src;
            }
            // If it's a numeric filename like 67f2ab56e219b_1743956822.jpg without proper path
            else if (src.match(/[0-9a-f]+_\d+\.(jpg|png|jpeg|gif)$/i) && !src.includes('/uploads/')) {
                img.src = '../../uploads/products/' + src.split('/').pop();
            }
        }
    });
}

/**
 * Load both navbar and sidebar components with error handling
 */
function loadNavbarAndSidebar() {
    // Determine base path for components
    const basePath = '../../';
    
    // Load navbar
    if (document.getElementById('navbar-container')) {
        fetch(basePath + 'components/navbar.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('پەیوەندی بە ناوبارەوە سەرکەوتوو نەبوو');
                }
                return response.text();
            })
            .then(data => {
                document.getElementById('navbar-container').innerHTML = data;
            })
            .catch(error => {
                console.error('هەڵە لە بارکردنی ناوبار:', error);
                document.getElementById('navbar-container').innerHTML = `
                    <div class="alert alert-danger m-3">
                        <strong>هەڵە!</strong> ناتوانرێت ناوبار باربکرێت.
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="loadNavbarAndSidebar()">
                            <i class="fas fa-sync-alt"></i> هەوڵدانەوە
                        </button>
                    </div>
                `;
            });
    }

    // Load sidebar
    if (document.getElementById('sidebar-container')) {
        fetch(basePath + 'components/sidebar.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('پەیوەندی بە سایدبارەوە سەرکەوتوو نەبوو');
                }
                return response.text();
            })
            .then(data => {
                document.getElementById('sidebar-container').innerHTML = data;
                
                // Initialize sidebar dropdowns after loading
                const dropdownItems = document.querySelectorAll('.sidebar-menu .menu-item > a');
                
                dropdownItems.forEach(item => {
                    if (item.getAttribute('href') && item.getAttribute('href').startsWith('#')) {
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
                                
                                // Store submenu state in localStorage
                                const submenuIdWithoutHash = submenuId.substring(1);
                                if (submenu.classList.contains('show')) {
                                    localStorage.setItem('submenu_' + submenuIdWithoutHash, 'open');
                                } else {
                                    localStorage.removeItem('submenu_' + submenuIdWithoutHash);
                                }
                            }
                        });
                    }
                });
                
                // Restore submenu state from localStorage
                restoreSubmenuState();
                
                // Set active menu based on current page
                setActiveMenuItem();
            })
            .catch(error => {
                console.error('هەڵە لە بارکردنی سایدبار:', error);
                document.getElementById('sidebar-container').innerHTML = `
                    <div class="alert alert-danger m-3">
                        <strong>هەڵە!</strong> ناتوانرێت سایدبار باربکرێت.
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="loadNavbarAndSidebar()">
                            <i class="fas fa-sync-alt"></i> هەوڵدانەوە
                        </button>
                    </div>
                `;
            });
    }
}

/**
 * Restore submenu state from localStorage
 */
function restoreSubmenuState() {
    // Get all submenus
    const submenus = document.querySelectorAll('.submenu');
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    let activeSubmenuFound = false;
    
    // First check if the current page is in a submenu
    // If so, we'll prioritize opening that submenu
    submenus.forEach(submenu => {
        const submenuLinks = submenu.querySelectorAll('a');
        submenuLinks.forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                submenu.classList.add('show');
                link.classList.add('active');
                
                // Also rotate the dropdown icon
                const parentLink = document.querySelector(`a[href="#${submenu.id}"]`);
                if (parentLink) {
                    const dropdownIcon = parentLink.querySelector('.dropdown-icon');
                    if (dropdownIcon) {
                        dropdownIcon.classList.add('rotate');
                    }
                    // Mark parent menu item as active
                    const parentItem = parentLink.closest('.menu-item');
                    if (parentItem) {
                        parentItem.classList.add('active');
                    }
                }
                
                // Store this state
                localStorage.setItem('submenu_' + submenu.id, 'open');
                localStorage.setItem('active_page', currentPage);
                
                activeSubmenuFound = true;
            }
        });
    });
    
    // If we didn't find the active page in a submenu,
    // restore any previously opened submenus from localStorage
    if (!activeSubmenuFound) {
        submenus.forEach(submenu => {
            const submenuId = submenu.id;
            const isOpen = localStorage.getItem('submenu_' + submenuId);
            
            if (isOpen === 'open') {
                submenu.classList.add('show');
                
                // Also rotate the dropdown icon
                const parentLink = document.querySelector(`a[href="#${submenuId}"]`);
                if (parentLink) {
                    const dropdownIcon = parentLink.querySelector('.dropdown-icon');
                    if (dropdownIcon) {
                        dropdownIcon.classList.add('rotate');
                    }
                }
            }
        });
    }
}

/**
 * Initialize sidebar menu expand/collapse
 */
function initSidebarMenu() {
    // Get all sidebar menu items with submenu
    const menuItems = document.querySelectorAll('.sidebar-menu .menu-item > a[href*="#"]');
    if (!menuItems.length) return;
    
    menuItems.forEach(item => {
        // Remove existing event listeners
        item.removeEventListener('click', toggleSubmenu);
        // Add new event listener
        item.addEventListener('click', toggleSubmenu);
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
            
            // If in submenu, expand parent and keep it expanded
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
                    
                    // Store this submenu state in localStorage to persist between page loads
                    localStorage.setItem('submenu_' + submenu.id, 'open');
                    localStorage.setItem('active_page', currentPage);
                    
                    // Remove the click event that would toggle the submenu
                    parentLink.removeEventListener('click', toggleSubmenu);
                    parentLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Keep submenu open when clicking the parent
                        submenu.classList.add('show');
                        if (dropdownIcon) {
                            dropdownIcon.classList.add('rotate');
                        }
                    });
                }
            }
        }
    });
}

// Separate function for toggling submenu
function toggleSubmenu(e) {
    e.preventDefault();
    const submenuId = this.getAttribute('href');
    const submenu = document.querySelector(submenuId);
    
    if (submenu) {
        submenu.classList.toggle('show');
        const dropdownIcon = this.querySelector('.dropdown-icon');
        if (dropdownIcon) {
            dropdownIcon.classList.toggle('rotate');
        }
    }
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
            // Reset body position when closing sidebar
            document.body.style.position = '';
            document.body.style.width = '';
        }
    });
    
    // Close sidebar on window resize if screen is large
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            document.body.classList.remove('sidebar-active');
            // Reset body position when closing sidebar
            document.body.style.position = '';
            document.body.style.width = '';
        }
    });
    
    // Close sidebar when clicking outside (for mobile and iPad)
    if (window.innerWidth <= 1024) {
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (sidebar && sidebarToggle) {
                const isClickInsideSidebar = sidebar.contains(e.target);
                const isClickOnToggleBtn = sidebarToggle.contains(e.target);
                
                if (!isClickInsideSidebar && !isClickOnToggleBtn && document.body.classList.contains('sidebar-active')) {
                    document.body.classList.remove('sidebar-active');
                    // Reset body position when closing sidebar
                    document.body.style.position = '';
                    document.body.style.width = '';
                }
            }
        });
    }
}

/**
 * Initialize sidebar toggle button
 */
function initSidebarToggle() {
    const sidebarToggle = document.createElement('button');
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.innerHTML = '<img src="../../assets/icons/menu.svg" alt="Menu" class="menu-icon toggle-open">';
    document.body.appendChild(sidebarToggle);

    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        document.body.classList.toggle('sidebar-active');
        
        // Check if wrapper element exists before trying to access its classList
        const wrapper = document.getElementById('wrapper');
        if (wrapper) {
            wrapper.classList.toggle('sidebar-collapsed');
        }
        
        // Create overlay if it doesn't exist
        let overlay = document.querySelector('.overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'overlay';
            document.body.appendChild(overlay);
            
            // Add click event to close sidebar when overlay is clicked
            overlay.addEventListener('click', function() {
                document.body.classList.remove('sidebar-active');
            });
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

// Make functions globally available
window.loadNavbarAndSidebar = loadNavbarAndSidebar; 