$(document).ready(function() {
    // Load components (sidebar, navbar)
    loadComponents();
    
    // Initialize forms
    initializeEmployeeForm();
    initializeCustomerForm();
    initializeSupplierForm();
    
    // Setup tab click handlers
    setupTabHandlers();
    
    // Fetch employees if on staff page
    if (window.location.pathname.includes('staff.php')) {
        fetchEmployees();
    }
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
        // Set up salary input with formatter
        const salaryInput = document.getElementById('salary');
        if (salaryInput) {
            salaryInput.addEventListener('input', function() {
                formatNumber(this);
            });
        }

        // Add phone number validation
        const phoneInput = document.getElementById('employeePhone');
        if (phoneInput) {
            // Validate on input
            phoneInput.addEventListener('input', function() {
                handlePhoneInput(this);
            });
            
            // Validate on blur
            phoneInput.addEventListener('blur', function() {
                handlePhoneInput(this);
            });
        }

        employeeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            // Validate phone number format
            if (phoneInput && !validatePhoneNumber(phoneInput.value)) {
                phoneInput.classList.add('is-invalid');
                return;
            }

            // Clean salary input (remove commas)
            if (salaryInput) {
                salaryInput.value = salaryInput.value.replace(/,/g, '');
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

            // Submit form using fetch
            fetch('../../process/add_employee.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو!',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Reset form
                        employeeForm.reset();
                        employeeForm.classList.remove('was-validated');
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی زانیاریەکان',
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
    // Store cursor position
    let cursorPos = input.selectionStart;
    let oldLength = input.value.length;
    
    // Remove all non-digit characters
    let value = input.value.replace(/[^\d]/g, '');
    
    // Add commas for thousands
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    // Update the input value
    input.value = value;
    
    // Adjust cursor position based on length change
    let newLength = input.value.length;
    cursorPos = cursorPos + (newLength - oldLength);
    
    // Ensure cursor position is valid
    cursorPos = Math.max(0, Math.min(cursorPos, input.value.length));
    
    // Restore cursor position
    input.setSelectionRange(cursorPos, cursorPos);
}

// Function to validate phone number format
function validatePhoneNumber(phone) {
    return /^07\d{9}$/.test(phone);
}

// Function to handle phone number input
function handlePhoneInput(input) {
    // Remove any non-digit characters
    let value = input.value.replace(/\D/g, '');
    
    // Limit to 11 digits
    value = value.substring(0, 11);
    
    // Update input value
    input.value = value;
    
    // Validate and show feedback
    if (value.length > 0) {
        if (value.length < 11 || !value.startsWith('07')) {
            input.classList.add('is-invalid');
            input.nextElementSibling.textContent = 'ژمارە مۆبایل دەبێت بە 07 دەست پێبکات و 11 ژمارە بێت';
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        }
    } else {
        input.classList.remove('is-invalid', 'is-valid');
    }
}

// Function to get URL parameter by name
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

$(document).ready(function() {
    // Check if tab parameter exists in URL
    var tabParam = getUrlParameter('tab');
    
    // If tab parameter exists, activate the corresponding tab
    if (tabParam) {
        // Find the tab button and activate it
        $('#' + tabParam + '-tab').tab('show');
    }
    
    // Apply formatting to number inputs
    const debitOnBusinessInput = document.getElementById('debitOnBusiness');
    if (debitOnBusinessInput) {
        debitOnBusinessInput.addEventListener('input', function() {
            formatNumber(this);
        });
    }
    
    const debtOnMyselfInput = document.getElementById('debt_on_myself');
    if (debtOnMyselfInput) {
        debtOnMyselfInput.addEventListener('input', function() {
            formatNumber(this);
        });
    }
});

// Format number with commas
function formatNumber(input) {
    // Remove all non-digit characters
    let value = input.value.replace(/[^\d]/g, '');
    
    // Add commas for thousands
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    // Update the input value
    input.value = value;
}

       // Select the appropriate tab based on URL parameter
       document.addEventListener('DOMContentLoaded', function() {
        // Get tab parameter from URL
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        // If tab parameter exists, activate the corresponding tab
        if (tabParam) {
            // Find the tab button
            const tabButton = document.getElementById(tabParam + '-tab');
            if (tabButton) {
                // Create a new Bootstrap Tab instance and show it
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        }
    });

// Function to fetch and display employees
function fetchEmployees() {
    // Show loading state
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        text: 'زانیاری کارمەندان بار دەکرێت',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch employees from server
    fetch('../../process/get_employees.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide loading
                Swal.close();
                
                // Get table body
                const tbody = document.querySelector('#employeesTable tbody');
                if (!tbody) return;
                
                // Clear existing rows
                tbody.innerHTML = '';
                
                // Add each employee to table
                data.employees.forEach((employee, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${employee.name}</td>
                        <td>${employee.phone}</td>
                        <td>${formatNumberWithCommas(employee.salary)}</td>
                        <td>${employee.notes || ''}</td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-employee" data-id="${employee.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-employee" data-id="${employee.id}" data-type="employee">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
                // Add event listeners for edit and delete buttons
                addEmployeeActionListeners();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: data.message || 'هەڵەیەک ڕوویدا لە کاتی گەڕانەوەی زانیاری کارمەندان',
                    confirmButtonText: 'باشە'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                confirmButtonText: 'باشە'
            });
        });
}

// Function to add event listeners for employee actions
function addEmployeeActionListeners() {
    // Edit employee action
    document.querySelectorAll('.edit-employee').forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.getAttribute('data-id');
            window.location.href = `addStaff.php?id=${employeeId}`;
        });
    });

    // Delete employee action
    document.querySelectorAll('.delete-employee').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type') || 'employee'; // Default to 'employee' if not set
            
            if (!id) {
                alert('Missing required data for deletion');
                return;
            }
            
            console.log('Deleting record:', { id, type });
            
            if (confirm('Are you sure you want to delete this record?')) {
                // Get the base URL (domain and application path)
                const baseUrl = window.location.href.split('/pages/')[0];
                const deleteUrl = `${baseUrl}/process/delete_employee.php`;
                
                console.log('Delete URL:', deleteUrl);
                
                fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id: parseInt(id, 10),
                        type: type
                    })
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || 'Server error');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Refresh the appropriate list based on type
                            if (type === 'employee') {
                                fetchEmployees();
                            } else if (type === 'customer') {
                                fetchCustomers();
                            } else if (type === 'supplier') {
                                fetchSuppliers();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'An error occurred while deleting the record',
                        confirmButtonText: 'OK'
                    });
                });
            }
        });
    });
}

// Function to format number with commas
function formatNumberWithCommas(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}