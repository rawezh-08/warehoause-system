/**
 * Quick Access Functionality for ASHKAN Warehouse System
 * Provides enhanced animations and interactions for quick access buttons
 */

class QuickAccess {
    constructor() {
        this.quickAccessItems = document.querySelectorAll('.quick-access-item');
        this.activeItem = document.querySelector('.quick-access-icon.active')?.closest('.quick-access-item');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupRippleEffect();
        this.setupInitialState();
    }

    setupEventListeners() {
        if (this.quickAccessItems.length === 0) return;

        this.quickAccessItems.forEach(item => {
            item.addEventListener('click', (e) => this.handleItemClick(e, item));
        });
    }

    handleItemClick(e, item) {
        // Skip if it's the same item that is already active
        const clickedIcon = item.querySelector('.quick-access-icon');
        if (this.activeItem === item && !clickedIcon.classList.contains('bordered')) {
            return;
        }

        // Remove active class from all items
        this.quickAccessItems.forEach(btn => {
            const icon = btn.querySelector('.quick-access-icon');
            if (icon) icon.classList.remove('active');
        });

        // Add active class to clicked item if it's not the bordered (add) button
        if (clickedIcon && !clickedIcon.classList.contains('bordered')) {
            clickedIcon.classList.add('active');
            this.activeItem = item;
            this.animateActivation(clickedIcon);
        }

        // Special handling for the "add" button
        if (clickedIcon && clickedIcon.classList.contains('bordered')) {
            e.preventDefault();
            
            // Slight pulse animation before showing menu
            this.animateBorderedButton(clickedIcon);
            
            // Show quick add menu
            const options = [
                { icon: 'fa-file-invoice', text: 'فاتورە', color: '#4361ee' },
                { icon: 'fa-truck-loading', text: 'بەرهەم', color: '#3bc167' },
                { icon: 'fa-user-plus', text: 'کڕیار', color: '#f6ad55' },
                { icon: 'fa-tag', text: 'پۆل', color: '#e44a66' }
            ];
            
            this.showQuickAddMenu(clickedIcon, options);
        }
    }

    setupRippleEffect() {
        this.quickAccessItems.forEach(item => {
            item.addEventListener('mousedown', (e) => this.createRipple(e, item));
        });
    }

    createRipple(e, item) {
        const icon = item.querySelector('.quick-access-icon');
        if (!icon) return;

        const ripple = document.createElement('span');
        ripple.classList.add('ripple');

        icon.appendChild(ripple);

        const rect = icon.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = `${size}px`;

        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        // Remove ripple after animation completes
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    animateActivation(element) {
        // Add a subtle bounce effect
        element.style.animation = 'none';
        setTimeout(() => {
            element.style.animation = 'quickAccessBounce 0.5s ease';
        }, 10);
    }

    animateBorderedButton(element) {
        element.style.animation = 'none';
        setTimeout(() => {
            element.style.animation = 'quickAccessPulse 0.5s ease';
        }, 10);
    }

    setupInitialState() {
        // Ensure the first item is active if none are
        if (!this.activeItem && this.quickAccessItems.length > 0) {
            const firstItem = this.quickAccessItems[0];
            const icon = firstItem.querySelector('.quick-access-icon');
            if (icon && !icon.classList.contains('bordered')) {
                icon.classList.add('active');
                this.activeItem = firstItem;
            }
        }
    }

    showQuickAddMenu(targetElement, options) {
        // Remove any existing menus
        const existingMenu = document.querySelector('.quick-add-menu');
        if (existingMenu) {
            existingMenu.remove();
        }
        
        // Create the menu with enhanced animations
        const menu = document.createElement('div');
        menu.className = 'quick-add-menu';
        
        // Add options to the menu with staggered animation
        options.forEach((option, index) => {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'quick-add-item';
            item.style.transitionDelay = `${index * 0.05}s`;
            
            item.innerHTML = `
                <span class="quick-add-icon" style="color: ${option.color}">
                    <i class="fas ${option.icon}"></i>
                </span>
                <span class="quick-add-text">${option.text}</span>
            `;
            
            item.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Add a nice selection animation
                item.classList.add('selected');
                
                setTimeout(() => {
                    console.log(`Selected option: ${option.text}`);
                    this.closeMenuWithAnimation(menu);
                    
                    // Here you would navigate to the appropriate page
                    // or open a modal for the selected option
                }, 200);
            });
            
            menu.appendChild(item);
        });
        
        // Position the menu
        document.body.appendChild(menu);
        const rect = targetElement.getBoundingClientRect();
        
        // Adjust position based on screen size
        if (window.innerWidth <= 576) {
            // Mobile: bottom sheet style
            menu.style.bottom = '0';
            menu.style.left = '0';
            menu.style.right = '0';
        } else {
            // Desktop: dropdown style
            menu.style.top = `${rect.bottom + 10}px`;
            menu.style.left = `${rect.left - (menu.offsetWidth / 2) + (rect.width / 2)}px`;
            
            // Ensure menu doesn't go off-screen
            const menuRect = menu.getBoundingClientRect();
            if (menuRect.right > window.innerWidth) {
                menu.style.left = `${window.innerWidth - menuRect.width - 10}px`;
            }
            if (menuRect.left < 0) {
                menu.style.left = '10px';
            }
        }
        
        // Add backdrop for mobile
        if (window.innerWidth <= 576) {
            const backdrop = document.createElement('div');
            backdrop.className = 'quick-add-backdrop';
            document.body.appendChild(backdrop);
            
            backdrop.addEventListener('click', () => {
                this.closeMenuWithAnimation(menu);
                backdrop.remove();
            });
            
            setTimeout(() => {
                backdrop.classList.add('show');
            }, 10);
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && e.target !== targetElement) {
                const backdrop = document.querySelector('.quick-add-backdrop');
                if (backdrop) backdrop.remove();
                
                menu.classList.remove('show');
                setTimeout(() => {
                    menu.remove();
                }, 300);
                
                document.removeEventListener('click', closeMenu);
            }
        });
        
        // Escape key to close
        document.addEventListener('keydown', function closeOnEscape(e) {
            if (e.key === 'Escape') {
                const backdrop = document.querySelector('.quick-add-backdrop');
                if (backdrop) backdrop.remove();
                
                menu.classList.remove('show');
                setTimeout(() => {
                    menu.remove();
                }, 300);
                
                document.removeEventListener('keydown', closeOnEscape);
            }
        });
        
        // Add animation
        requestAnimationFrame(() => {
            menu.classList.add('show');
        });
    }
    
    closeMenuWithAnimation(menu) {
        menu.classList.remove('show');
        
        const backdrop = document.querySelector('.quick-add-backdrop');
        if (backdrop) {
            backdrop.classList.remove('show');
            setTimeout(() => {
                backdrop.remove();
            }, 300);
        }
        
        setTimeout(() => {
            menu.remove();
        }, 300);
    }
}

// Initialize Quick Access functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.quickAccess = new QuickAccess();
}); 