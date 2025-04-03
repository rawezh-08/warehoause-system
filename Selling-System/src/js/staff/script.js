$(document).ready(function() {
    // Load components (sidebar, navbar)
    loadComponents();
    
    // Initialize tables
    initializeEmployeeTable();
    initializeCustomerTable();
    initializeSupplierTable();
    
    // Setup tab click handlers
    setupTabHandlers();
    
    // Setup filter handlers
    setupFilterHandlers();
    
    // Setup modals
    setupEditModals();
    
    // Setup action button handlers
    setupActionButtons();
    
    // Check URL for tab parameter and activate the appropriate tab
    activateTabFromURL();
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
    
    // Highlight the staff list menu item
    const staffItem = submenu.find('a[href="staff.php"]');
    staffItem.parent().addClass('active');
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
 * Activate tab based on URL parameter
 */
function activateTabFromURL() {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    // If tab parameter exists, activate the corresponding tab
    if (tabParam) {
        $(`#${tabParam}-tab`).tab('show');
    }
}

/**
 * Setup filter handlers
 */
function setupFilterHandlers() {
    // Employee filters
    $('#employeeName, #employeePosition').on('input change', function() {
        filterEmployeeTable();
    });
    
    $('#employeeResetFilter').on('click', function() {
        $('#employeeName').val('');
        $('#employeePosition').val('');
        filterEmployeeTable();
    });
    
    // Customer filters
    $('#customerName, #customerType').on('input change', function() {
        filterCustomerTable();
    });
    
    $('#customerResetFilter').on('click', function() {
        $('#customerName').val('');
        $('#customerType').val('');
        filterCustomerTable();
    });
    
    // Supplier filters
    $('#supplierName, #supplierType').on('input change', function() {
        filterSupplierTable();
    });
    
    $('#supplierResetFilter').on('click', function() {
        $('#supplierName').val('');
        $('#supplierType').val('');
        filterSupplierTable();
    });
}

/**
 * Setup edit modals
 */
function setupEditModals() {
    // Employee edit modal
    $(document).on('click', '#employee-content .edit-btn', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        // Get data from row
        const name = row.find('td:eq(1)').text();
        const phone = row.find('td:eq(2)').text();
        const position = row.find('td:eq(3)').text();
        const salary = row.find('td:eq(4)').text().replace('$', '');
        const address = row.find('td:eq(5)').text();
        
        // Populate modal fields
        $('#editEmployeeId').val(id);
        $('#editEmployeeName').val(name);
        $('#editEmployeePhone').val(phone);
        $('#editEmployeePosition').val(position);
        $('#editEmployeeSalary').val(salary);
        $('#editEmployeeAddress').val(address);
    });
    
    // Customer edit modal
    $(document).on('click', '#customer-content .edit-btn', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        // Get data from row
        const name = row.find('td:eq(1)').text();
        const phone = row.find('td:eq(2)').text();
        const address = row.find('td:eq(3)').text();
        const type = row.find('td:eq(4) .badge').text();
        const creditLimit = row.find('td:eq(5)').text().replace('$', '');
        
        // Populate modal fields
        $('#editCustomerId').val(id);
        $('#editCustomerName').val(name);
        $('#editCustomerPhone').val(phone);
        $('#editCustomerAddress').val(address);
        $('#editCustomerType').val(getCustomerTypeValue(type));
        $('#editCreditLimit').val(creditLimit);
    });
    
    // Supplier edit modal
    $(document).on('click', '#supplier-content .edit-btn', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        // Get data from row
        const name = row.find('td:eq(1)').text();
        const phone = row.find('td:eq(2)').text();
        const contactPerson = row.find('td:eq(3)').text();
        const address = row.find('td:eq(4)').text();
        const type = row.find('td:eq(5) .badge').text();
        
        // Populate modal fields
        $('#editSupplierId').val(id);
        $('#editSupplierName').val(name);
        $('#editSupplierPhone').val(phone);
        $('#editContactPerson').val(contactPerson);
        $('#editSupplierAddress').val(address);
        $('#editSupplierType').val(getSupplierTypeValue(type));
    });
    
    // Save employee edit
    $('#saveEmployeeEdit').on('click', function() {
        // Get form data
        const id = $('#editEmployeeId').val();
        const name = $('#editEmployeeName').val();
        const phone = $('#editEmployeePhone').val();
        const position = $('#editEmployeePosition').val();
        const salary = $('#editEmployeeSalary').val();
        const address = $('#editEmployeeAddress').val();
        
        // Validate form
        if (!name || !phone) {
            showToast('تکایە هەموو خانە پێویستەکان پڕبکەوە', 'error');
            return;
        }
        
        // Here you would typically make an AJAX call to update the database
        // For demo purposes, we'll just show a success message
        Swal.fire({
            title: 'سەرکەوتوو بوو!',
            text: 'زانیاری کارمەند نوێکرایەوە',
            icon: 'success',
            confirmButtonText: 'باشە'
        }).then(() => {
            // Close the modal
            $('#editEmployeeModal').modal('hide');
            
            // Update the table row with new data
            const row = $(`#employeeTable tr[data-id="${id}"]`);
            row.find('td:eq(1)').text(name);
            row.find('td:eq(2)').text(phone);
            row.find('td:eq(3)').text(position);
            row.find('td:eq(4)').text('$' + salary);
            row.find('td:eq(5)').text(address);
        });
    });
    
    // Save customer edit
    $('#saveCustomerEdit').on('click', function() {
        // Get form data
        const id = $('#editCustomerId').val();
        const name = $('#editCustomerName').val();
        const phone = $('#editCustomerPhone').val();
        const address = $('#editCustomerAddress').val();
        const type = $('#editCustomerType').val();
        const creditLimit = $('#editCreditLimit').val();
        
        // Validate form
        if (!name || !phone) {
            showToast('تکایە هەموو خانە پێویستەکان پڕبکەوە', 'error');
            return;
        }
        
        // Here you would typically make an AJAX call to update the database
        // For demo purposes, we'll just show a success message
        Swal.fire({
            title: 'سەرکەوتوو بوو!',
            text: 'زانیاری کڕیار نوێکرایەوە',
            icon: 'success',
            confirmButtonText: 'باشە'
        }).then(() => {
            // Close the modal
            $('#editCustomerModal').modal('hide');
            
            // Update the table row with new data
            const row = $(`#customerTable tr[data-id="${id}"]`);
            row.find('td:eq(1)').text(name);
            row.find('td:eq(2)').text(phone);
            row.find('td:eq(3)').text(address);
            
            // Update customer type badge
            const badgeClass = getCustomerTypeBadgeClass(type);
            const typeText = getCustomerTypeText(type);
            row.find('td:eq(4)').html(`<span class="badge ${badgeClass}">${typeText}</span>`);
            
            row.find('td:eq(5)').text('$' + creditLimit);
        });
    });
    
    // Save supplier edit
    $('#saveSupplierEdit').on('click', function() {
        // Get form data
        const id = $('#editSupplierId').val();
        const name = $('#editSupplierName').val();
        const phone = $('#editSupplierPhone').val();
        const contactPerson = $('#editContactPerson').val();
        const email = $('#editSupplierEmail').val();
        const address = $('#editSupplierAddress').val();
        const type = $('#editSupplierType').val();
        const paymentTerms = $('#editPaymentTerms').val();
        
        // Validate form
        if (!name || !phone) {
            showToast('تکایە هەموو خانە پێویستەکان پڕبکەوە', 'error');
            return;
        }
        
        // Here you would typically make an AJAX call to update the database
        // For demo purposes, we'll just show a success message
        Swal.fire({
            title: 'سەرکەوتوو بوو!',
            text: 'زانیاری دابینکەر نوێکرایەوە',
            icon: 'success',
            confirmButtonText: 'باشە'
        }).then(() => {
            // Close the modal
            $('#editSupplierModal').modal('hide');
            
            // Update the table row with new data
            const row = $(`#supplierTable tr[data-id="${id}"]`);
            row.find('td:eq(1)').text(name);
            row.find('td:eq(2)').text(phone);
            row.find('td:eq(3)').text(contactPerson);
            row.find('td:eq(4)').text(address);
            
            // Update supplier type badge
            const badgeClass = getSupplierTypeBadgeClass(type);
            const typeText = getSupplierTypeText(type);
            row.find('td:eq(5)').html(`<span class="badge ${badgeClass}">${typeText}</span>`);
        });
    });
}

