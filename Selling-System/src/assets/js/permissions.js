/**
 * Permissions handling JavaScript
 * فایلی جاڤاسکریپت بۆ بەڕێوەبردنی دەسەڵاتەکان
 */

// Cache for user permissions
let userPermissionsCache = null;
let isAdmin = false;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize permission-related UI
    initPermissionUI();
    
    // Load user permissions
    loadUserPermissions();
});

/**
 * Initialize permission-related UI elements
 */
function initPermissionUI() {
    // Handle locked menu items
    setupLockedMenuItems();
}

/**
 * Setup event listeners for locked menu items
 */
function setupLockedMenuItems() {
    // Add click event listeners to locked menu items
    document.querySelectorAll('.locked-menu-item').forEach(item => {
        item.addEventListener('click', handleLockedItemClick);
    });

    // Also handle locked parent menu items (sections)
    document.querySelectorAll('.locked-item').forEach(item => {
        item.addEventListener('click', handleLockedItemClick);
    });
}

/**
 * Handle clicks on locked menu items
 * @param {Event} e - The click event
 */
function handleLockedItemClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Show a message explaining why the item is locked
    Swal.fire({
        icon: 'error',
        title: 'دەسەڵات نیە',
        text: 'ببورە، تۆ دەسەڵاتی بینینی ئەم بەشەت نیە.',
        confirmButtonText: 'باشە',
        confirmButtonColor: '#7d1aff',
        timer: 3000
    });
}

/**
 * Load user permissions from the server
 * This will cache the permissions for future checks
 */
function loadUserPermissions() {
    if (userPermissionsCache !== null) {
        return Promise.resolve(userPermissionsCache);
    }
    
    return new Promise((resolve, reject) => {
        $.ajax({
            url: '../../api/permissions/get_user_permissions.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    userPermissionsCache = response.data.permissions;
                    isAdmin = response.data.is_admin;
                    resolve(userPermissionsCache);
                } else {
                    console.error('Failed to load user permissions:', response.message);
                    reject(new Error(response.message));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading user permissions:', error);
                reject(new Error(error));
            }
        });
    });
}

/**
 * Check if user has a specific permission
 * Uses cached permissions if available, otherwise loads from server
 * 
 * @param {string} permissionCode - The permission code to check
 * @returns {Promise<boolean>} Promise resolving to true if user has permission
 */
function hasPermission(permissionCode) {
    if (isAdmin) {
        return Promise.resolve(true);
    }
    
    if (userPermissionsCache !== null) {
        return Promise.resolve(userPermissionsCache.includes(permissionCode));
    }
    
    return loadUserPermissions().then(permissions => {
        return permissions.includes(permissionCode);
    });
}

/**
 * Check if an element should be visible based on user permissions
 * Can be used to hide UI elements when user doesn't have permission
 * 
 * @param {string} permissionCode - The permission code to check
 * @param {function} callback - Callback function with result (true/false)
 */
function checkElementPermission(permissionCode, callback) {
    hasPermission(permissionCode).then(callback).catch(() => callback(false));
}

/**
 * Toggle visibility of elements based on permissions
 * Add data-permission attribute to HTML elements with permission code
 * Elements without permission will be hidden
 */
function applyPermissionsToUI() {
    document.querySelectorAll('[data-permission]').forEach(element => {
        const permissionCode = element.dataset.permission;
        
        checkElementPermission(permissionCode, hasAccess => {
            if (!hasAccess) {
                element.style.display = 'none';
            }
        });
    });
} 