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

    // Get filter values
    const nameFilter = document.getElementById('employeeNameFilter').value;
    const phoneSearch = document.getElementById('employeePhoneSearch').value;

    // Build query parameters
    const params = new URLSearchParams();
    if (nameFilter) params.append('name', nameFilter);
    if (phoneSearch) params.append('phone', phoneSearch);

    // Fetch employees from server
    fetch(`../../process/get_employees.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide loading
                Swal.close();
                
                // Get table body
                const tbody = document.querySelector('#employeeTable tbody');
                if (!tbody) return;
                
                // Clear existing rows
                tbody.innerHTML = '';
                
                // Update name filter options
                const nameFilter = document.getElementById('employeeNameFilter');
                nameFilter.innerHTML = '<option value="">هەموو کارمەندان</option>';
                
                // Add each employee to table and name filter
                data.employees.forEach((employee, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${employee.name || '-'}</td>
                        <td>${employee.phone || '-'}</td>
                        <td>${employee.salary ? formatNumberWithCommas(employee.salary) : '-'}</td>
                      
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-btn" data-id="${employee.id}" data-bs-toggle="modal" data-bs-target="#editEmployeeModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning rounded-circle notes-btn" 
                                    data-notes="${employee.notes || ''}" data-employee-name="${employee.name || ''}">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${employee.id}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                    
                    // Add to name filter
                    const option = document.createElement('option');
                    option.value = employee.name;
                    option.textContent = employee.name;
                    nameFilter.appendChild(option);
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
    // Edit buttons
    document.querySelectorAll('#employeeTable .edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.dataset.id;
            // Get employee data
            fetch(`../../process/get_employee.php?id=${employeeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fill the edit form with employee data
                        document.getElementById('editEmployeeId').value = data.employee.id;
                        document.getElementById('editEmployeeName').value = data.employee.name;
                        document.getElementById('editEmployeePhone').value = data.employee.phone;
                        document.getElementById('editEmployeeSalary').value = data.employee.salary;
                        document.getElementById('editEmployeeNotes').value = data.employee.notes;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: data.message || 'هەڵەیەک ڕوویدا لە کاتی گەڕانەوەی زانیاری کارمەند',
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
        });
    });
    
    // Delete buttons - ONLY for employee table
    document.querySelectorAll('#employeeTable .delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.dataset.id;
            deleteEmployee(employeeId);
        });
    });
}

// Function to delete an employee
function deleteEmployee(employeeId) {
    Swal.fire({
        title: 'دڵنیای لە سڕینەوەی ئەم کارمەندە؟',
        text: 'ئەم کردارە ناتوانرێت گەڕێنرێتەوە!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'سڕینەوەی کارمەند بەردەوامە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send delete request
            fetch('../../process/delete_employee.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: employeeId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو!',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Refresh employee list
                        fetchEmployees();
                    });
                } else {
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
                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                    confirmButtonText: 'باشە'
                });
            });
        }
    });
}

