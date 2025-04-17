<?php
// Include database connection
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕەشنووسی پسوڵەکان</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/sales.css">
    <style>
        .draft-badge {
            background-color: #FFC107;
            color: #333;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
        }
        .actions-column {
            min-width: 200px;
        }
        .draft-table th, .draft-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="main-content">
        <div id="navbar-container"></div>
        
        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>
        
        <div class="container mt-4">
            <div class="card card-qiuck-style">
                <div class="card-body">
                    <h2 class="mb-4"><i class="fas fa-file-alt me-2"></i> ڕەشنووسی پسوڵەکان</h2>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        پسوڵانەی کە بە ڕەشنووس تۆمار کراون و هێشتا لەسەر حیساباتی کۆگاکە ئەژمار نەکراون. ئەم پسوڵانە کاریگەریان نییە لەسەر ئامار و عەمباری کۆگاکە.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover draft-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ژمارەی پسوڵە</th>
                                    <th>کڕیار</th>
                                    <th>بەروار</th>
                                    <th>جۆری پارەدان</th>
                                    <th>کۆی گشتی</th>
                                    <th class="actions-column">کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get draft receipts
                                $stmt = $conn->prepare("
                                    SELECT s.*, c.name as customer_name,
                                    COALESCE(SUM(si.total_price), 0) as subtotal,
                                    (COALESCE(SUM(si.total_price), 0) + s.shipping_cost + s.other_costs - s.discount) as total_amount
                                    FROM sales s
                                    LEFT JOIN customers c ON s.customer_id = c.id
                                    LEFT JOIN sale_items si ON s.id = si.sale_id
                                    WHERE s.is_draft = 1
                                    GROUP BY s.id
                                    ORDER BY s.created_at DESC
                                ");
                                $stmt->execute();
                                $draft_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($draft_receipts) > 0) {
                                    foreach ($draft_receipts as $index => $receipt) {
                                        echo '<tr>';
                                        echo '<td>' . ($index + 1) . '</td>';
                                        echo '<td>' . htmlspecialchars($receipt['invoice_number']) . ' <span class="draft-badge">ڕەشنووس</span></td>';
                                        echo '<td>' . htmlspecialchars($receipt['customer_name']) . '</td>';
                                        echo '<td>' . date('Y-m-d', strtotime($receipt['date'])) . '</td>';
                                        echo '<td>' . ($receipt['payment_type'] == 'cash' ? 'نەقد' : 'قەرز') . '</td>';
                                        echo '<td>' . number_format($receipt['total_amount']) . ' دینار</td>';
                                        echo '<td class="actions-column">
                                            <button class="btn btn-sm btn-info view-btn" data-id="' . $receipt['id'] . '">
                                                <i class="fas fa-eye"></i> بینین
                                            </button>
                                            <button class="btn btn-sm btn-primary edit-btn" data-id="' . $receipt['id'] . '">
                                                <i class="fas fa-edit"></i> دەستکاری
                                            </button>
                                            <button class="btn btn-sm btn-success finalize-btn" data-id="' . $receipt['id'] . '">
                                                <i class="fas fa-check"></i> پەسەندکردن
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn" data-id="' . $receipt['id'] . '">
                                                <i class="fas fa-trash"></i> سڕینەوە
                                            </button>
                                        </td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">هیچ ڕەشنووسێک نەدۆزرایەوە</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Receipt Modal -->
    <div class="modal fade" id="viewReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">بینینی پسوڵە</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="receipt-details">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="print-receipt-btn">
                        <i class="fas fa-print"></i> چاپکردن
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../js/include-components.js"></script>
    
    <script>
        $(document).ready(function() {
            // View receipt details
            $('.view-btn').on('click', function() {
                const receiptId = $(this).data('id');
                
                // Show loading
                $('#receipt-details').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> جاوەڕوان بە...</div>');
                
                // Show modal
                $('#viewReceiptModal').modal('show');
                
                // Load receipt details
                $.ajax({
                    url: '../../api/get_receipt_details.php',
                    type: 'POST',
                    data: {
                        id: receiptId,
                        type: 'selling'
                    },
                    success: function(response) {
                        if (response.success) {
                            let html = `
                                <div class="receipt-header mb-3">
                                    <h4>ژمارەی پسوڵە: ${response.data.header.invoice_number} <span class="draft-badge">ڕەشنووس</span></h4>
                                    <p>کڕیار: ${response.data.header.customer_name}</p>
                                    <p>بەروار: ${response.data.header.date}</p>
                                    <p>جۆری پارەدان: ${response.data.header.payment_type === 'cash' ? 'نەقد' : 'قەرز'}</p>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>کاڵا</th>
                                                <th>یەکە</th>
                                                <th>نرخی یەکە</th>
                                                <th>بڕ</th>
                                                <th>کۆی گشتی</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                            
                            response.data.items.forEach((item, index) => {
                                html += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.product_name}</td>
                                        <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                                        <td>${item.unit_price}</td>
                                        <td>${item.quantity}</td>
                                        <td>${item.total_price}</td>
                                    </tr>`;
                            });
                            
                            html += `
                                        </tbody>
                                    </table>
                                </div>
                                <div class="totals mt-3">
                                    <div class="row">
                                        <div class="col-md-6 ms-auto">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>کۆی کاڵاکان:</th>
                                                    <td>${response.data.totals.subtotal} دینار</td>
                                                </tr>
                                                <tr>
                                                    <th>تێچووی گواستنەوە:</th>
                                                    <td>${response.data.header.shipping_cost} دینار</td>
                                                </tr>
                                                <tr>
                                                    <th>تێچووی تر:</th>
                                                    <td>${response.data.header.other_costs} دینار</td>
                                                </tr>
                                                <tr>
                                                    <th>داشکاندن:</th>
                                                    <td>${response.data.header.discount} دینار</td>
                                                </tr>
                                                <tr class="table-primary">
                                                    <th>کۆی گشتی:</th>
                                                    <td>${response.data.totals.grand_total} دینار</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            $('#receipt-details').html(html);
                            $('#print-receipt-btn').data('id', receiptId);
                        } else {
                            $('#receipt-details').html(`<div class="alert alert-danger">${response.message}</div>`);
                        }
                    },
                    error: function() {
                        $('#receipt-details').html('<div class="alert alert-danger">هەڵەیەک ڕوویدا لە وەرگرتنی زانیاری پسوڵە</div>');
                    }
                });
            });
            
            // Print receipt
            $('#print-receipt-btn').on('click', function() {
                const receiptId = $(this).data('id');
                window.open(`../../views/receipt/print_receipt.php?sale_id=${receiptId}&is_draft=1`, '_blank');
            });
            
            // Edit draft receipt
            $('.edit-btn').on('click', function() {
                const receiptId = $(this).data('id');
                window.location.href = `editReceipt.php?id=${receiptId}&type=selling&is_draft=1`;
            });
            
            // Finalize draft receipt
            $('.finalize-btn').on('click', function() {
                const receiptId = $(this).data('id');
                const row = $(this).closest('tr');
                
                Swal.fire({
                    title: 'دڵنیای؟',
                    text: 'ئەم ڕەشنووسە دەبێتە پسوڵەیەکی ڕاستەقینە و کاریگەری دەبێت لەسەر عەمباری کۆگاکە. ئایا دڵنیایت؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ، پەسەندی بکە',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'جاری پەسەندکردن...',
                            text: 'تکایە چاوەڕوان بە',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Call API to finalize draft
                        $.ajax({
                            url: '../../api/finalize_draft.php',
                            type: 'POST',
                            data: {
                                id: receiptId,
                                type: 'selling'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'پەسەندکرا!',
                                        text: 'ڕەشنووسەکە بووە بە پسوڵەیەکی پەسەندکراو.'
                                    }).then(() => {
                                        // Remove row from table
                                        row.fadeOut(500, function() {
                                            $(this).remove();
                                            
                                            // If no more rows, show "no drafts" message
                                            if ($('.draft-table tbody tr').length === 0) {
                                                $('.draft-table tbody').html('<tr><td colspan="7" class="text-center">هیچ ڕەشنووسێک نەدۆزرایەوە</td></tr>');
                                            }
                                        });
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: 'هەڵەیەک ڕوویدا لە پەسەندکردنی ڕەشنووس'
                                });
                            }
                        });
                    }
                });
            });
            
            // Delete draft receipt
            $('.delete-btn').on('click', function() {
                const receiptId = $(this).data('id');
                const row = $(this).closest('tr');
                
                Swal.fire({
                    title: 'دڵنیای؟',
                    text: 'ئەم ڕەشنووسە دەسڕێتەوە و ناتوانرێت بگەڕێنرێتەوە!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ، بیسڕەوە',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'جاری سڕینەوە...',
                            text: 'تکایە چاوەڕوان بە',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Call API to delete draft
                        $.ajax({
                            url: '../../api/delete_draft.php',
                            type: 'POST',
                            data: {
                                id: receiptId,
                                type: 'selling'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'سڕایەوە!',
                                        text: 'ڕەشنووسەکە بە سەرکەوتوویی سڕایەوە.'
                                    }).then(() => {
                                        // Remove row from table
                                        row.fadeOut(500, function() {
                                            $(this).remove();
                                            
                                            // If no more rows, show "no drafts" message
                                            if ($('.draft-table tbody tr').length === 0) {
                                                $('.draft-table tbody').html('<tr><td colspan="7" class="text-center">هیچ ڕەشنووسێک نەدۆزرایەوە</td></tr>');
                                            }
                                        });
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: 'هەڵەیەک ڕوویدا لە سڕینەوەی ڕەشنووس'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 