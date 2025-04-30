<?php
// Include authentication check
require_once 'includes/auth.php';
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all sales with customer information
$salesQuery = "SELECT s.*, c.name as customer_name, c.phone1 as customer_phone
               FROM sales s 
               JOIN customers c ON s.customer_id = c.id
               ORDER BY s.date DESC";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute();
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیستی پسووڵەکان</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <style>
        /* Custom styles */
        .receipt-list-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-align: right !important;
        }
        
        .table tbody td {
            text-align: right !important;
        }
        
        .table td:last-child {
            text-align: center !important;
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
        }
        
        .badge-paid {
            background-color: #28a745;
            color: white;
        }
        
        .badge-unpaid {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-partial {
            background-color: #ffc107;
            color: #343a40;
        }
        
        .badge-draft {
            background-color: #6c757d;
            color: white;
        }
        
        .action-buttons .btn {
            margin: 0 2px;
        }
        
        .action-buttons .btn i {
            font-size: 0.875rem;
        }
        
        /* DataTables RTL Support */
        .dataTables_filter {
            text-align: left !important;
        }
        
        .dataTables_length {
            text-align: right !important;
        }
        
        .dataTables_info {
            text-align: right !important;
        }
        
        .dataTables_paginate {
            text-align: left !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .receipt-list-header {
                padding: 15px;
            }
            
            .table td {
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main content -->
        <div class="container-fluid py-4">
            <div class="receipt-list-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">لیستی پسووڵەکان</h4>
                    <button class="btn btn-primary" id="newReceiptBtn">
                        <i class="fas fa-plus"></i> پسووڵەی نوێ
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="receiptsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ژمارەی پسووڵە</th>
                                    <th>کڕیار</th>
                                    <th>ژمارەی مۆبایل</th>
                                    <th>بەروار</th>
                                    <th>جۆری پارەدان</th>
                                    <th>بڕی پسووڵە</th>
                                    <th>بڕی پارەدراو</th>
                                    <th>بڕی ماوە</th>
                                    <th>دۆخی پسووڵە</th>
                                    <th>کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales as $sale): ?>
                                    <?php
                                    $status = '';
                                    if ($sale['is_draft']) {
                                        $status = '<span class="badge badge-draft">ڕەشنووس</span>';
                                    } else if ($sale['payment_type'] == 'cash') {
                                        $status = '<span class="badge badge-paid">پارەدراوە</span>';
                                    } else if ($sale['remaining_amount'] == 0) {
                                        $status = '<span class="badge badge-paid">پارەدراوە</span>';
                                    } else if ($sale['paid_amount'] > 0) {
                                        $status = '<span class="badge badge-partial">بەشێکی دراوە</span>';
                                    } else {
                                        $status = '<span class="badge badge-unpaid">پارە نەدراوە</span>';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['customer_phone']); ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($sale['date'])); ?></td>
                                        <td><?php echo $sale['payment_type'] == 'cash' ? 'نەقد' : 'قەرز'; ?></td>
                                        <td><?php echo number_format($sale['paid_amount'] + $sale['remaining_amount'], 2); ?> $</td>
                                        <td><?php echo number_format($sale['paid_amount'], 2); ?> $</td>
                                        <td><?php echo number_format($sale['remaining_amount'], 2); ?> $</td>
                                        <td><?php echo $status; ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-info btn-sm view-receipt" data-receipt-id="<?php echo $sale['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($sale['is_draft']): ?>
                                                <button class="btn btn-success btn-sm finalize-receipt" data-receipt-id="<?php echo $sale['id']; ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm delete-receipt" data-receipt-id="<?php echo $sale['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const receiptsTable = $('#receiptsTable').DataTable({
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Kurdish.json'
                },
                order: [[3, 'desc']], // Sort by date column by default
                columnDefs: [
                    { className: "dt-right", targets: "_all" },
                    { className: "dt-center", targets: 9 } // Action column
                ]
            });

            // New Receipt button handler
            $('#newReceiptBtn').on('click', function() {
                window.location.href = 'newReceipt.php';
            });

            // View Receipt button handler
            $(document).on('click', '.view-receipt', function() {
                const receiptId = $(this).data('receipt-id');
                window.location.href = `viewReceipt.php?id=${receiptId}`;
            });

            // Finalize Receipt button handler
            $(document).on('click', '.finalize-receipt', function() {
                const receiptId = $(this).data('receipt-id');
                Swal.fire({
                    title: 'دڵنیای؟',
                    text: 'ئایا دڵنیای لە پەسەندکردنی پسووڵەکە؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Send AJAX request to finalize receipt
                        $.ajax({
                            url: 'api/finalizeReceipt.php',
                            method: 'POST',
                            data: { receipt_id: receiptId },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'سەرکەوتوو',
                                        text: 'پسووڵەکە بە سەرکەوتوویی پەسەندکرا',
                                        icon: 'success'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'هەڵە',
                                        text: response.message || 'هەڵەیەک ڕوویدا',
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'هەڵە',
                                    text: 'هەڵەیەک ڕوویدا لە کاتی پەسەندکردنی پسووڵەکە',
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });

            // Delete Receipt button handler
            $(document).on('click', '.delete-receipt', function() {
                const receiptId = $(this).data('receipt-id');
                Swal.fire({
                    title: 'دڵنیای؟',
                    text: 'ئایا دڵنیای لە سڕینەوەی پسووڵەکە؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Send AJAX request to delete receipt
                        $.ajax({
                            url: 'api/deleteReceipt.php',
                            method: 'POST',
                            data: { receipt_id: receiptId },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'سەرکەوتوو',
                                        text: 'پسووڵەکە بە سەرکەوتوویی سڕایەوە',
                                        icon: 'success'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'هەڵە',
                                        text: response.message || 'هەڵەیەک ڕوویدا',
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'هەڵە',
                                    text: 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی پسووڵەکە',
                                    icon: 'error'
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