/**
 * Setup action buttons (view, delete)
 */
function setupActionButtons() {
    // View button click handler
    $(document).on('click', '.view-btn', function() {
        const id = $(this).data('id');
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        let message = '';
        
        if (tabId === 'employee-content') {
            message = 'پیشاندانی زانیاری کارمەند - تۆماری ژمارە: ' + id;
        } else if (tabId === 'customer-content') {
            message = 'پیشاندانی زانیاری کڕیار - تۆماری ژمارە: ' + id;
        } else if (tabId === 'supplier-content') {
            message = 'پیشاندانی زانیاری دابینکەر - تۆماری ژمارە: ' + id;
        }
        
        showToast(message, 'info');
    });
    
    // Delete button click handler
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        let title = '';
        let text = '';
        
        if (tabId === 'employee-content') {
            title = 'سڕینەوەی کارمەند';
            text = 'دڵنیای لە سڕینەوەی ئەم کارمەندە؟';
        } else if (tabId === 'customer-content') {
            title = 'سڕینەوەی کڕیار';
            text = 'دڵنیای لە سڕینەوەی ئەم کڕیارە؟';
        } else if (tabId === 'supplier-content') {
            title = 'سڕینەوەی دابینکەر';
            text = 'دڵنیای لە سڕینەوەی ئەم دابینکەرە؟';
        }
        
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بیسڕەوە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                // Here you would typically make an AJAX call to delete the record
                // For demo purposes, we'll just remove the row from the table
                $(`#${tabId} tr[data-id="${id}"]`).remove();
                
                Swal.fire({
                    title: 'سڕایەوە!',
                    text: 'تۆمارەکە بە سەرکەوتوویی سڕایەوە.',
                    icon: 'success',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
    
    // Refresh button click handler
    $('.refresh-btn').on('click', function() {
        const tabContainer = $(this).closest('.tab-pane');
        const tabId = tabContainer.attr('id');
        
        showToast('داتاکان نوێکرانەوە', 'info');
    });
}

/**
 * Initialize employee table
 */
function initializeEmployeeTable() {
    // Search functionality
    $('#employeeTableSearch').on('input', function() {
        filterEmployeeTable();
    });
    
    // Records per page
    $('#employeeRecordsPerPage').on('change', function() {
        // This would typically reload the data with the new page size
        showToast('ژمارەی تۆمارەکان گۆڕا', 'info');
    });
}

/**
 * Initialize customer table
 */
function initializeCustomerTable() {
    // Search functionality
    $('#customerTableSearch').on('input', function() {
        filterCustomerTable();
    });
    
    // Records per page
    $('#customerRecordsPerPage').on('change', function() {
        // This would typically reload the data with the new page size
        showToast('ژمارەی تۆمارەکان گۆڕا', 'info');
    });
}

/**
 * Initialize supplier table
 */
function initializeSupplierTable() {
    // Search functionality
    $('#supplierTableSearch').on('input', function() {
        filterSupplierTable();
    });
    
    // Records per page
    $('#supplierRecordsPerPage').on('change', function() {
        // This would typically reload the data with the new page size
        showToast('ژمارەی تۆمارەکان گۆڕا', 'info');
    });
}

/**
 * Filter employee table
 */
function filterEmployeeTable() {
    const nameFilter = $('#employeeName').val().toLowerCase();
    const positionFilter = $('#employeePosition').val().toLowerCase();
    const searchFilter = $('#employeeTableSearch').val().toLowerCase();
    
    $('#employeeTable tbody tr').each(function() {
        const name = $(this).find('td:eq(1)').text().toLowerCase();
        const position = $(this).find('td:eq(3)').text().toLowerCase();
        const phone = $(this).find('td:eq(2)').text().toLowerCase();
        const salary = $(this).find('td:eq(4)').text().toLowerCase();
        const address = $(this).find('td:eq(5)').text().toLowerCase();
        
        // Check if the row matches all active filters
        const matchesName = nameFilter === '' || name.includes(nameFilter);
        const matchesPosition = positionFilter === '' || position === positionFilter;
        const matchesSearch = searchFilter === '' || 
                              name.includes(searchFilter) || 
                              phone.includes(searchFilter) || 
                              position.includes(searchFilter) || 
                              salary.includes(searchFilter) || 
                              address.includes(searchFilter);
        
        // Show or hide the row based on the filter result
        if (matchesName && matchesPosition && matchesSearch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

/**
 * Filter customer table
 */
function filterCustomerTable() {
    const nameFilter = $('#customerName').val().toLowerCase();
    const typeFilter = $('#customerType').val().toLowerCase();
    const searchFilter = $('#customerTableSearch').val().toLowerCase();
    
    $('#customerTable tbody tr').each(function() {
        const name = $(this).find('td:eq(1)').text().toLowerCase();
        const phone = $(this).find('td:eq(2)').text().toLowerCase();
        const address = $(this).find('td:eq(3)').text().toLowerCase();
        const type = $(this).find('td:eq(4) .badge').text().toLowerCase();
        const creditLimit = $(this).find('td:eq(5)').text().toLowerCase();
        
        // Check if the row matches all active filters
        const matchesName = nameFilter === '' || name.includes(nameFilter);
        const matchesType = typeFilter === '' || getCustomerTypeValue(type) === typeFilter;
        const matchesSearch = searchFilter === '' || 
                               name.includes(searchFilter) || 
                               phone.includes(searchFilter) || 
                               address.includes(searchFilter) || 
                               type.includes(searchFilter) || 
                               creditLimit.includes(searchFilter);
        
        // Show or hide the row based on the filter result
        if (matchesName && matchesType && matchesSearch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

/**
 * Filter supplier table
 */
function filterSupplierTable() {
    const nameFilter = $('#supplierName').val().toLowerCase();
    const typeFilter = $('#supplierType').val().toLowerCase();
    const searchFilter = $('#supplierTableSearch').val().toLowerCase();
    
    $('#supplierTable tbody tr').each(function() {
        const name = $(this).find('td:eq(1)').text().toLowerCase();
        const phone = $(this).find('td:eq(2)').text().toLowerCase();
        const contactPerson = $(this).find('td:eq(3)').text().toLowerCase();
        const address = $(this).find('td:eq(4)').text().toLowerCase();
        const type = $(this).find('td:eq(5) .badge').text().toLowerCase();
        
        // Check if the row matches all active filters
        const matchesName = nameFilter === '' || name.includes(nameFilter);
        const matchesType = typeFilter === '' || getSupplierTypeValue(type) === typeFilter;
        const matchesSearch = searchFilter === '' || 
                               name.includes(searchFilter) || 
                               phone.includes(searchFilter) || 
                               contactPerson.includes(searchFilter) || 
                               address.includes(searchFilter) || 
                               type.includes(searchFilter);
        
        // Show or hide the row based on the filter result
        if (matchesName && matchesType && matchesSearch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

/**
 * Helper function to get customer type value
 */
function getCustomerTypeValue(typeText) {
    const typeMap = {
        'تاک': 'retail',
        'کۆ': 'wholesale',
        'بەردەوام': 'regular'
    };
    return typeMap[typeText] || '';
}

/**
 * Helper function to get customer type text
 */
function getCustomerTypeText(typeValue) {
    const typeMap = {
        'retail': 'تاک',
        'wholesale': 'کۆ',
        'regular': 'بەردەوام'
    };
    return typeMap[typeValue] || '';
}

/**
 * Helper function to get customer type badge class
 */
function getCustomerTypeBadgeClass(typeValue) {
    const classMap = {
        'retail': 'bg-info',
        'wholesale': 'bg-warning text-dark',
        'regular': 'bg-success'
    };
    return classMap[typeValue] || 'bg-secondary';
}

/**
 * Helper function to get supplier type value
 */
function getSupplierTypeValue(typeText) {
    const typeMap = {
        'بەرهەمهێنەر': 'manufacturer',
        'دابەشکەر': 'distributor',
        'فرۆشیاری کۆ': 'wholesaler'
    };
    return typeMap[typeText] || '';
}

/**
 * Helper function to get supplier type text
 */
function getSupplierTypeText(typeValue) {
    const typeMap = {
        'manufacturer': 'بەرهەمهێنەر',
        'distributor': 'دابەشکەر',
        'wholesaler': 'فرۆشیاری کۆ'
    };
    return typeMap[typeValue] || '';
}

/**
 * Helper function to get supplier type badge class
 */
function getSupplierTypeBadgeClass(typeValue) {
    const classMap = {
        'manufacturer': 'bg-primary',
        'distributor': 'bg-info',
        'wholesaler': 'bg-warning text-dark'
    };
    return classMap[typeValue] || 'bg-secondary';
} 