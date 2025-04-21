// Wasting Receipts Management
$(document).ready(function() {
    // Handle wasting receipt deletion
    $(document).on('click', '.delete-btn', function() {
        const receiptId = $(this).data('id');
        Swal.fire({
            title: 'دڵنیای لە سڕینەوەی پسووڵە؟',
            text: 'پاش سڕینەوە ناتوانیت گەڕایەوە',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'نەخێر، هەڵوەشانەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../src/api/receipts/delete_wasting.php',
                    method: 'POST',
                    data: { receipt_id: receiptId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو!',
                                text: 'پسووڵە بە سەرکەوتوویی سڕایەوە',
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا',
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });

    // Handle wasting receipt view
    $(document).on('click', '.view-btn', function() {
        const receiptId = $(this).data('id');
        window.location.href = `../../src/views/admin/view_wasting.php?id=${receiptId}`;
    });

    // Handle date filter changes
    $('#withdrawalStartDate, #withdrawalEndDate').on('change', function() {
        const startDate = $('#withdrawalStartDate').val();
        const endDate = $('#withdrawalEndDate').val();
        
        if (startDate && endDate) {
            $.ajax({
                url: '../../src/api/receipts/filter_wastings.php',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success) {
                        updateWastingTable(response.data);
                    } else {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: response.message || 'هەڵەیەک ڕوویدا',
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە سێرڤەرەوە',
                        icon: 'error'
                    });
                }
            });
        }
    });

    // Handle reset filter
    $('#withdrawalResetFilter').on('click', function() {
        $('#withdrawalStartDate').val('');
        $('#withdrawalEndDate').val('');
        location.reload();
    });

    // Function to update wasting table with new data
    function updateWastingTable(data) {
        const tbody = $('#withdrawalHistoryTable tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`<tr><td colspan="6" class="text-center">هیچ داتایەک نەدۆزرایەوە</td></tr>`);
            return;
        }

        data.forEach((wasting, index) => {
            const row = `<tr>
                <td>${index + 1}</td>
                <td>${wasting.date}</td>
                <td class="products-list-cell" data-products="${wasting.products_list}">
                    ${wasting.products_list}
                    <div class="products-popup"></div>
                </td>
                <td>${formatCurrency(wasting.total_amount)}</td>
                <td>${wasting.notes || ''}</td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${wasting.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${wasting.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
            tbody.append(row);
        });

        // Reinitialize product hover functionality
        initProductsListHover();
    }
}); 