// Function to format number with commas
function formatNumberWithCommas(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Function to save employee edit
function saveEmployeeEdit() {
    const employeeId = document.getElementById('editEmployeeId').value;
    const name = document.getElementById('editEmployeeName').value;
    const phone = document.getElementById('editEmployeePhone').value;
    const salary = document.getElementById('editEmployeeSalary').value;
    const notes = document.getElementById('editEmployeeNotes').value;

    // Show loading
    Swal.fire({
        title: 'تکایە چاوەڕێ بکە...',
        text: 'زانیاری کارمەند تازە دەکرێتەوە',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Send update request
    fetch('../../process/update_employee.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: employeeId,
            name: name,
            phone: phone,
            salary: salary,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'سەرکەوتوو بوو!',
                text: data.message,
                confirmButtonText: 'باشە'
            }).then(() => {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editEmployeeModal'));
                modal.hide();
                
                // Refresh employee list
                fetchEmployees();
            });
        } else {
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
            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
            confirmButtonText: 'باشە'
        });
    });
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add notes button click handlers for employees
    document.addEventListener('click', function(e) {
        if (e.target && e.target.closest('#employeeTable .notes-btn')) {
            const button = e.target.closest('.notes-btn');
            const notes = button.getAttribute('data-notes');
            const employeeName = button.getAttribute('data-employee-name');
            
            Swal.fire({
                title: `تێبینیەکانی ${employeeName}`,
                text: notes || 'هیچ تێبینیەک نییە',
                icon: 'info',
                confirmButtonText: 'داخستن'
            });
        }
    });

    // Add notes button click handlers for customers
    document.querySelectorAll('#customerTable .notes-btn').forEach(button => {
        button.addEventListener('click', function() {
            const notes = this.getAttribute('data-notes');
            const customerName = this.getAttribute('data-customer-name');
            
            Swal.fire({
                title: `تێبینیەکانی ${customerName}`,
                text: notes || 'هیچ تێبینیەک نییە',
                icon: 'info',
                confirmButtonText: 'داخستن'
            });
        });
    });

    // Add notes button click handlers for suppliers
    document.querySelectorAll('#supplierTable .notes-btn').forEach(button => {
        button.addEventListener('click', function() {
            const notes = this.getAttribute('data-notes');
            const supplierName = this.getAttribute('data-supplier-name');
            
            Swal.fire({
                title: `تێبینیەکانی ${supplierName}`,
                text: notes || 'هیچ تێبینیەک نییە',
                icon: 'info',
                confirmButtonText: 'داخستن'
            });
        });
    });

    // Fetch employees when page loads
    fetchEmployees();

    // Add refresh button click handler
    document.querySelector('.refresh-btn').addEventListener('click', function() {
        fetchEmployees();
    });

    // Add save button click handler
    document.getElementById('saveEmployeeEdit').addEventListener('click', saveEmployeeEdit);

    // Add filter change handlers
    document.getElementById('employeeNameFilter').addEventListener('change', fetchEmployees);
    document.getElementById('employeePhoneSearch').addEventListener('input', function() {
        // Add debounce to prevent too many requests
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            fetchEmployees();
        }, 500);
    });

    // Add customer edit button click handlers
    document.querySelectorAll('#customerTable .edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const customerId = this.getAttribute('data-id');
            // Show loading
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'زانیاری کڕیار بار دەکرێت',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fetch customer data
            fetch(`../../process/get_customer.php?id=${customerId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        // Fill the edit form with customer data
                        document.getElementById('editCustomerId').value = data.customer.id;
                        document.getElementById('editCustomerName').value = data.customer.name;
                        document.getElementById('editCustomerPhone').value = data.customer.phone1;
                        document.getElementById('editCustomerPhone2').value = data.customer.phone2 || '';
                        document.getElementById('editCustomerAddress').value = data.customer.address || '';
                        document.getElementById('editGuarantorName').value = data.customer.guarantor_name || '';
                        document.getElementById('editGuarantorPhone').value = data.customer.guarantor_phone || '';
                        document.getElementById('editDebitOnBusiness').value = data.customer.debit_on_business || 0;
                        document.getElementById('editCustomerNotes').value = data.customer.notes || '';
                        
                        // Open the modal
                        const editCustomerModal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
                        editCustomerModal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: data.message || 'کڕیار نەدۆزرایەوە',
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
        });
    });

    // Add save customer edit button click handler
    document.getElementById('saveCustomerEdit').addEventListener('click', function() {
        // Get form data
        const customerId = document.getElementById('editCustomerId').value;
        const formData = {
            id: customerId,
            name: document.getElementById('editCustomerName').value,
            phone1: document.getElementById('editCustomerPhone').value,
            phone2: document.getElementById('editCustomerPhone2').value,
            address: document.getElementById('editCustomerAddress').value,
            guarantor_name: document.getElementById('editGuarantorName').value,
            guarantor_phone: document.getElementById('editGuarantorPhone').value,
            debit_on_business: document.getElementById('editDebitOnBusiness').value,
            notes: document.getElementById('editCustomerNotes').value
        };

        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            text: 'زانیاری کڕیار نوێ دەکرێتەوە',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send update request
        fetch('../../process/update_customer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the row in the table directly
                const row = document.querySelector(`#customerTable tr[data-id="${customerId}"]`);
                if (row) {
                    row.cells[1].textContent = formData.name;
                    row.cells[2].textContent = formData.phone1;
                    row.cells[3].textContent = formData.phone2 || '-';
                    row.cells[4].textContent = formData.guarantor_name || '-';
                    row.cells[5].textContent = formData.guarantor_phone || '-';
                    row.cells[6].textContent = formData.address || '-';
                    row.cells[7].textContent = formData.debit_on_business ? Number(formData.debit_on_business).toLocaleString() : '-';
                }

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editCustomerModal'));
                modal.hide();

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو!',
                    text: data.message || 'زانیاری کڕیار بە سەرکەوتوویی نوێ کرایەوە',
                    confirmButtonText: 'باشە'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: data.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی زانیاری کڕیار',
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
    });

    // Add save supplier edit button click handler
    document.getElementById('saveSupplierEdit').addEventListener('click', function() {
        // Get form data
        const supplierId = document.getElementById('editSupplierId').value;
        const formData = {
            id: supplierId,
            name: document.getElementById('editSupplierName').value,
            phone1: document.getElementById('editSupplierPhone1').value,
            phone2: document.getElementById('editSupplierPhone2').value,
            debt_on_myself: document.getElementById('editSupplierDebt').value.replace(/,/g, ''),
            notes: document.getElementById('editSupplierNotes').value
        };

        // Show loading
        Swal.fire({
            title: 'تکایە چاوەڕێ بکە...',
            text: 'زانیاری دابینکەر نوێ دەکرێتەوە',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send update request
        fetch('../../process/update_supplier.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the row in the table directly
                const row = document.querySelector(`#supplierTable tr[data-id="${supplierId}"]`);
                if (row) {
                    row.cells[1].textContent = formData.name;
                    row.cells[2].textContent = formData.phone1;
                    row.cells[3].textContent = formData.phone2 || '-';
                    row.cells[4].textContent = formData.debt_on_myself ? Number(formData.debt_on_myself).toLocaleString() + ' دینار' : '-';
                }

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editSupplierModal'));
                modal.hide();

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو!',
                    text: data.message || 'زانیاری دابینکەر بە سەرکەوتوویی نوێ کرایەوە',
                    confirmButtonText: 'باشە'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: data.message || 'هەڵەیەک ڕوویدا لە نوێکردنەوەی زانیاری دابینکەر',
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
    });

    // Add customer delete button click handlers
    document.querySelectorAll('#customerTable .delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const customerId = this.getAttribute('data-id');
            const row = this.closest('tr');
            
            Swal.fire({
                title: 'دڵنیای لە سڕینەوە؟',
                text: 'ئەم کردارە ناتوانرێت گەڕێنرێتەوە!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بەڵێ، بیسڕەوە',
                cancelButtonText: 'نا، هەڵوەشێنەوە'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'تکایە چاوەڕێ بکە...',
                        text: 'سڕینەوەی کڕیار',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send delete request
                    fetch('../../process/delete_customer.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ id: customerId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            row.remove();
                            
                            // Update pagination
                            applyCustomerPagination(1);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو بوو!',
                                text: data.message,
                                confirmButtonText: 'باشە'
                            });
                        } else {
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
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە: ' + error.message,
                            confirmButtonText: 'باشە'
                        });
                    });
                }
            });
        });
    });
    
    // Add supplier delete button click handlers
    document.querySelectorAll('#supplierTable .delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const supplierId = this.getAttribute('data-id');
            const row = this.closest('tr');
            
            Swal.fire({
                title: 'دڵنیای لە سڕینەوە؟',
                text: 'ئەم کردارە ناتوانرێت گەڕێنرێتەوە!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بەڵێ، بیسڕەوە',
                cancelButtonText: 'نا، هەڵوەشێنەوە'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'تکایە چاوەڕێ بکە...',
                        text: 'سڕینەوەی دابینکەر',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send delete request
                    fetch('../../process/delete_supplier.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ id: supplierId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            row.remove();
                            
                            // Update pagination
                            applySupplierPagination(1);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو بوو!',
                                text: data.message,
                                confirmButtonText: 'باشە'
                            });
                        } else {
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
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە: ' + error.message,
                            confirmButtonText: 'باشە'
                        });
                    });
                }
            });
        });
    });

    // Employee reset filter functionality
    document.getElementById('employeeResetFilter').addEventListener('click', function() {
        // Reset name filter
        document.getElementById('employeeNameFilter').value = '';
        
        // Reset phone filter
        document.getElementById('employeePhoneSearch').value = '';
        
        // Reset table search
        document.getElementById('employeeTableSearch').value = '';
        
        // Refresh employee list
        fetchEmployees();
    });

    // Customer reset filter functionality
    document.getElementById('customerResetFilter').addEventListener('click', function() {
        // Reset name filter
        document.getElementById('customerName').value = '';
        
        // Reset phone filter
        document.getElementById('customerPhone').value = '';
        
        // Reset table search
        document.getElementById('customerTableSearch').value = '';
        
        // Reset all rows to visible
        const customerRows = document.querySelectorAll('#customerTable tbody tr');
        customerRows.forEach(row => {
            row.dataset.filterMatch = 'true';
            row.dataset.searchMatch = 'true';
            row.style.display = '';
        });
        
        // Reapply pagination
        applyCustomerPagination(1);
    });

    // Supplier reset filter functionality
    document.getElementById('supplierResetFilter').addEventListener('click', function() {
        // Reset name filter
        document.getElementById('supplierName').value = '';
        
        // Reset phone filter
        document.getElementById('supplierPhone').value = '';
        
        // Reset table search
        document.getElementById('supplierTableSearch').value = '';
        
        // Reset all rows to visible
        const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
        supplierRows.forEach(row => {
            row.dataset.filterMatch = 'true';
            row.dataset.searchMatch = 'true';
            row.style.display = '';
        });
        
        // Reapply pagination
        applySupplierPagination(1);
    });

    // Add customer table search functionality
    document.getElementById('customerTableSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const customerRows = document.querySelectorAll('#customerTable tbody tr');
        
        customerRows.forEach(row => {
            let match = false;
            // Search in all cells except the last one (actions column)
            for (let i = 1; i < row.cells.length - 1; i++) {
                const cellText = row.cells[i].textContent.toLowerCase();
                if (cellText.includes(searchTerm)) {
                    match = true;
                    break;
                }
            }
            
            row.dataset.searchMatch = match ? 'true' : 'false';
            applyCustomerFilters();
        });
    });

    // Add supplier table search functionality
    document.getElementById('supplierTableSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
        
        supplierRows.forEach(row => {
            let match = false;
            // Search in all cells except the last one (actions column)
            for (let i = 1; i < row.cells.length - 1; i++) {
                const cellText = row.cells[i].textContent.toLowerCase();
                if (cellText.includes(searchTerm)) {
                    match = true;
                    break;
                }
            }
            
            row.dataset.searchMatch = match ? 'true' : 'false';
            applySupplierFilters();
        });
    });

    // Customer name filter functionality
    document.getElementById('customerName').addEventListener('change', function() {
        const selectedName = this.value.toLowerCase();
        const customerRows = document.querySelectorAll('#customerTable tbody tr');
        
        customerRows.forEach(row => {
            const nameCell = row.cells[1]; // Name is in the second column
            const name = nameCell.textContent.toLowerCase();
            
            // Match if filter is empty or name matches
            const match = !selectedName || name === selectedName;
            row.dataset.nameFilterMatch = match ? 'true' : 'false';
            
            applyCustomerFilters();
        });
    });

    // Customer phone filter functionality
    document.getElementById('customerPhone').addEventListener('input', function() {
        const phoneFilter = this.value.toLowerCase();
        const customerRows = document.querySelectorAll('#customerTable tbody tr');
        
        customerRows.forEach(row => {
            const phoneCell = row.cells[2]; // Phone is in the third column
            const phone = phoneCell.textContent.toLowerCase();
            
            // Match if filter is empty or phone includes the filter
            const match = !phoneFilter || phone.includes(phoneFilter);
            row.dataset.phoneFilterMatch = match ? 'true' : 'false';
            
            applyCustomerFilters();
        });
    });

    // Supplier name filter functionality
    document.getElementById('supplierName').addEventListener('change', function() {
        const selectedName = this.value.toLowerCase();
        const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
        
        supplierRows.forEach(row => {
            const nameCell = row.cells[1]; // Name is in the second column
            const name = nameCell.textContent.toLowerCase();
            
            // Match if filter is empty or name matches
            const match = !selectedName || name === selectedName;
            row.dataset.nameFilterMatch = match ? 'true' : 'false';
            
            applySupplierFilters();
        });
    });

    // Supplier phone filter functionality
    document.getElementById('supplierPhone').addEventListener('input', function() {
        const phoneFilter = this.value.toLowerCase();
        const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
        
        supplierRows.forEach(row => {
            const phoneCell = row.cells[2]; // Phone is in the third column
            const phone = phoneCell.textContent.toLowerCase();
            
            // Match if filter is empty or phone includes the filter
            const match = !phoneFilter || phone.includes(phoneFilter);
            row.dataset.phoneFilterMatch = match ? 'true' : 'false';
            
            applySupplierFilters();
        });
    });

    // Function to apply all customer filters
    function applyCustomerFilters() {
        const customerRows = document.querySelectorAll('#customerTable tbody tr');
        let visibleCount = 0;
        
        customerRows.forEach(row => {
            // Default all filters to true if not set
            const nameMatch = row.dataset.nameFilterMatch !== 'false';
            const phoneMatch = row.dataset.phoneFilterMatch !== 'false';
            const searchMatch = row.dataset.searchMatch !== 'false';
            
            // Show row only if it matches all filters
            const isVisible = nameMatch && phoneMatch && searchMatch;
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) {
                visibleCount++;
            }
        });
        
        // Update pagination after filtering
        applyCustomerPagination(1);
    }

    // Function to apply all supplier filters
    function applySupplierFilters() {
        const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
        let visibleCount = 0;
        
        supplierRows.forEach(row => {
            // Default all filters to true if not set
            const nameMatch = row.dataset.nameFilterMatch !== 'false';
            const phoneMatch = row.dataset.phoneFilterMatch !== 'false';
            const searchMatch = row.dataset.searchMatch !== 'false';
            
            // Show row only if it matches all filters
            const isVisible = nameMatch && phoneMatch && searchMatch;
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) {
                visibleCount++;
            }
        });
        
        // Update pagination after filtering
        applySupplierPagination(1);
    }

    // Customer pagination functionality
    function applyCustomerPagination(page) {
        const recordsPerPage = parseInt(document.getElementById('customerRecordsPerPage').value);
        const customerRows = document.querySelectorAll('#customerTable tbody tr');
        const visibleRows = Array.from(customerRows).filter(row => row.style.display !== 'none');
        const totalVisibleRecords = visibleRows.length;
        
        // Calculate total pages
        const totalPages = Math.ceil(totalVisibleRecords / recordsPerPage);
        
        // Ensure page is within bounds
        page = Math.min(Math.max(1, page), totalPages || 1);
        
        // Show only rows for current page
        visibleRows.forEach((row, index) => {
            const startIndex = (page - 1) * recordsPerPage;
            const endIndex = startIndex + recordsPerPage - 1;
            
            row.style.display = (index >= startIndex && index <= endIndex) ? '' : 'none';
        });
        
        // Update pagination info
        const startRecord = totalVisibleRecords > 0 ? (page - 1) * recordsPerPage + 1 : 0;
        const endRecord = Math.min(page * recordsPerPage, totalVisibleRecords);
        
        document.getElementById('customerStartRecord').textContent = startRecord;
        document.getElementById('customerEndRecord').textContent = endRecord;
        document.getElementById('customerTotalRecords').textContent = totalVisibleRecords;
        
        // Update pagination buttons
        const prevPageBtn = document.getElementById('customerPrevPageBtn');
        const nextPageBtn = document.getElementById('customerNextPageBtn');
        
        prevPageBtn.disabled = page === 1;
        nextPageBtn.disabled = page === totalPages || totalPages === 0;
        
        // Update pagination numbers
        const paginationNumbers = document.getElementById('customerPaginationNumbers');
        paginationNumbers.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `btn btn-sm ${i === page ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => applyCustomerPagination(i));
            paginationNumbers.appendChild(pageBtn);
        }
        
        return page;
    }

    // Supplier pagination functionality
    function applySupplierPagination(page) {
        const recordsPerPage = parseInt(document.getElementById('supplierRecordsPerPage').value);
        const supplierRows = document.querySelectorAll('#supplierTable tbody tr');
        const visibleRows = Array.from(supplierRows).filter(row => row.style.display !== 'none');
        const totalVisibleRecords = visibleRows.length;
        
        // Calculate total pages
        const totalPages = Math.ceil(totalVisibleRecords / recordsPerPage);
        
        // Ensure page is within bounds
        page = Math.min(Math.max(1, page), totalPages || 1);
        
        // Show only rows for current page
        visibleRows.forEach((row, index) => {
            const startIndex = (page - 1) * recordsPerPage;
            const endIndex = startIndex + recordsPerPage - 1;
            
            row.style.display = (index >= startIndex && index <= endIndex) ? '' : 'none';
        });
        
        // Update pagination info
        const startRecord = totalVisibleRecords > 0 ? (page - 1) * recordsPerPage + 1 : 0;
        const endRecord = Math.min(page * recordsPerPage, totalVisibleRecords);
        
        document.getElementById('supplierStartRecord').textContent = startRecord;
        document.getElementById('supplierEndRecord').textContent = endRecord;
        document.getElementById('supplierTotalRecords').textContent = totalVisibleRecords;
        
        // Update pagination buttons
        const prevPageBtn = document.getElementById('supplierPrevPageBtn');
        const nextPageBtn = document.getElementById('supplierNextPageBtn');
        
        prevPageBtn.disabled = page === 1;
        nextPageBtn.disabled = page === totalPages || totalPages === 0;
        
        // Update pagination numbers
        const paginationNumbers = document.getElementById('supplierPaginationNumbers');
        paginationNumbers.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `btn btn-sm ${i === page ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => applySupplierPagination(i));
            paginationNumbers.appendChild(pageBtn);
        }
        
        return page;
    }

    // Add records per page change handlers
    document.getElementById('customerRecordsPerPage').addEventListener('change', function() {
        applyCustomerPagination(1);
    });
    
    document.getElementById('supplierRecordsPerPage').addEventListener('change', function() {
        applySupplierPagination(1);
    });
    
    // Add pagination next/prev button handlers
    document.getElementById('customerPrevPageBtn').addEventListener('click', function() {
        const currentActivePage = document.querySelector('#customerPaginationNumbers .btn-primary');
        if (currentActivePage) {
            const currentPage = parseInt(currentActivePage.textContent);
            applyCustomerPagination(currentPage - 1);
        }
    });
    
    document.getElementById('customerNextPageBtn').addEventListener('click', function() {
        const currentActivePage = document.querySelector('#customerPaginationNumbers .btn-primary');
        if (currentActivePage) {
            const currentPage = parseInt(currentActivePage.textContent);
            applyCustomerPagination(currentPage + 1);
        }
    });
    
    document.getElementById('supplierPrevPageBtn').addEventListener('click', function() {
        const currentActivePage = document.querySelector('#supplierPaginationNumbers .btn-primary');
        if (currentActivePage) {
            const currentPage = parseInt(currentActivePage.textContent);
            applySupplierPagination(currentPage - 1);
        }
    });
    
    document.getElementById('supplierNextPageBtn').addEventListener('click', function() {
        const currentActivePage = document.querySelector('#supplierPaginationNumbers .btn-primary');
        if (currentActivePage) {
            const currentPage = parseInt(currentActivePage.textContent);
            applySupplierPagination(currentPage + 1);
        }
    });
    
    // Initialize customer and supplier pagination
    applyCustomerPagination(1);
    applySupplierPagination(1);

    // Add supplier edit button click handlers
    document.querySelectorAll('#supplierTable .edit-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent the default modal opening behavior
            const supplierId = this.getAttribute('data-id');
            
            // Show loading
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'زانیاری دابینکەر بار دەکرێت',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fetch supplier data
            fetch(`../../process/get_supplier.php?id=${supplierId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        // Fill the edit form with supplier data
                        document.getElementById('editSupplierId').value = data.supplier.id;
                        document.getElementById('editSupplierName').value = data.supplier.name;
                        document.getElementById('editSupplierPhone1').value = data.supplier.phone1;
                        document.getElementById('editSupplierPhone2').value = data.supplier.phone2 || '';
                        document.getElementById('editSupplierDebt').value = data.supplier.debt_on_myself || 0;
                        document.getElementById('editSupplierNotes').value = data.supplier.notes || '';
                        
                        // Open the modal
                        const editSupplierModal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
                        editSupplierModal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: data.message || 'دابینکەر نەدۆزرایەوە',
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
        });
    });

    // Add employee table search functionality
    document.getElementById('employeeTableSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const employeeRows = document.querySelectorAll('#employeeTable tbody tr');
        
        employeeRows.forEach(row => {
            let match = false;
            // Search in all cells except the last one (actions column)
            for (let i = 1; i < row.cells.length - 1; i++) {
                const cellText = row.cells[i].textContent.toLowerCase();
                if (cellText.includes(searchTerm)) {
                    match = true;
                    break;
                }
            }
            
            row.dataset.searchMatch = match ? 'true' : 'false';
            row.style.display = match ? '' : 'none';
        });

        // Update pagination after search
        applyEmployeePagination(1);
    });

    // Function to apply employee pagination
    function applyEmployeePagination(page) {
        const recordsPerPage = parseInt(document.getElementById('employeeRecordsPerPage').value);
        const employeeRows = document.querySelectorAll('#employeeTable tbody tr');
        const visibleRows = Array.from(employeeRows).filter(row => row.style.display !== 'none');
        const totalVisibleRecords = visibleRows.length;
        
        // Calculate total pages
        const totalPages = Math.ceil(totalVisibleRecords / recordsPerPage);
        
        // Ensure page is within bounds
        page = Math.min(Math.max(1, page), totalPages || 1);
        
        // Show only rows for current page
        visibleRows.forEach((row, index) => {
            const startIndex = (page - 1) * recordsPerPage;
            const endIndex = startIndex + recordsPerPage - 1;
            
            row.style.display = (index >= startIndex && index <= endIndex) ? '' : 'none';
        });
        
        // Update pagination info
        const startRecord = totalVisibleRecords > 0 ? (page - 1) * recordsPerPage + 1 : 0;
        const endRecord = Math.min(page * recordsPerPage, totalVisibleRecords);
        
        document.getElementById('employeeStartRecord').textContent = startRecord;
        document.getElementById('employeeEndRecord').textContent = endRecord;
        document.getElementById('employeeTotalRecords').textContent = totalVisibleRecords;
        
        // Update pagination buttons
        const prevPageBtn = document.getElementById('employeePrevPageBtn');
        const nextPageBtn = document.getElementById('employeeNextPageBtn');
        
        prevPageBtn.disabled = page === 1;
        nextPageBtn.disabled = page === totalPages || totalPages === 0;
        
        // Update pagination numbers
        const paginationNumbers = document.getElementById('employeePaginationNumbers');
        paginationNumbers.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `btn btn-sm ${i === page ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => applyEmployeePagination(i));
            paginationNumbers.appendChild(pageBtn);
        }
        
        return page;
    }

    // Add employee pagination controls
    document.getElementById('employeeRecordsPerPage').addEventListener('change', function() {
        applyEmployeePagination(1);
    });

    document.getElementById('employeePrevPageBtn').addEventListener('click', function() {
        const currentActivePage = document.querySelector('#employeePaginationNumbers .btn-primary');
        if (currentActivePage) {
            const currentPage = parseInt(currentActivePage.textContent);
            applyEmployeePagination(currentPage - 1);
        }
    });

    document.getElementById('employeeNextPageBtn').addEventListener('click', function() {
        const currentActivePage = document.querySelector('#employeePaginationNumbers .btn-primary');
        if (currentActivePage) {
            const currentPage = parseInt(currentActivePage.textContent);
            applyEmployeePagination(currentPage + 1);
        }
    });

    // Initialize employee pagination
    applyEmployeePagination(1);
});

