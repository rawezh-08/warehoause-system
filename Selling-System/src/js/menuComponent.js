/**
 * Menu Component - Reusable menu functionality for the warehouse management system
 * This component handles the sidebar menu, submenus, and responsive behavior
 */

class MenuComponent {
    constructor() {
        // DOM Elements
        this.sidebar = document.getElementById('sidebar');
        this.content = document.getElementById('content');
        this.overlay = document.querySelector('.overlay');
        this.sidebarToggle = document.getElementById('sidebarToggle') || document.getElementById('sidebarCollapse');
        this.notificationToggle = document.getElementById('notificationToggle');
        this.notificationPanel = document.querySelector('.notification-panel');
        this.btnClosePanel = document.querySelector('.btn-close-panel');
        
        // Initialize
        this.init();
    }
    
    /**
     * Initialize the menu component
     */
    init() {
        // Setup event listeners
        this.setupEventListeners();
        
        // Setup enhanced submenus
        this.setupEnhancedSubmenus();
        
        // Initialize menu state based on current page
        this.initCurrentPageHighlight();
        
        // Initialize notification panel
        this.initNotificationPanel();
    }
    
    /**
     * Setup event listeners for sidebar toggle and overlay
     */
    setupEventListeners() {
        // Toggle sidebar
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', () => {
                this.toggleSidebar();
            });
        }
        
        // Close sidebar when clicking on overlay
        if (this.overlay) {
            this.overlay.addEventListener('click', () => {
                this.closeSidebar();
            });
        }
        
        // Close sidebar and notification panel on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeSidebar();
                if (this.notificationPanel) {
                    this.notificationPanel.classList.remove('active');
                }
            }
        });
        
        // Mobile search toggle if it exists
        const searchToggle = document.getElementById('searchToggle');
        const mobileSearch = document.querySelector('.mobile-search');
        
        if (searchToggle && mobileSearch) {
            searchToggle.addEventListener('click', (e) => {
                e.preventDefault();
                mobileSearch.classList.toggle('active');
                
                // Focus the search input when opened
                if (mobileSearch.classList.contains('active')) {
                    mobileSearch.querySelector('input').focus();
                }
            });
            
            // Close mobile search when clicking outside
            document.addEventListener('click', (e) => {
                if (!mobileSearch.contains(e.target) && !searchToggle.contains(e.target)) {
                    mobileSearch.classList.remove('active');
                }
            });
        }
    }
    
    /**
     * Set up enhanced submenu functionality
     */
    setupEnhancedSubmenus() {
        // Restore open submenus from session storage
        const openMenuIds = JSON.parse(sessionStorage.getItem('openMenuIds') || '[]');
        openMenuIds.forEach(id => {
            const menuItem = document.getElementById(id);
            if (menuItem) {
                menuItem.classList.add('open');
                const submenu = menuItem.querySelector('.submenu');
                if (submenu) {
                    submenu.style.display = 'block';
                }
            }
        });
        
        // Click event for dropdown links
        const menuItems = document.querySelectorAll('.sidebar-menu .menu-item > a');
        menuItems.forEach(link => {
            link.addEventListener('click', (e) => {
                const parent = link.parentElement;
                
                // Check if the link has a submenu
                const submenu = parent.querySelector('.submenu');
                if (submenu) {
                    e.preventDefault();
                    
                    // Add ripple effect
                    this.createRipple(e, link);
                    
                    // Close all other open submenus first
                    menuItems.forEach(otherLink => {
                        const otherParent = otherLink.parentElement;
                        if (otherParent !== parent && otherParent.classList.contains('open')) {
                            otherParent.classList.remove('open');
                            const otherSubmenu = otherParent.querySelector('.submenu');
                            if (otherSubmenu) {
                                this.hideSubmenu(otherParent);
                            }
                        }
                    });
                    
                    // Toggle the open class
                    parent.classList.toggle('open');
                    
                    // Toggle the submenu visibility with animation
                    if (parent.classList.contains('open')) {
                        this.showSubmenu(parent);
                    } else {
                        this.hideSubmenu(parent);
                    }
                    
                    // Save open menu state
                    this.saveOpenMenuState();
                } else {
                    // For regular menu items without submenu
                    this.createRipple(e, link);
                }
            });
        });
        
        // Click event for submenu links
        const submenuLinks = document.querySelectorAll('.sidebar-menu .submenu a');
        submenuLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                this.createRipple(e, link);
                
                // Remove active class from all submenu items
                document.querySelectorAll('.sidebar-menu .submenu a').forEach(a => {
                    a.classList.remove('active');
                });
                
                // Add active class to clicked submenu item
                link.classList.add('active');
                
                // If on mobile, close sidebar after a slight delay
                if (window.innerWidth < 768) {
                    setTimeout(() => {
                        this.closeSidebar();
                    }, 300);
                }
                
                // Allow the default navigation behavior
                // The link will navigate to its href
            });
        });
    }
    
    /**
     * Initialize menu state based on current page
     */
    initCurrentPageHighlight() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            const href = link.getAttribute('href');
            
            if (href === currentPage) {
                link.classList.add('active');
                
                // If submenu item is active, open its parent menu
                const submenu = link.closest('.submenu');
                if (submenu) {
                    const menuItem = submenu.closest('.menu-item');
                    menuItem.classList.add('open');
                    this.showSubmenu(menuItem);
                }
            }
        });
    }
    
    /**
     * Toggle sidebar open/closed state
     */
    toggleSidebar() {
        this.sidebar.classList.toggle('active');
        if (this.overlay) {
            this.overlay.classList.toggle('active');
            // Ensure overlay is displayed on iPad screens
            if (this.sidebar.classList.contains('active')) {
                this.overlay.style.display = 'block';
                // Force page redraw to ensure overlay shows on iPad
                setTimeout(() => {
                    this.overlay.style.opacity = '1';
                }, 10);
            } else {
                this.overlay.style.opacity = '0';
                // Delay hiding to allow fade out animation
                setTimeout(() => {
                    this.overlay.style.display = 'none';
                }, 300);
            }
        }
        document.body.classList.toggle('sidebar-active');
    }
    
    /**
     * Close sidebar
     */
    closeSidebar() {
        this.sidebar.classList.remove('active');
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }
        document.body.classList.remove('sidebar-active');
    }
    
    /**
     * Create ripple effect on element click
     */
    createRipple(e, element) {
        // Remove any existing ripple
        const existingRipple = element.querySelector('.ripple');
        if (existingRipple) {
            existingRipple.remove();
        }
        
        // Create ripple element
        const ripple = document.createElement('span');
        ripple.classList.add('ripple');
        
        // Get position relative to clicked element
        const rect = element.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Set size and position
        const size = Math.max(rect.width, rect.height) * 2;
        ripple.style.width = size + 'px';
        ripple.style.height = size + 'px';
        ripple.style.left = (x - size/2) + 'px';
        ripple.style.top = (y - size/2) + 'px';
        
        // Append to element
        element.appendChild(ripple);
        
        // Remove after animation completes
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
    
    /**
     * Show submenu with animation
     */
    showSubmenu(menuItem) {
        const submenu = menuItem.querySelector('.submenu');
        if (!submenu) return;
        
        submenu.style.display = 'block';
        submenu.style.opacity = '0';
        submenu.style.transform = 'translateY(-8px)';
        
        // Force layout recalculation
        submenu.offsetHeight;
        
        // Animate in
        submenu.style.maxHeight = '500px';
        submenu.style.opacity = '1';
        submenu.style.visibility = 'visible';
        submenu.style.transform = 'translateY(0)';
    }
    
    /**
     * Hide submenu with animation
     */
    hideSubmenu(menuItem) {
        const submenu = menuItem.querySelector('.submenu');
        if (!submenu) return;
        
        // Animate out
        submenu.style.maxHeight = '0';
        submenu.style.opacity = '0';
        submenu.style.transform = 'translateY(-8px)';
        
        // Hide after transition
        setTimeout(() => {
            if (!menuItem.classList.contains('open')) {
                submenu.style.display = 'none';
            }
        }, 350);
    }
    
    /**
     * Save open menu state to session storage
     */
    saveOpenMenuState() {
        const openMenuIds = [];
        document.querySelectorAll('.sidebar-menu .menu-item.open').forEach(item => {
            const id = item.getAttribute('id');
            if (id) {
                openMenuIds.push(id);
            }
        });
        sessionStorage.setItem('openMenuIds', JSON.stringify(openMenuIds));
    }
    
    /**
     * Initialize notification panel functionality
     */
    initNotificationPanel() {
        if (this.notificationToggle && this.notificationPanel) {
            this.notificationToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.notificationPanel.classList.toggle('active');
            });
            
            // Close button in notification panel
            if (this.btnClosePanel) {
                this.btnClosePanel.addEventListener('click', () => {
                    this.notificationPanel.classList.remove('active');
                });
            }
            
            // Mark notifications as read
            const notifications = document.querySelectorAll('.notification-item');
            notifications.forEach(notification => {
                notification.addEventListener('click', () => {
                    notification.classList.remove('unread');
                    this.updateNotificationBadge();
                });
            });
            
            // Update notification badge count
            this.updateNotificationBadge();
            
            // Close notification panel when clicking outside
            document.addEventListener('click', (e) => {
                if (this.notificationPanel.classList.contains('active') &&
                    !this.notificationPanel.contains(e.target) && 
                    e.target !== this.notificationToggle) {
                    this.notificationPanel.classList.remove('active');
                }
            });
        }
    }
    
    /**
     * Update notification badge count
     */
    updateNotificationBadge() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        const badge = document.querySelector('.notification-badge');
        
        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
}

// Initialize the menu component when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Create a global instance of the menu component
    window.menuComponent = new MenuComponent();
}); 