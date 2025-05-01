<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all purchases with details
$purchasesQuery = "SELECT p.*, 
               prod.name as product_name,
               prod.code as product_code,
               pi.quantity,
               pi.unit_type,
               pi.unit_price,
               pi.total_price,
               p.shipping_cost,
               p.other_cost,
               p.discount,
               p.payment_type,
               s.name as supplier_name
               FROM purchases p 
               LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
               LEFT JOIN products prod ON pi.product_id = prod.id
               LEFT JOIN suppliers s ON p.supplier_id = s.id
               ORDER BY p.date DESC";
$purchasesStmt = $conn->prepare($purchasesQuery);
$purchasesStmt->execute();
$purchases = $purchasesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get suppliers for filter dropdown
$suppliersQuery = "SELECT id, name FROM suppliers ORDER BY name ASC";
$suppliersStmt = $conn->prepare($suppliersQuery);
$suppliersStmt->execute();
$suppliers = $suppliersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all purchase items for modal
$purchaseItemsQuery = "SELECT p.*, 
                      prod.name as product_name,
                      prod.code as product_code,
                      pi.quantity,
                      pi.unit_type,
                      pi.unit_price,
                      pi.total_price
                      FROM purchases p 
                      LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
                      LEFT JOIN products prod ON pi.product_id = prod.id";