// Business Partners Tab Functionality
function initializeBusinessPartnersTab() {
    // Table functionality
    initializeDataTable('partner');
    
    // Filter functionality
    $('#partnerName, #partnerPhone').on('change keyup', function() {
        filterPartnerTable();
    });
    
    // Reset filter button
    $('#partnerResetFilter').on('click', function() {
        $('#partnerName').val('');
        $('#partnerPhone').val('');
        filterPartnerTable();
    });
    
    // Refresh button
    $('#business-partner-content .refresh-btn').on('click', function() {
        location.reload();
    });
    
    // Add delete functionality for business partners
    $('.delete-partner-btn').on('click', function() {
        const partnerId = $(this).data('id');
        const customerId = $(this).data('customer-id');
        const supplierId = $(this).data('supplier-id');
        const partnerName = $(this).data('name');
        
        deleteBusinessPartner(partnerId, customerId, supplierId, partnerName);
    });
}

// Function to delete a business partner
function deleteBusinessPartner(partnerId, customerId, supplierId, partnerName) {
    Swal.fire({
        title: 'دڵنیای لە سڕینەوەی ئەم کڕیار و دابینکەرە؟',
        text: `دەتەوێت "${partnerName}" بسڕیتەوە؟ ئەم کردارە ناتوانرێت گەڕێنرێتەوە!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'تکایە چاوەڕێ بکە...',
                text: 'سڕینەوەی کڕیار و دابینکەر بەردەوامە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Prepare data to send
            const data = {
                id: partnerId
            };
            
            if (customerId) data.customer_id = customerId;
            if (supplierId) data.supplier_id = supplierId;
            
            // Send delete request
            fetch('../../process/delete_business_partner.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو بوو!',
                        text: data.message || 'کڕیار و دابینکەر بە سەرکەوتوویی سڕایەوە',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        // Refresh the page to show updated data
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: data.message || 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی کڕیار و دابینکەر',
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
    });
}

// Filter partners table based on selected criteria
function filterPartnerTable() {
    const nameFilter = $('#partnerName').val().toLowerCase();
    const phoneFilter = $('#partnerPhone').val().toLowerCase();
    
    $('#partnerTable tbody tr').each(function() {
        const name = $(this).find('td:eq(1)').text().toLowerCase();
        const phone = $(this).find('td:eq(2)').text().toLowerCase();
        
        // Show row if it matches all selected filters
        const nameMatch = nameFilter === '' || name.includes(nameFilter);
        const phoneMatch = phoneFilter === '' || phone.includes(phoneFilter);
        
        if (nameMatch && phoneMatch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    
    // Update pagination after filtering
    updateTableInfo('partner');
}

// Initialize all tabs when document ready
$(document).ready(function() {
    // Initialize tabs without explicitly defining functions
    // The functionality for these has already been set up above
    
    // Initialize new business partners tab
    initializeBusinessPartnersTab();
    
    // Make sure the table is responsive
    $(window).resize(function() {
        updateTableInfo('partner');
    });
});

// Helper function for data tables
function initializeDataTable(prefix) {
    // Set up table search
    $(`#${prefix}TableSearch`).on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterTable(prefix, searchTerm);
    });
    
    // Set up records per page
    $(`#${prefix}RecordsPerPage`).on('change', function() {
        updateTableInfo(prefix);
    });
    
    // Set up pagination
    $(`#${prefix}PrevPageBtn`).on('click', function() {
        const currentPage = parseInt($(`#${prefix}PaginationNumbers .btn-primary`).text());
        if (currentPage > 1) {
            navigateToPage(prefix, currentPage - 1);
        }
    });
    
    $(`#${prefix}NextPageBtn`).on('click', function() {
        const currentPage = parseInt($(`#${prefix}PaginationNumbers .btn-primary`).text());
        const totalPages = Math.ceil($(`#${prefix}Table tbody tr:visible`).length / parseInt($(`#${prefix}RecordsPerPage`).val()));
        if (currentPage < totalPages) {
            navigateToPage(prefix, currentPage + 1);
        }
    });
    
    // Initialize pagination
    updateTableInfo(prefix);
}

