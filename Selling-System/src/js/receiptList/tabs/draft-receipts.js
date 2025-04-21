// Draft Receipts Management
$(document).ready(function() {
    // Handle draft receipt finalization
    $(document).on('click', '.finalize-btn', function() {
        const receiptId = $(this).data('id');
        Swal.fire({
            title: 'دڵنیای لە تەواوکردنی پسووڵە؟',
            text: 'پاش تەواوکردن ناتوانیت گۆڕانکاری بکەیت',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، تەواو بکە',
            cancelButtonText: 'نەخێر، هەڵوەشانەوە'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../api/receipts/finalize_draft.php',
                    method: 'POST',
                    data: { receipt_id: receiptId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'سەرکەوتوو!',
                                text: 'پسووڵە بە سەرکەوتوویی تەواو کرا',
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

    // Handle draft receipt deletion
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
                    url: '../../api/receipts/delete_draft.php',
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

    // Handle draft receipt view
    $(document).on('click', '.view-btn', function() {
        const receiptId = $(this).data('id');
        window.location.href = `../../src/views/admin/view_draft.php?id=${receiptId}`;
    });

    // Handle draft receipt edit
    $(document).on('click', '.edit-btn', function() {
        const receiptId = $(this).data('id');
        window.location.href = `../../src/views/admin/edit_draft.php?id=${receiptId}`;
    });

    // Handle date filter changes
    $('#draftStartDate, #draftEndDate').on('change', function() {
        const startDate = $('#draftStartDate').val();
        const endDate = $('#draftEndDate').val();
        
        if (startDate && endDate) {
            $.ajax({
                url: '../../api/receipts/filter_drafts.php',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success) {
                        updateDraftTable(response.data);
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
    $('#draftResetFilter').on('click', function() {
        $('#draftStartDate').val('');
        $('#draftEndDate').val('');
        location.reload();
    });

    // Function to update draft table with new data
    function updateDraftTable(data) {
        const tbody = $('#draftHistoryTable tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`<tr><td colspan="7" class="text-center">هیچ داتایەک نەدۆزرایەوە</td></tr>`);
            return;
        }

        data.forEach((draft, index) => {
            const row = `<tr>
                <td>${index + 1}</td>
                <td>${draft.date}</td>
                <td class="products-list-cell" data-products="${draft.products_list}">
                    ${draft.products_list}
                    <div class="products-popup"></div>
                </td>
                <td>${formatCurrency(draft.total_amount)}</td>
                <td>${draft.notes || ''}</td>
                <td>${draft.status}</td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-btn" data-id="${draft.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle edit-btn" data-id="${draft.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-circle finalize-btn" data-id="${draft.id}">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn" data-id="${draft.id}">
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