$purchaseItemsStmt = $conn->prepare($purchaseItemsQuery);
$purchaseItemsStmt->execute();
$purchaseItems = $purchaseItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to calculate the total for a purchase
function calculatePurchaseTotal($purchaseId, $conn) {
    $query = "SELECT SUM(total_price) as total FROM purchase_items WHERE purchase_id = :purchase_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':purchase_id', $purchaseId);
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
    <title>لیستی کڕینەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/products.css">

    <link rel="stylesheet" href="../../test/main.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Custom styles for this page -->
    <style>
        .badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            letter-spacing: 0.3px;
            font-weight: 500;
        }
        
        .badge.rounded-pill {
            border-radius: 50rem;
            padding-right: 12px;
            padding-left: 12px;
        }
        
        .badge.bg-danger {
            background-color: rgba(220, 53, 69, 0.2) !important;
            color: #dc3545 !important;
            border: 1px solid rgba(220, 53, 69, 0.4);
        }
        
        .badge.bg-warning {
            background-color: rgba(255, 193, 7, 0.2) !important;
            color: #e0a800 !important;
            border: 1px solid rgba(255, 193, 7, 0.4);
        }
        
        .badge.bg-success {
            background-color: rgba(25, 135, 84, 0.2) !important;
            color: #198754 !important;
            border: 1px solid rgba(25, 135, 84, 0.4);
        }
        
        /* RTL specific fixes */
        .sidebar-menu .menu-item a i {
            margin-left: 8px;
            margin-right: 0;
        }
        
        .sidebar-menu .menu-item a {
            display: flex;
            align-items: center;
        }
        
        /* Fix for cart icon in sidebar */
        .sidebar .menu-item a span {
            display: inline-block;
        }
        
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
            padding: 0.85rem;
            border: 1px solid #eaeaea;
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
            background-color: #cde1ff;
            border: none !important;
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
            border-color: transparent;
            color: #0d6efd;
        }

        .custom-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            border-bottom: 3px solid #0d6efd;
            font-weight: 600;
        }

        .custom-tabs .nav-item .nav-link i {
            margin-left: 0.5rem;
        }
        
        /* Fix for buttons and icon display in RTL mode */
        button, .btn {
            direction: rtl !important;
            text-align: right !important;
        }
        
        /* Fix for RTL alignment in sidebar */
        .sidebar .menu-item .item-link {
            display: flex !important;
            align-items: center !important;
            text-align: right !important;
        }
        
        .sidebar .menu-item .item-link span {
            order: 2;
        }
        
        .sidebar .menu-item .item-link .icon-cont {
            order: 1;
            margin-left: 10px;
        }
        
        .sidebar .menu-item .item-link .dropdown-icon {
            order: 3;
            margin-right: auto;
        }
        
        /* Fix for cart icon specifically */
        #psoola-btn {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
        }
        
        #psoola-btn i {
            margin-left: 8px !important;
            margin-right: 0 !important;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }

        .table-responsive {
            overflow-x: auto;
            max-height: 68vh;
            border-radius: 0.25rem;
        }

        /* Filter section styles */
        .filter-section {
            background-color: #f8f9ff;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 24px; 
        }

        /* Custom table styles */
        .custom-table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 16px;
            margin: 0;
            border: none;
        }
        
        .custom-table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        .custom-table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Card styling */
        .card {
            border-radius: 24px !important; 
            border: 1px solid #9ec5ff !important;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: transparent !important;
            border-bottom: 1px solid #e3f2fd !important;
            padding: 1.25rem 1.5rem !important;
        }

        .card-footer {
            background-color: transparent !important;
            border-top: 1px solid #e3f2fd !important;
            padding: 1rem 1.5rem !important;
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

        /* Fix margin for main content */
        #main-content-area {
            margin-top: 100px !important;
            padding-top: 20px !important;
        }
        
        /* Make sure the page title is visible */
        .page-title {
            margin-top: 10px !important;
            margin-bottom: 20px !important;
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div id="main-container">
        <div id="navbar-container"></div>

        <!-- Sidebar container - populated by JavaScript -->
        <div id="sidebar-container"></div>

        <div class="main-content p-3" id="main-content-area">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="page-title">لیستی کڕینەکان</h3>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4 filter-section">
                    <div class="card-body p-3">
                        <form id="purchaseFiltersForm">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="supplierFilter" class="form-label">ناوی دابینکەر</label>
                                    <select id="supplierFilter" class="form-select supplier-select">
                                        <option value="">هەموو دابینکەرەکان</option>
                                        <?php foreach($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="paymentTypeFilter" class="form-label">جۆری پارەدان</label>
                                    <select id="paymentTypeFilter" class="form-select">
                                        <option value="">هەموو جۆرەکان</option>
                                        <option value="cash">نەقد</option>
                                        <option value="credit">قەرز</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-redo-alt me-1"></i> ڕیسێت
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Purchases Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="table-controls mt-3">
                            <div class="row align-items-center">
                                <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                    <div class="records-per-page d-flex align-items-center">
                                        <label class="me-2 mb-0">نیشاندان:</label>
                                        <select id="purchasesRecordsPerPage" class="form-select form-select-sm rounded-pill" style="width: auto;">
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-8 col-sm-6">
                                    <div class="search-container">
                                        <div class="input-group">
                                            <input type="text" id="purchasesSearchInput" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                            <span class="input-group-text rounded-pill-end bg-light">
                                                <i class="fas fa-search"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive p-2" style="background-color: #fff; border-radius: 16px;">
                            <table id="purchasesTable" class="table table-bordered custom-table table-hover" style="border-radius: 16px;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="background-color: #cde1ff; border: none;">#</th>
                                        <th style="background-color: #cde1ff; border: none;">ژمارەی پسووڵە</th>
                                        <th style="background-color: #cde1ff; border: none;">بەروار</th>
                                        <th style="background-color: #cde1ff; border: none;">ناوی دابینکەر</th>
                                        <th style="background-color: #cde1ff; border: none;">ناوی کاڵا</th>
                                        <th style="background-color: #cde1ff; border: none;">کۆدی کاڵا</th>
                                        <th style="background-color: #cde1ff; border: none;">بڕ</th>
                                        <th style="background-color: #cde1ff; border: none;">یەکە</th>
                                        <th style="background-color: #cde1ff; border: none;">نرخی تاک</th>
                                        <th style="background-color: #cde1ff; border: none;">نرخی گشتی</th>
                                        <th style="background-color: #cde1ff; border: none;">کرێی گواستنەوە</th>
                                        <th style="background-color: #cde1ff; border: none;">خەرجی تر</th>
                                        <th style="background-color: #cde1ff; border: none;">داشکاندن</th>
                                        <th style="background-color: #cde1ff; border: none;">جۆری پارەدان</th>
                                        <th style="background-color: #cde1ff; border: none;">کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($purchases)): ?>
                                    <tr>
                                        <td colspan="15" class="text-center py-4">هیچ تۆماری کڕینێک نەدۆزرایەوە</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($purchases as $index => $purchase): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($purchase['invoice_number']) ?></td>
                                                <td><?= formatDate($purchase['date']) ?></td>
                                                <td><?= htmlspecialchars($purchase['supplier_name'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($purchase['product_name'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($purchase['product_code'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($purchase['quantity'] ?? '-') ?></td>
                                                <td><?= translateUnitType($purchase['unit_type']) ?></td>
                                                <td><?= number_format($purchase['unit_price'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                <td><?= number_format($purchase['total_price'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                <td><?= number_format($purchase['shipping_cost'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                <td><?= number_format($purchase['other_cost'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                <td><?= number_format($purchase['discount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                <td>
                                                    <?php if($purchase['payment_type'] == 'cash'): ?>
                                                        <span class="badge bg-success">نەقد</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">قەرز</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="../../Views/receipt/print_purchase.php?purchase_id=<?= $purchase['id'] ?>" 
                                                            class="btn btn-sm btn-outline-success rounded-circle"
                                                            title="چاپکردن">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                        <button type="button" 
                                                            class="btn btn-sm btn-outline-info rounded-circle show-purchase-items"
                                                            data-invoice="<?php echo $purchase['invoice_number']; ?>"
                                                            title="بینینی هەموو کاڵاکان">
                                                            <i class="fas fa-list"></i>
                                                        </button>
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
                            <div class="pagination-controls">
                                <button class="btn btn-sm btn-outline-secondary rounded-circle" id="purchasesPrevPage" disabled>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <div class="pagination-numbers" id="purchasesPagination"></div>
                                <button class="btn btn-sm btn-outline-secondary rounded-circle" id="purchasesNextPage">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
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
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Page Script -->
    <script src="../../js/include-components.js"></script>
    <script src="../../js/ajax-config.js"></script>
    <script src="../../js/purchase-search.js"></script>
    <script src="../../js/purchase-filters.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize the table with pagination
        initializeTable('purchases');

        function initializeTable(tableId) {
            const table = $(`#${tableId}Table`);
            const tableBody = table.find('tbody');
            const rows = tableBody.find('tr');
            let itemsPerPage = parseInt($(`#${tableId}RecordsPerPage`).val());
            let currentPage = 1;
            let totalItems = rows.length;
            let totalPages = Math.ceil(totalItems / itemsPerPage);

            // Initial pagination setup
            updatePagination(tableId);
            showPage(tableId, 1);

            // Handle records per page change
            $(`#${tableId}RecordsPerPage`).on('change', function() {
                itemsPerPage = parseInt($(this).val());
                currentPage = 1;
                
                // Count visible rows after filtering
                const visibleRows = $(`#${tableId}Table tbody tr:visible`);
                totalItems = visibleRows.length;
                totalPages = Math.ceil(totalItems / itemsPerPage);
                
                updatePagination(tableId);
                showPage(tableId, 1);
            });

            // Show specific page
            function showPage(tableId, page) {
                // Only operate on visible rows (after filtering)
                const visibleRows = $(`#${tableId}Table tbody tr:visible`);
                
                // Calculate start and end indexes
                const startIndex = (page - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                
                // Hide all rows first
                visibleRows.hide();
                
                // Show only rows for current page
                visibleRows.slice(startIndex, endIndex).show();
            }

            // Update pagination info and buttons
            function updatePagination(tableId) {
                const pagination = $(`#${tableId}Pagination`);
                pagination.empty();

                // Don't show pagination if there are no pages
                if (totalPages === 0) {
                    $(`#${tableId}PrevPage`).prop('disabled', true);
                    $(`#${tableId}NextPage`).prop('disabled', true);
                    return;
                }
                
                // Limit number of page buttons to display
                const maxPagesToShow = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
                let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

                if (endPage - startPage + 1 < maxPagesToShow && startPage > 1) {
                    startPage = Math.max(1, endPage - maxPagesToShow + 1);
                }

                // Add first page button if not visible
                if (startPage > 1) {
                    const firstPageBtn = $('<button class="btn btn-sm btn-outline-secondary rounded-circle">١</button>');
                    firstPageBtn.on('click', function() {
                        currentPage = 1;
                        showPage(tableId, 1);
                        updatePagination(tableId);
                    });
                    pagination.append(firstPageBtn);
                    
                    // Add ellipsis if there's a gap
                    if (startPage > 2) {
                        pagination.append('<span class="px-1">...</span>');
                    }
                }

                // Add page buttons
                for (let i = startPage; i <= endPage; i++) {
                    // Convert to Kurdish numerals
                    const kurdishNum = convertToKurdishNumerals(i);
                    const pageButton = $('<button class="btn btn-sm ' + (i === currentPage ? 'btn-primary' : 'btn-outline-secondary') + ' rounded-circle">' + kurdishNum + '</button>');
                    pageButton.on('click', function() {
                        currentPage = i;
                        showPage(tableId, i);
                        updatePagination(tableId);
                    });
                    pagination.append(pageButton);
                }

                // Add last page button if not visible
                if (endPage < totalPages) {
                    // Add ellipsis if there's a gap
                    if (endPage < totalPages - 1) {
                        pagination.append('<span class="px-1">...</span>');
                    }
                    
                    const lastPageBtn = $('<button class="btn btn-sm btn-outline-secondary rounded-circle">' + convertToKurdishNumerals(totalPages) + '</button>');
                    lastPageBtn.on('click', function() {
                        currentPage = totalPages;
                        showPage(tableId, totalPages);
                        updatePagination(tableId);
                    });
                    pagination.append(lastPageBtn);
                }

                $(`#${tableId}PrevPage`).prop('disabled', currentPage === 1);
                $(`#${tableId}NextPage`).prop('disabled', currentPage === totalPages);
            }

            // Convert numbers to Kurdish numerals
            function convertToKurdishNumerals(num) {
                const kurdishDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
                return num.toString().split('').map(digit => 
                    isNaN(parseInt(digit)) ? digit : kurdishDigits[parseInt(digit)]
                ).join('');
            }

            // Previous page button
            $(`#${tableId}PrevPage`).on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    showPage(tableId, currentPage);
                    updatePagination(tableId);
                }
            });

            // Next page button
            $(`#${tableId}NextPage`).on('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    showPage(tableId, currentPage);
                    updatePagination(tableId);
                }
            });
        }

        // Show purchase items in modal
        $(document).on('click', '.show-purchase-items', function() {
            const invoiceNumber = $(this).data('invoice');
            
            // Get all purchase items with this invoice number
            const purchaseItems = <?php echo json_encode($purchaseItems); ?>;
            const items = purchaseItems.filter(item => item.invoice_number === invoiceNumber);
            
            let itemsHtml = '<div class="table-responsive"><table class="table table-bordered">';
            itemsHtml += '<thead><tr><th>ناوی کاڵا</th><th>کۆدی کاڵا</th><th>بڕ</th><th>یەکە</th><th>نرخی تاک</th><th>نرخی گشتی</th></tr></thead>';
            itemsHtml += '<tbody>';
            
            if (items.length === 0) {
                itemsHtml += '<tr><td colspan="6" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>';
            } else {
                items.forEach(item => {
                    itemsHtml += `<tr>
                        <td>${item.product_name || '-'}</td>
                        <td>${item.product_code || '-'}</td>
                        <td>${item.quantity || '-'}</td>
                        <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                        <td>${parseInt(item.unit_price || 0).toLocaleString()} دینار</td>
                        <td>${parseInt(item.total_price || 0).toLocaleString()} دینار</td>
                    </tr>`;
                });
            }
            
            itemsHtml += '</tbody></table></div>';
            
            Swal.fire({
                title: `کاڵاکانی پسووڵە ${invoiceNumber || ''}`,
                html: itemsHtml,
                width: '80%',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal2-popup-custom'
                }
            });
        });
    });
    </script>
</body>

</html> 