// Filter table based on search
function filterTable(prefix, searchTerm) {
    $(`#${prefix}Table tbody tr`).each(function() {
        let match = false;
        $(this).find('td').each(function() {
            if ($(this).text().toLowerCase().includes(searchTerm)) {
                match = true;
                return false; // break the loop
            }
        });
        $(this).toggle(match);
    });
    
    // Reset to page 1 after filtering
    updateTableInfo(prefix);
}

// Update table pagination info
function updateTableInfo(prefix) {
    const recordsPerPage = parseInt($(`#${prefix}RecordsPerPage`).val());
    const visibleRows = $(`#${prefix}Table tbody tr:visible`);
    const totalRecords = visibleRows.length;
    const totalPages = Math.ceil(totalRecords / recordsPerPage);
    
    // Show page 1 initially
    navigateToPage(prefix, 1);
    
    // Update pagination numbers
    const paginationNumbers = $(`#${prefix}PaginationNumbers`);
    paginationNumbers.empty();
    
    for (let i = 1; i <= totalPages; i++) {
        const pageBtn = $(`<button class="btn btn-sm ${i === 1 ? 'btn-primary' : 'btn-outline-primary'} rounded-circle me-2">${i}</button>`);
        pageBtn.on('click', function() {
            navigateToPage(prefix, i);
        });
        paginationNumbers.append(pageBtn);
    }
    
    // Update pagination info text
    $(`#${prefix}TotalRecords`).text(totalRecords);
    
    // Update pagination buttons
    $(`#${prefix}PrevPageBtn`).prop('disabled', totalPages <= 1);
    $(`#${prefix}NextPageBtn`).prop('disabled', totalPages <= 1);
}

