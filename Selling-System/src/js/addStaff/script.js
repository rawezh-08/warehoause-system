$(document).ready(function() {
    // Load components (sidebar, navbar)
    loadComponents();
    
    // Initialize forms
    initializeEmployeeForm();
    initializeCustomerForm();
    initializeSupplierForm();
    
    // Setup tab click handlers
    setupTabHandlers();
});

/**
 * Safe toast function to show notifications
 */
function showToast(message, type) {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Configure toast based on type
        const config = {
            icon: type,
            title: message,
            rtl: true,
            customClass: {
                container: 'toast-container-rtl'
            }
        };

        // Show the toast
        Toast.fire(config);
    } else {
        console.log(type + ': ' + message);
    }
}

/**
 * Load the navbar and sidebar components
 */
function loadComponents() {
    try {
        $("#navbar-container").load("components/navbar.php");
        $("#sidebar-container").load("components/sidebar.php", function() {
            // Activate the current menu item
            activateSidebarItem();
        });
    } catch (error) {
        console.error("Error loading components:", error);
    }
}

/**
 * Activate the current sidebar menu item
 */
function activateSidebarItem() {
    // Find and activate the staff menu item
    const staffMenuItem = $('[href="#staffSubmenu"]');
    staffMenuItem.parent().addClass('active');
    staffMenuItem.attr('aria-expanded', 'true');
    
    // Show the submenu
    const submenu = $('#staffSubmenu');
    submenu.addClass('show');
    
    // Highlight the add staff menu item
    const addStaffItem = submenu.find('a[href="addStaff.php"]');
    addStaffItem.parent().addClass('active');
}

/**
 * Setup tab click handlers
 */
function setupTabHandlers() {
    // Tab click event
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const tabId = $(e.target).attr('id');
        activeTab = tabId.replace('-tab', '');
    });
}

/**
 * Initialize employee form
 */
function initializeEmployeeForm() {
    // Form validation
    const employeeForm = document.getElementById('employeeForm');
    if (employeeForm) {
        employeeForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!employeeForm.checkValidity()) {
                event.stopPropagation();
                employeeForm.classList.add('was-validated');
                return;
            }
            
            // Collect form data
            const formData = new FormData(employeeForm);
            const employeeData = Object.fromEntries(formData.entries());
            
            // Here you would typically make an AJAX call to submit the data
            // For demo purposes, we'll just show a success message
            Swal.fire({
                title: 'سەرکەوتوو بوو!',
                text: 'زانیاری کارمەند پاشەکەوت کرا',
                icon: 'success',
                confirmButtonText: 'باشە'
            }).then(() => {
                // Reset form
                employeeForm.reset();
                employeeForm.classList.remove('was-validated');
            });
        });
        
        // Reset button
        document.getElementById('resetEmployeeForm').addEventListener('click', function() {
            employeeForm.reset();
            employeeForm.classList.remove('was-validated');
            showToast('فۆرمەکە ڕیسێت کرا', 'info');
        });
    }
}

/**
 * Initialize customer form
 */
function initializeCustomerForm() {
    // Form validation
    const customerForm = document.getElementById('customerForm');
    if (customerForm) {
        customerForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!customerForm.checkValidity()) {
                event.stopPropagation();
                customerForm.classList.add('was-validated');
                return;
            }
            
            // Collect form data
            const formData = new FormData(customerForm);
            const customerData = Object.fromEntries(formData.entries());
            
            // Here you would typically make an AJAX call to submit the data
            // For demo purposes, we'll just show a success message
            Swal.fire({
                title: 'سەرکەوتوو بوو!',
                text: 'زانیاری کڕیار پاشەکەوت کرا',
                icon: 'success',
                confirmButtonText: 'باشە'
            }).then(() => {
                // Reset form
                customerForm.reset();
                customerForm.classList.remove('was-validated');
            });
        });
        
        // Reset button
        document.getElementById('resetCustomerForm').addEventListener('click', function() {
            customerForm.reset();
            customerForm.classList.remove('was-validated');
            showToast('فۆرمەکە ڕیسێت کرا', 'info');
        });
    }
}

/**
 * Initialize supplier form
 */
function initializeSupplierForm() {
    // Form validation
    const supplierForm = document.getElementById('supplierForm');
    if (supplierForm) {
        supplierForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!supplierForm.checkValidity()) {
                event.stopPropagation();
                supplierForm.classList.add('was-validated');
                return;
            }
            
            // Collect form data
            const formData = new FormData(supplierForm);
            const supplierData = Object.fromEntries(formData.entries());
            
            // Here you would typically make an AJAX call to submit the data
            // For demo purposes, we'll just show a success message
            Swal.fire({
                title: 'سەرکەوتوو بوو!',
                text: 'زانیاری دابینکەر پاشەکەوت کرا',
                icon: 'success',
                confirmButtonText: 'باشە'
            }).then(() => {
                // Reset form
                supplierForm.reset();
                supplierForm.classList.remove('was-validated');
            });
        });
        
        // Reset button
        document.getElementById('resetSupplierForm').addEventListener('click', function() {
            supplierForm.reset();
            supplierForm.classList.remove('was-validated');
            showToast('فۆرمەکە ڕیسێت کرا', 'info');
        });
    }
} 