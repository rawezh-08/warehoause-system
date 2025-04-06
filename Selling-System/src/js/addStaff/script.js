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
    // Get form elements
    const employeeForm = document.getElementById('employeeForm');
    const resetEmployeeForm = document.getElementById('resetEmployeeForm');
    
    // Initialize form validation
    if (employeeForm) {
        employeeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Add employee form submission handler
            if (!employeeForm.checkValidity()) {
                e.stopPropagation();
                employeeForm.classList.add('was-validated');
                return;
            }
            
            // Show loading state
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'زیادکردنی کارمەند بەردەوامە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Create FormData object
            const formData = new FormData(employeeForm);
            
            // Send form data using fetch API
            fetch('process/add_employee.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        employeeForm.reset();
                        employeeForm.classList.remove('was-validated');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    confirmButtonText: 'باشە'
                });
            });
        });
    }
    
    // Reset form buttons
    if (resetEmployeeForm) {
        resetEmployeeForm.addEventListener('click', function() {
            employeeForm.reset();
            employeeForm.classList.remove('was-validated');
        });
    }
}

/**
 * Initialize customer form
 */
function initializeCustomerForm() {
    // Get form elements
    const customerForm = document.getElementById('customerForm');
    const resetCustomerForm = document.getElementById('resetCustomerForm');
    
    // Initialize form validation
    if (customerForm) {
        // Phone number validation function
        function validatePhoneNumber(phone) {
            return /^07\d{9}$/.test(phone);
        }
        
        // Add input validation on blur
        const phone1Input = document.getElementById('phone1');
        if (phone1Input) {
            phone1Input.addEventListener('blur', function() {
                if (this.value && !validatePhoneNumber(this.value)) {
                    this.classList.add('is-invalid');
                    this.nextElementSibling.textContent = 'ژمارە مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت';
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }
        
        const phone2Input = document.getElementById('phone2');
        if (phone2Input) {
            phone2Input.addEventListener('blur', function() {
                if (this.value && !validatePhoneNumber(this.value)) {
                    this.classList.add('is-invalid');
                    if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.textContent = 'ژمارە مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت';
                        this.parentNode.appendChild(feedback);
                    }
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }
        
        const guarantorPhoneInput = document.getElementById('guarantorPhone');
        if (guarantorPhoneInput) {
            guarantorPhoneInput.addEventListener('blur', function() {
                if (this.value && !validatePhoneNumber(this.value)) {
                    this.classList.add('is-invalid');
                    if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.textContent = 'ژمارە مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت';
                        this.parentNode.appendChild(feedback);
                    }
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }
        
        customerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!customerForm.checkValidity()) {
                e.stopPropagation();
                customerForm.classList.add('was-validated');
                return;
            }
            
            // Show loading state
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'زیادکردنی کڕیار بەردەوامە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Clean number inputs (remove commas)
            const debitInput = document.getElementById('debitOnBusiness');
            if (debitInput && debitInput.value) {
                debitInput.value = debitInput.value.replace(/,/g, '');
            }
            
            // Create FormData object
            const formData = new FormData(customerForm);
            
            // Send form data using fetch API
            fetch(customerForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        customerForm.reset();
                        customerForm.classList.remove('was-validated');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    confirmButtonText: 'باشە'
                });
            });
        });
    }
    
    // Reset form buttons
    if (resetCustomerForm) {
        resetCustomerForm.addEventListener('click', function() {
            customerForm.reset();
            customerForm.classList.remove('was-validated');
        });
    }
}

/**
 * Initialize supplier form
 */
function initializeSupplierForm() {
    // Get form elements
    const supplierForm = document.getElementById('supplierForm');
    const resetSupplierForm = document.getElementById('resetSupplierForm');
    
    // Initialize form validation
    if (supplierForm) {
        supplierForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!supplierForm.checkValidity()) {
                e.stopPropagation();
                supplierForm.classList.add('was-validated');
                return;
            }
            
            // Show loading state
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'زیادکردنی دابینکەر بەردەوامە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Clean number inputs (remove commas)
            const debtInput = document.getElementById('debt_on_myself');
            if (debtInput && debtInput.value) {
                debtInput.value = debtInput.value.replace(/,/g, '');
            }
            
            // Create FormData object
            const formData = new FormData(supplierForm);
            
            // Send form data using fetch API
            fetch(supplierForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        supplierForm.reset();
                        supplierForm.classList.remove('was-validated');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    confirmButtonText: 'باشە'
                });
            });
        });
    }
    
    // Reset form buttons
    if (resetSupplierForm) {
        resetSupplierForm.addEventListener('click', function() {
            supplierForm.reset();
            supplierForm.classList.remove('was-validated');
        });
    }
}

// Function to format numbers with commas
function formatNumber(input) {
    // Remove all non-digit characters
    let value = input.value.replace(/[^\d]/g, '');
    
    // Add commas for thousands
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    // Update the input value
    input.value = value;
} 