// Navigate to specific page
function navigateToPage(prefix, pageNumber) {
    const recordsPerPage = parseInt($(`#${prefix}RecordsPerPage`).val());
    const visibleRows = $(`#${prefix}Table tbody tr:visible`);
    const totalRecords = visibleRows.length;
    const totalPages = Math.ceil(totalRecords / recordsPerPage);
    
    // Validate page number
    if (pageNumber < 1) pageNumber = 1;
    if (pageNumber > totalPages) pageNumber = totalPages;
    
    // Calculate start and end index
    const startIndex = (pageNumber - 1) * recordsPerPage;
    const endIndex = Math.min(startIndex + recordsPerPage, totalRecords);
    
    // Hide all rows first
    visibleRows.hide();
    
    // Show only rows for current page
    visibleRows.slice(startIndex, endIndex).show();
    
    // Update pagination info
    $(`#${prefix}StartRecord`).text(totalRecords ? startIndex + 1 : 0);
    $(`#${prefix}EndRecord`).text(endIndex);
    
    // Update active page in pagination
    $(`#${prefix}PaginationNumbers button`).removeClass('btn-primary').addClass('btn-outline-primary');
    $(`#${prefix}PaginationNumbers button:nth-child(${pageNumber})`).removeClass('btn-outline-primary').addClass('btn-primary');
    
    // Update prev/next buttons
    $(`#${prefix}PrevPageBtn`).prop('disabled', pageNumber === 1);
    $(`#${prefix}NextPageBtn`).prop('disabled', pageNumber === totalPages || totalPages === 0);
}