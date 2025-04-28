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
 * Format number with commas as thousands separators
 */
function formatNumber(number) {
    // If not a number or empty, return as is
    if (number === null || number === undefined || number === '') {
        return '0';
    }
    // Convert to number if it's a string
    const num = typeof number === 'string' ? parseFloat(number) : number;
    // Format with commas
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

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
    
    // Set up number formatting for input fields
    setupNumberFormatting();
}

/**
 * Set up number formatting for input fields with the 'number-format' class
 */
function setupNumberFormatting() {
    // Format on input
    $('.number-format').on('input', function() {
        let value = $(this).val().replace(/,/g, '');
        if (value) {
            value = parseInt(value).toString();
            $(this).val(formatNumber(value));
        }
    });
    
    // Format on focus out
    $('.number-format').on('blur', function() {
        let value = $(this).val().replace(/,/g, '');
        if (value) {
            value = parseInt(value).toString();
            $(this).val(formatNumber(value));
        } else {
            $(this).val('0');
        }
    });
    
    // Clear formatting on focus
    $('.number-format').on('focus', function() {
        let value = $(this).val().replace(/,/g, '');
        if (value === '0') {
            $(this).val('');
        } else {
            $(this).val(value);
        }
    });
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
    $('#supplierName, #supplierPhone').on('input change', function() {
        filterSupplierTable();
    });
    
    $('#supplierResetFilter').on('click', function() {
        $('#supplierName').val('');
        $('#supplierPhone').val('');
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
        
        // Get supplier data from server
        fetch(`api/get_supplier.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const supplier = data.supplier;
                    
                    // Populate modal fields
                    $('#editSupplierId').val(supplier.id);
                    $('#editSupplierName').val(supplier.name);
                    $('#editSupplierPhone1').val(supplier.phone1);
                    $('#editSupplierPhone2').val(supplier.phone2);
                    $('#editSupplierDebt').val(formatNumber(supplier.debt_on_myself));
                    $('#editSupplierNotes').val(supplier.notes);
                } else {
                    showToast(data.message || 'کێشەیەک ڕوویدا لە وەرگرتنی زانیاری دابینکەر', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('کێشەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەرەوە', 'error');
            });
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
    
    // Handle supplier edit form submission
    $('#saveSupplierEdit').on('click', function() {
        const id = $('#editSupplierId').val();
        const name = $('#editSupplierName').val();
        const phone1 = $('#editSupplierPhone1').val();
        const phone2 = $('#editSupplierPhone2').val();
        const debt = $('#editSupplierDebt').val().replace(/,/g, '');
        const notes = $('#editSupplierNotes').val();
        
        // Validate form
        if (!name || !phone1) {
            showToast('تکایە هەموو خانە پێویستەکان پڕ بکەوە', 'warning');
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            html: 'زانیاری دابینکەر نوێ دەکرێتەوە',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Submit form data
        const formData = {
            id: id,
            name: name,
            phone1: phone1,
            phone2: phone2,
            debt_on_myself: debt,
            notes: notes
        };
        
        fetch('api/update_supplier.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            // Check if the response is OK
            if (!response.ok) {
                throw new Error('Server returned status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Update response:', data); // Debug output
            if (data.success) {
                // Close modal
                $('#editSupplierModal').modal('hide');
                
                // Refresh the table
                const row = $(`#supplierTable tr[data-id="${id}"]`);
                const index = row.find('td:eq(0)').text();
                
                // Update the row with new data
                row.find('td:eq(1)').text(name);
                row.find('td:eq(2)').text(phone1);
                row.find('td:eq(3)').text(phone2 || '-');
                row.find('td:eq(4)').text(formatNumber(debt) + ' دینار');
                
                // Show success message
                Swal.fire({
                    title: 'سەرکەوتوو بوو!',
                    text: 'زانیاری دابینکەر بە سەرکەوتوویی نوێ کرایەوە',
                    icon: 'success',
                    confirmButtonText: 'باشە'
                });
            } else {
                showToast(data.message || 'کێشەیەک ڕوویدا لە نوێکردنەوەی زانیاری دابینکەر', 'error');
            }
            
            Swal.close();
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('کێشەیەک ڕوویدا لە پەیوەندی کردن بە سێرڤەرەوە: ' + error.message, 'error');
            Swal.close();
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

    // Notes button click handler for suppliers
    $(document).on('click', '#supplier-content .notes-btn', function() {
        const notes = $(this).data('notes');
        const supplierName = $(this).data('supplier-name');
        
        // Display notes in SweetAlert2
        Swal.fire({
            title: 'تێبینییەکانی ' + supplierName,
            html: notes ? notes : 'هیچ تێبینییەک نییە',
            icon: 'info',
            confirmButtonText: 'باشە'
        });
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
    const phoneFilter = $('#supplierPhone').val().toLowerCase();
    const searchFilter = $('#supplierTableSearch').val().toLowerCase();
    
    $('#supplierTable tbody tr').each(function() {
        const name = $(this).find('td:eq(1)').text().toLowerCase();
        const phone1 = $(this).find('td:eq(2)').text().toLowerCase();
        const phone2 = $(this).find('td:eq(3)').text().toLowerCase();
        const debt = $(this).find('td:eq(4)').text().toLowerCase();
        
        // Check if the row matches all active filters
        const matchesName = nameFilter === '' || name.includes(nameFilter);
        const matchesPhone = phoneFilter === '' || phone1.includes(phoneFilter) || phone2.includes(phoneFilter);
        const matchesSearch = searchFilter === '' || 
                               name.includes(searchFilter) || 
                               phone1.includes(searchFilter) || 
                               phone2.includes(searchFilter) || 
                               debt.includes(searchFilter);
        
        // Show or hide the row based on the filter result
        if (matchesName && matchesPhone && matchesSearch) {
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

// Customer Table Functionality
document.addEventListener('DOMContentLoaded', function() {
    const customerTable = document.getElementById('customerTable');
    const customerTableSearch = document.getElementById('customerTableSearch');
    const customerRecordsPerPage = document.getElementById('customerRecordsPerPage');
    const customerStartRecord = document.getElementById('customerStartRecord');
    const customerEndRecord = document.getElementById('customerEndRecord');
    const customerTotalRecords = document.getElementById('customerTotalRecords');
    const customerPrevPageBtn = document.getElementById('customerPrevPageBtn');
    const customerNextPageBtn = document.getElementById('customerNextPageBtn');
    const customerPaginationNumbers = document.getElementById('customerPaginationNumbers');
    const customerFilterForm = document.getElementById('customerFilterForm');
    const customerResetFilter = document.getElementById('customerResetFilter');
    const editCustomerModal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
    const saveCustomerEdit = document.getElementById('saveCustomerEdit');

    let currentPage = 1;
    let recordsPerPage = parseInt(customerRecordsPerPage.value);
    let filteredCustomers = Array.from(customerTable.getElementsByTagName('tbody')[0].rows);

    // Function to update table display
    function updateTableDisplay() {
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        const totalPages = Math.ceil(filteredCustomers.length / recordsPerPage);

        // Hide all rows
        Array.from(customerTable.getElementsByTagName('tbody')[0].rows).forEach(row => {
            row.style.display = 'none';
        });

        // Show rows for current page
        filteredCustomers.slice(startIndex, endIndex).forEach(row => {
            row.style.display = '';
        });

        // Update pagination info
        customerStartRecord.textContent = filteredCustomers.length > 0 ? startIndex + 1 : 0;
        customerEndRecord.textContent = Math.min(endIndex, filteredCustomers.length);
        customerTotalRecords.textContent = filteredCustomers.length;

        // Update pagination buttons
        customerPrevPageBtn.disabled = currentPage === 1;
        customerNextPageBtn.disabled = currentPage === totalPages;

        // Update pagination numbers
        customerPaginationNumbers.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement('button');
            button.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2`;
            button.textContent = i;
            button.addEventListener('click', () => {
                currentPage = i;
                updateTableDisplay();
            });
            customerPaginationNumbers.appendChild(button);
        }
    }

    // Search functionality
    customerTableSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        filteredCustomers = Array.from(customerTable.getElementsByTagName('tbody')[0].rows).filter(row => {
            return Array.from(row.cells).some(cell => 
                cell.textContent.toLowerCase().includes(searchTerm)
            );
        });
        currentPage = 1;
        updateTableDisplay();
    });

    // Records per page change
    customerRecordsPerPage.addEventListener('change', function() {
        recordsPerPage = parseInt(this.value);
        currentPage = 1;
        updateTableDisplay();
    });

    // Pagination buttons
    customerPrevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            updateTableDisplay();
        }
    });

    customerNextPageBtn.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredCustomers.length / recordsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updateTableDisplay();
        }
    });

    // Filter form
    customerFilterForm.addEventListener('input', function(e) {
        const nameFilter = document.getElementById('customerName').value.toLowerCase();
        const phoneFilter = document.getElementById('customerPhone').value.toLowerCase();
        const addressFilter = document.getElementById('customerAddress').value.toLowerCase();

        filteredCustomers = Array.from(customerTable.getElementsByTagName('tbody')[0].rows).filter(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const phone = row.cells[2].textContent.toLowerCase();
            const address = row.cells[6].textContent.toLowerCase();

            return name.includes(nameFilter) && 
                   phone.includes(phoneFilter) && 
                   address.includes(addressFilter);
        });

        currentPage = 1;
        updateTableDisplay();
    });

    // Reset filter
    customerResetFilter.addEventListener('click', function() {
        customerFilterForm.reset();
        filteredCustomers = Array.from(customerTable.getElementsByTagName('tbody')[0].rows);
        currentPage = 1;
        updateTableDisplay();
    });

    // Initialize table display
    updateTableDisplay();

    // Handle buttons in customer table
    customerTable.addEventListener('click', function(e) {
        const target = e.target.closest('button');
        if (!target) return;

        const row = target.closest('tr');
        const customerId = row.dataset.id;

        // Show Notes Button
        if (target.classList.contains('notes-btn')) {
            const notes = target.dataset.notes;
            const customerName = target.dataset.customerName;
            
            Swal.fire({
                title: `تێبینییەکانی ${customerName}`,
                html: notes ? `<div class="text-right" dir="rtl">${notes}</div>` : '<div class="text-center">هیچ تێبینییەک نییە</div>',
                confirmButtonText: 'داخستن',
                customClass: {
                    popup: 'swal-wide',
                    htmlContainer: 'text-right'
                }
            });
        }
        // Edit Button
        else if (target.classList.contains('edit-btn')) {
            // Get customer data
            fetch(`api/get_customer.php?id=${customerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const customer = data.customer;
                    
                    // Fill the form with customer data
                    document.getElementById('editCustomerId').value = customer.id;
                    document.getElementById('editCustomerName').value = customer.name;
                    document.getElementById('editCustomerPhone').value = customer.phone1;
                    document.getElementById('editCustomerPhone2').value = customer.phone2 || '';
                    document.getElementById('editCustomerAddress').value = customer.address || '';
                    document.getElementById('editGuarantorName').value = customer.guarantor_name || '';
                    document.getElementById('editGuarantorPhone').value = customer.guarantor_phone || '';
                    document.getElementById('editDebitOnBusiness').value = customer.debit_on_business || 0;
                    document.getElementById('editCustomerNotes').value = customer.notes || '';
                    
                    // Show the modal
                    editCustomerModal.show();
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: data.message || 'ناتوانرێت داتای کڕیار بهێنرێت.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵە لە پەیوەندی بە سێرڤەرەوە.',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            });
        }
        // Delete Button
        else if (target.classList.contains('delete-btn')) {
            Swal.fire({
                title: 'دڵنیای لە سڕینەوەی کڕیار؟',
                text: 'ئەم کردارە ناتوانرێت گەڕێندرێتەوە!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'بەڵێ، بسڕەوە!',
                cancelButtonText: 'نەخێر'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send delete request
                    fetch(`api/delete_customer.php?id=${customerId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'سڕایەوە!',
                                'کڕیار بە سەرکەوتوویی سڕایەوە.',
                                'success'
                            ).then(() => {
                                row.remove();
                                filteredCustomers = Array.from(customerTable.getElementsByTagName('tbody')[0].rows);
                                updateTableDisplay();
                            });
                        } else {
                            Swal.fire(
                                'هەڵە!',
                                data.message || 'هەڵە لە سڕینەوەی کڕیار.',
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        Swal.fire(
                            'هەڵە!',
                            'هەڵە لە پەیوەندی بە سێرڤەرەوە.',
                            'error'
                        );
                    });
                }
            });
        }
    });

    // Handle save customer edit button
    saveCustomerEdit.addEventListener('click', function() {
        const form = document.getElementById('editCustomerForm');
        const formData = new FormData(form);
        
        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Send update request
        fetch('api/update_customer.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'سەرکەوتوو!',
                    text: 'زانیاری کڕیار بە سەرکەوتوویی نوێ کرایەوە.',
                    icon: 'success',
                    confirmButtonText: 'باشە'
                }).then(() => {
                    // Close the modal
                    editCustomerModal.hide();
                    
                    // Update the table row instead of reloading
                    const row = customerTable.querySelector(`tr[data-id="${formData.get('id')}"]`);
                    if (row) {
                        row.cells[1].textContent = formData.get('name');
                        row.cells[2].textContent = formData.get('phone1');
                        row.cells[3].textContent = formData.get('phone2') || '';
                        row.cells[4].textContent = formData.get('guarantor_name') || '';
                        row.cells[5].textContent = formData.get('guarantor_phone') || '';
                        row.cells[6].textContent = formData.get('address') || '';
                        row.cells[7].textContent = formatNumber(formData.get('debit_on_business'));
                        
                        // Update the notes button data
                        const notesBtn = row.querySelector('.notes-btn');
                        if (notesBtn) {
                            notesBtn.dataset.notes = formData.get('notes') || '';
                            notesBtn.dataset.customerName = formData.get('name');
                        }
                    }
                });
            } else {
                Swal.fire({
                    title: 'هەڵە!',
                    text: data.message || 'هەڵە لە نوێکردنەوەی زانیاری کڕیار.',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'هەڵە!',
                text: 'هەڵە لە پەیوەندی بە سێرڤەرەوە.',
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        });
    });

    // Function to add new customer row to table
    function addCustomerToTable(customer) {
        const tbody = customerTable.querySelector('tbody');
        const newRow = document.createElement('tr');
        newRow.dataset.id = customer.id;
        
        newRow.innerHTML = `
            <td>${tbody.children.length + 1}</td>
            <td>${customer.name}</td>
            <td>${customer.phone1}</td>
            <td>${customer.phone2 || ''}</td>
            <td>${customer.guarantor_name || ''}</td>
            <td>${customer.guarantor_phone || ''}</td>
            <td>${customer.address || ''}</td>
            <td>${number_format(customer.debit_on_business, 0)}</td>
            <td>
                <div class="action-buttons">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${customer.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${customer.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning rounded-circle notes-btn" 
                            data-notes="${customer.notes || ''}"
                            data-customer-name="${customer.name}">
                        <i class="fas fa-sticky-note"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${customer.id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(newRow);
        updateTableDisplay();
    }

    // Function to format number with no decimal places
    function formatNumber(number) {
        // Remove any existing commas
        const cleanNumber = number.toString().replace(/,/g, '');
        // Format with no decimal places
        return parseFloat(cleanNumber).toFixed(0);
    }

    // Handle add customer form submission
    const addCustomerForm = document.getElementById('addCustomerForm');
    if (addCustomerForm) {
        addCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Show loading
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send add request
            fetch('api/add_customer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'سەرکەوتوو!',
                        text: 'کڕیار بە سەرکەوتوویی زیاد کرا.',
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Add new customer to table
                        addCustomerToTable(data.customer);
                        // Reset form
                        this.reset();
                        // Close modal if exists
                        const addCustomerModal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                        if (addCustomerModal) {
                            addCustomerModal.hide();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: data.message || 'هەڵە لە زیادکردنی کڕیار.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'هەڵە!',
                    text: 'هەڵە لە پەیوەندی بە سێرڤەرەوە.',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            });
        });
    }
}); 