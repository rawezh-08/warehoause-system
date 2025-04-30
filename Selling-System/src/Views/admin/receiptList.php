<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all sales with details
$salesQuery = "SELECT s.*, 
               p.name as product_name,
               p.code as product_code,
               si.quantity,
               si.unit_type,
               si.unit_price,
               si.total_price,
               s.shipping_cost,
               s.other_costs,
               s.discount,
               s.payment_type,
               c.name as customer_name
               FROM sales s 
               LEFT JOIN sale_items si ON s.id = si.sale_id
               LEFT JOIN products p ON si.product_id = p.id
               LEFT JOIN customers c ON s.customer_id = c.id
               ORDER BY s.date DESC";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute();
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to calculate the total for a sale
function calculateSaleTotal($saleId, $conn) {
    $query = "SELECT SUM(total_price) as total FROM sale_items WHERE sale_id = :sale_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sale_id', $saleId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

// Format date for display
function formatDate($date) {
    return date('Y-m-d H:i', strtotime($date));
}

// Function to translate unit type to Kurdish
function translateUnitType($unitType) {
    switch ($unitType) {
        case 'piece':
            return 'دانە';
        case 'box':
            return 'کارتۆن';
        case 'set':
            return 'سێت';
        default:
            return '-';
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیستی پسووڵەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/reports.css">

    <link rel="stylesheet" href="../../test/main.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Custom styles for this page -->
    <style>
        /* Transparent search input */
        .table-search-input {
            background-color: transparent !important;
            border: 1px solid #dee2e6;
        }

        .custom-table td,
        th {
            white-space: normal;
            word-wrap: break-word;
            vertical-align: middle;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
        }

        .custom-table td {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .custom-table th {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        /* Adjust pagination display */
        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .pagination-numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .pagination-numbers .btn {
            min-width: 35px;
            height: 35px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            border-radius: 50%;
            margin: 0 2px;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }

        .action-buttons .btn i {
            font-size: 0.875rem;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: #6c757d;
        }

        /* Custom Tabs Styling */
        .custom-tabs {
            border-bottom: 1px solid #dee2e6;
            background-color: #fff;
            padding: 0 1rem;
        }

        .custom-tabs .nav-item {
            margin-bottom: -1px;
        }

        .custom-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            background-color: transparent;
            transition: all 0.2s ease;
            position: relative;
            margin-right: 5px;
            border-radius: 0;
            padding: 0.75rem 1.25rem;
        }

        .custom-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
            color: #495057;
        }

        .custom-tabs .nav-link.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }

        .custom-tabs .nav-item .nav-link i {
            margin-left: 0.5rem;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }

        .table-responsive {
            overflow-x: auto;
            max-height: 68vh;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        /* Main content margin fix */
        .main-content {
            margin-top: 60px !important;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .custom-table td,
            .custom-table th {
                min-width: 120px;
            }
        }
    </style>
</head>

<body>
    <div id="main-container">
<div id="navbar-container"></div>

<!-- Sidebar container - populated by JavaScript -->
<div id="sidebar-container"></div>

    <div class="container-fluid mt-5">
        <div class="main-content">
            <h2 class="text-center mb-4"><i class="fas fa-receipt"></i> لیستی پسووڵەکان</h2>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs custom-tabs mb-4" id="receiptTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab" aria-controls="sales" aria-selected="true">
                        <i class="fas fa-shopping-cart"></i> پسووڵەکانی فرۆشتن
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab" aria-controls="purchases" aria-selected="false">
                        <i class="fas fa-truck"></i> پسووڵەکانی کڕین
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns" type="button" role="tab" aria-controls="returns" aria-selected="false">
                        <i class="fas fa-undo"></i> بەفیڕۆچوو
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="receiptTabsContent">
                <!-- Sales Tab -->
                <div class="tab-pane fade show active" id="sales" role="tabpanel" aria-labelledby="sales-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> پسووڵەکانی فرۆشتن</h5>
                                </div>
                                
                            </div>
                            <div class="table-controls mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                            <div class="records-per-page">
                                                <label class="me-2">نیشاندان:</label>
                                                <div class="custom-select-wrapper">
                                                    <select id="salesRecordsPerPage"
                                                        class="form-select form-select-sm rounded-pill">
                                                        <option value="5">5</option>
                                                        <option value="10" selected>10</option>
                                                        <option value="25">25</option>
                                                        <option value="50">50</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-sm-6">
                                            <div class="search-container">
                                                <div class="input-group">
                                                    <input type="text" id="salesTableSearch"
                                                        class="form-control rounded-pill-start table-search-input"
                                                        placeholder="گەڕان لە تەیبڵدا...">
                                                    <span class="input-group-text rounded-pill-end bg-light">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="salesHistoryTable" class="table table-bordered custom-table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>ژمارەی پسووڵە</th>
                                            <th>بەروار</th>
                                            <th>ناوی کڕیار</th>
                                            <th>ناوی کاڵا</th>
                                            <th>کۆدی کاڵا</th>
                                            <th>بڕ</th>
                                            <th>یەکە</th>
                                            <th>نرخی تاک</th>
                                            <th>نرخی گشتی</th>
                                            <th>کرێی گواستنەوە</th>
                                            <th>خەرجی تر</th>
                                            <th>داشکاندن</th>
                                            <th>جۆری پارەدان</th>
                                            <th>کردارەکان</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($sales)): ?>
                                        <tr>
                                            <td colspan="15" class="text-center py-4">هیچ پسووڵەیەک نەدۆزرایەوە</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($sales as $index => $sale): ?>
                                                <?php 
                                                    $total = $sale['total_price'] ?? 0;
                                                    $paymentStatus = 'unpaid';
                                                    if ($sale['payment_type'] == 'cash' || $sale['paid_amount'] >= $total) {
                                                        $paymentStatus = 'paid';
                                                    } elseif ($sale['paid_amount'] > 0) {
                                                        $paymentStatus = 'partial';
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($sale['invoice_number']) ?></td>
                                                    <td><?= formatDate($sale['date']) ?></td>
                                                    <td><?= htmlspecialchars($sale['customer_name'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($sale['product_name'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($sale['product_code'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($sale['quantity'] ?? '-') ?></td>
                                                    <td><?= translateUnitType($sale['unit_type']) ?></td>
                                                    <td><?= number_format($sale['unit_price'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($sale['total_price'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($sale['shipping_cost'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($sale['other_costs'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($sale['discount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td>
                                                        <?php if($sale['payment_type'] == 'cash'): ?>
                                                            <span class="badge bg-success">نەقد</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">قەرز</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                    <div class="action-buttons">
                                                                <a href="<?php echo (isset($sale['is_delivery']) && $sale['is_delivery'] == 1) ? 
                                                                    '../../Views/receipt/delivery_receipt.php?sale_id=' . $sale['id'] : 
                                                                    '../../Views/receipt/print_receipt.php?sale_id=' . $sale['id']; ?>"
                                                                    class="btn btn-sm btn-outline-success rounded-circle"
                                                                    title="چاپکردن">
                                                                    <i class="fas fa-print"></i>
                                                                </a>
                                                           
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-info rounded-circle show-invoice-items"
                                                                    data-invoice="<?php echo $sale['invoice_number']; ?>"
                                                                    title="بینینی هەموو کاڵاکان">
                                                                    <i class="fas fa-list"></i>
                                                                </button>
                                                                <!-- Add Return Button -->
                                                                
                                                                
                                                            </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="pagination-wrapper">
                                <div class="pagination-info">
                                    <select id="salesRecordsPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="5">5 ڕیکۆرد</option>
                                        <option value="10" selected>10 ڕیکۆرد</option>
                                        <option value="25">25 ڕیکۆرد</option>
                                        <option value="50">50 ڕیکۆرد</option>
                                        <option value="100">100 ڕیکۆرد</option>
                                    </select>
                                </div>
                                <div class="pagination-controls">
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle" id="salesPrevPage" disabled>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                    <div class="pagination-numbers" id="salesPagination"></div>
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle" id="salesNextPage">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchases Tab (Placeholder for future implementation) -->
                <div class="tab-pane fade" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <h4 class="text-muted"><i class="fas fa-truck mb-3 fa-3x"></i></h4>
                            <h5>پسووڵەکانی کڕین</h5>
                            <p class="text-muted">ئەم بەشە بەمزوانە چالاک دەکرێت</p>
                        </div>
                    </div>
                </div>

                <!-- Returns Tab (Placeholder for future implementation) -->
                <div class="tab-pane fade" id="returns" role="tabpanel" aria-labelledby="returns-tab">
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <h4 class="text-muted"><i class="fas fa-undo mb-3 fa-3x"></i></h4>
                            <h5>پسووڵەکانی گەڕانەوە</h5>
                            <p class="text-muted">ئەم بەشە بەمزوانە چالاک دەکرێت</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Page Script -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/ajax-config.js"></script>
    <script>
    $(document).ready(function() {
        // Table pagination and filtering
        const salesTable = $('#salesHistoryTable');
        const salesTableBody = salesTable.find('tbody');
        const salesRows = salesTableBody.find('tr');
        let salesItemsPerPage = parseInt($('#salesRecordsPerPage').val());
        let salesCurrentPage = 1;
        let salesTotalItems = salesRows.length;
        let salesTotalPages = Math.ceil(salesTotalItems / salesItemsPerPage);

        // Initial pagination setup
        updateSalesPagination();
        showSalesPage(1);

        // Handle records per page change
        $('#salesRecordsPerPage').on('change', function() {
            salesItemsPerPage = parseInt($(this).val());
            salesCurrentPage = 1; // Reset to first page
            salesTotalPages = Math.ceil(salesTotalItems / salesItemsPerPage);
            showSalesPage(1);
            updateSalesPagination();
        });

        // Update pagination info and buttons
        function updateSalesPagination() {
            // Clear pagination
            const pagination = $('#salesPagination');
            pagination.empty();

            // Add page numbers
            const maxPagesToShow = 5;
            let startPage = Math.max(1, salesCurrentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(salesTotalPages, startPage + maxPagesToShow - 1);

            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageButton = $('<button class="btn btn-sm ' + (i === salesCurrentPage ? 'btn-primary' : 'btn-outline-secondary') + ' rounded-circle">' + i + '</button>');
                pageButton.on('click', function () {
                    salesCurrentPage = i;
                    showSalesPage(i);
                    updateSalesPagination();
                });
                pagination.append(pageButton);
            }

            // Update prev/next buttons
            $('#salesPrevPage').prop('disabled', salesCurrentPage === 1);
            $('#salesNextPage').prop('disabled', salesCurrentPage === salesTotalPages || salesTotalPages === 0);
        }

        // Show specific page
        function showSalesPage(page) {
            salesRows.hide();
            salesRows.slice((page - 1) * salesItemsPerPage, page * salesItemsPerPage).show();
        }

        // Previous page button
        $('#salesPrevPage').on('click', function () {
            if (salesCurrentPage > 1) {
                salesCurrentPage--;
                showSalesPage(salesCurrentPage);
                updateSalesPagination();
            }
        });

        // Next page button
        $('#salesNextPage').on('click', function () {
            if (salesCurrentPage < salesTotalPages) {
                salesCurrentPage++;
                showSalesPage(salesCurrentPage);
                updateSalesPagination();
            }
        });

        // Search functionality
        $('#salesSearchInput').on('keyup', function () {
            const searchTerm = $(this).val().toLowerCase();
            let matchCount = 0;

            salesRows.each(function () {
                const rowText = $(this).text().toLowerCase();
                const showRow = rowText.indexOf(searchTerm) > -1;
                $(this).toggle(showRow);
                if (showRow) {
                    matchCount++;
                }
            });

            // Update pagination after search
            salesTotalItems = matchCount;
            salesTotalPages = Math.ceil(salesTotalItems / salesItemsPerPage);
            
            // Reset to first page on search
            salesCurrentPage = 1;
            showSalesPage(1);
            updateSalesPagination();
        });
        
        // Show invoice items when clicking the info button
        $('.show-invoice-items').on('click', function () {
            const invoiceNumber = $(this).data('invoice');
            
            $.ajax({
                url: '../../includes/get_invoice_items.php',
                type: 'POST',
                data: { invoice_number: invoiceNumber },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        // Create table with items
                        let itemsHtml = `
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>ناوی کاڵا</th>
                                            <th>بڕ</th>
                                            <th>یەکە</th>
                                            <th>نرخی تاک</th>
                                            <th>کۆی گشتی</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                        
                        if (response.items.length === 0) {
                            itemsHtml += `<tr><td colspan="6" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>`;
                        } else {
                            response.items.forEach((item, index) => {
                                let unitName = '-';
                                switch (item.unit_type) {
                                    case 'piece': unitName = 'دانە'; break;
                                    case 'box': unitName = 'کارتۆن'; break;
                                    case 'set': unitName = 'سێت'; break;
                                    default: unitName = item.unit_type || '-';
                                }
                                
                                itemsHtml += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.product_name}</td>
                                        <td>${item.quantity}</td>
                                        <td>${unitName}</td>
                                        <td>${Number(item.unit_price).toLocaleString()} د.ع</td>
                                        <td>${Number(item.total_price).toLocaleString()} د.ع</td>
                                    </tr>`;
                            });
                        }
                        
                        itemsHtml += `</tbody></table></div>`;
                        
                        // Show modal with items
                        Swal.fire({
                            title: `ناوەرۆکی پسووڵەی <strong dir="ltr">#${invoiceNumber}</strong>`,
                            html: itemsHtml,
                            width: '80%',
                            confirmButtonText: 'داخستن'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە ڕوویدا!',
                            text: response.message || 'نەتوانرا زانیاریەکان بهێنرێت، تکایە دووبارە هەوڵبدەوە.'
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە ڕوویدا!',
                        text: 'کێشەیەک لە پەیوەندی کردن بە سێرڤەرەوە ڕوویدا، تکایە دواتر هەوڵبدەوە.'
                    });
                }
            });
        });
    });
    </script>
    <script src="../../js/receiptList.js"></script>
    <script src="../../js/debtTransactions.js"></script>
</body>

</html> 