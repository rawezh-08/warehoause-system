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
               WHERE s.is_draft = 0 AND s.is_delivery = 0
               ORDER BY s.date DESC";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute();
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get delivery receipts
$deliveryQuery = "SELECT s.*, 
                  c.name as customer_name,
                  (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as total_amount
                  FROM sales s 
                  LEFT JOIN customers c ON s.customer_id = c.id
                  WHERE s.is_delivery = 1
                  GROUP BY s.id
                  ORDER BY s.date DESC";
$deliveryStmt = $conn->prepare($deliveryQuery);
$deliveryStmt->execute();
$deliveries = $deliveryStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all delivery items for modal
$deliveryItemsQuery = "SELECT s.*, 
                       p.name as product_name,
                       p.code as product_code,
                       si.quantity,
                       si.unit_type,
                       si.unit_price,
                       si.total_price
                       FROM sales s 
                       LEFT JOIN sale_items si ON s.id = si.sale_id
                       LEFT JOIN products p ON si.product_id = p.id
                       WHERE s.is_delivery = 1";
$deliveryItemsStmt = $conn->prepare($deliveryItemsQuery);
$deliveryItemsStmt->execute();
$deliveryItems = $deliveryItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get draft receipts
$draftQuery = "SELECT s.*, 
               c.name as customer_name,
               (SELECT SUM(total_price) FROM sale_items WHERE sale_id = s.id) as total_amount
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id
               WHERE s.is_draft = 1
               GROUP BY s.id
               ORDER BY s.date DESC";
$draftStmt = $conn->prepare($draftQuery);
$draftStmt->execute();
$drafts = $draftStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all draft items for modal
$draftItemsQuery = "SELECT s.*, 
                    p.name as product_name,
                    p.code as product_code,
                    si.quantity,
                    si.unit_type,
                    si.unit_price,
                    si.total_price
                    FROM sales s 
                    LEFT JOIN sale_items si ON s.id = si.sale_id
                    LEFT JOIN products p ON si.product_id = p.id
                    WHERE s.is_draft = 1";
$draftItemsStmt = $conn->prepare($draftItemsQuery);
$draftItemsStmt->execute();
$draftItems = $draftItemsStmt->fetchAll(PDO::FETCH_ASSOC);

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

// Get all returns
$returnsQuery = "SELECT r.*, 
                    GROUP_CONCAT(
                        CONCAT(p.name, ' (', ri.quantity, ' ', 
                        CASE ri.unit_type 
                            WHEN 'piece' THEN 'دانە'
                            WHEN 'box' THEN 'کارتۆن'
                            WHEN 'set' THEN 'سێت'
                        END, ')')
                    SEPARATOR ', ') as products_list,
                    SUM(ri.total_price) as total_amount
                FROM product_returns r
                LEFT JOIN return_items ri ON r.id = ri.return_id
                LEFT JOIN products p ON ri.product_id = p.id
                GROUP BY r.id
                ORDER BY r.return_date DESC";

// Get all wastings
$wastingsQuery = "SELECT w.*, 
                    GROUP_CONCAT(
                        CONCAT(p.name, ' (', wi.quantity, ' ', 
                        CASE wi.unit_type 
                            WHEN 'piece' THEN 'دانە'
                            WHEN 'box' THEN 'کارتۆن'
                            WHEN 'set' THEN 'سێت'
                        END, ')')
                    SEPARATOR ', ') as products_list,
                    SUM(wi.total_price) as total_amount
                FROM wastings w
                LEFT JOIN wasting_items wi ON w.id = wi.wasting_id
                LEFT JOIN products p ON wi.product_id = p.id
                GROUP BY w.id
                ORDER BY w.date DESC";

$returnsResult = $conn->query($returnsQuery);
$wastingsResult = $conn->query($wastingsQuery);
$returns = $returnsResult->fetchAll(PDO::FETCH_ASSOC);
$wastings = $wastingsResult->fetchAll(PDO::FETCH_ASSOC);

// Get all return items for modal
$returnItemsQuery = "SELECT ri.*, p.name as product_name, p.code as product_code
                    FROM return_items ri
                    LEFT JOIN products p ON ri.product_id = p.id";
$returnItemsResult = $conn->query($returnItemsQuery);
$returnItems = $returnItemsResult->fetchAll(PDO::FETCH_ASSOC);

// Get all wasting items for modal
$wastingItemsQuery = "SELECT wi.*, p.name as product_name, p.code as product_code
                    FROM wasting_items wi
                    LEFT JOIN products p ON wi.product_id = p.id";
$wastingItemsResult = $conn->query($wastingItemsQuery);
$wastingItems = $wastingItemsResult->fetchAll(PDO::FETCH_ASSOC);
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
                    <button class="nav-link" id="delivery-tab" data-bs-toggle="tab" data-bs-target="#delivery" type="button" role="tab" aria-controls="delivery" aria-selected="false">
                        <i class="fas fa-truck"></i> پسووڵەکانی گەیاندن
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts" type="button" role="tab" aria-controls="drafts" aria-selected="false">
                        <i class="fas fa-file-alt"></i> پسووڵە ڕەش نووسەکان
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
                            <div class="table-controls mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                        <div class="records-per-page d-flex align-items-center">
                                            <label class="me-2 mb-0">نیشاندان:</label>
                                            <select id="salesRecordsPerPage" class="form-select form-select-sm rounded-pill" style="width: auto;">
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
                                                <input type="text" id="salesSearchInput" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                                                    class="btn btn-sm btn-outline-info rounded-circle show-receipt-items"
                                                                    data-invoice="<?php echo $sale['invoice_number']; ?>"
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

                <!-- Delivery Tab -->
                <div class="tab-pane fade" id="delivery" role="tabpanel" aria-labelledby="delivery-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <h5 class="mb-0"><i class="fas fa-truck"></i> پسووڵەکانی گەیاندن</h5>
                                </div>
                            </div>
                            <div class="table-controls mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                        <div class="records-per-page d-flex align-items-center">
                                            <label class="me-2 mb-0">نیشاندان:</label>
                                            <select id="deliveryRecordsPerPage" class="form-select form-select-sm rounded-pill" style="width: auto;">
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
                                                <input type="text" id="deliverySearchInput" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                <table id="deliveryTable" class="table table-bordered custom-table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>ژمارەی پسووڵە</th>
                                            <th>بەروار</th>
                                            <th>ناوی کڕیار</th>
                                            <th>نرخی گشتی</th>
                                            <th>کرێی گواستنەوە</th>
                                            <th>خەرجی تر</th>
                                            <th>داشکاندن</th>
                                            <th>جۆری پارەدان</th>
                                            <th>کردارەکان</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($deliveries)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">هیچ پسووڵەیەکی گەیاندن نەدۆزرایەوە</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($deliveries as $index => $delivery): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($delivery['invoice_number']) ?></td>
                                                    <td><?= formatDate($delivery['date']) ?></td>
                                                    <td><?= htmlspecialchars($delivery['customer_name'] ?? '-') ?></td>
                                                    <td><?= number_format($delivery['total_amount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($delivery['shipping_cost'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($delivery['other_costs'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($delivery['discount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td>
                                                        <?php if($delivery['payment_type'] == 'cash'): ?>
                                                            <span class="badge bg-success">نەقد</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">قەرز</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="../../Views/receipt/delivery_receipt.php?sale_id=<?= $delivery['id'] ?>"
                                                                class="btn btn-sm btn-outline-success rounded-circle"
                                                                title="چاپکردن">
                                                                <i class="fas fa-print"></i>
                                                            </a>
                                                            <button type="button" 
                                                                class="btn btn-sm btn-outline-info rounded-circle show-receipt-items"
                                                                data-invoice="<?php echo $delivery['invoice_number']; ?>"
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
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle" id="deliveryPrevPage" disabled>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                    <div class="pagination-numbers" id="deliveryPagination"></div>
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle" id="deliveryNextPage">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drafts Tab -->
                <div class="tab-pane fade" id="drafts" role="tabpanel" aria-labelledby="drafts-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> پسووڵە ڕەش نووسەکان</h5>
                                </div>
                            </div>
                            <div class="table-controls mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                        <div class="records-per-page d-flex align-items-center">
                                            <label class="me-2 mb-0">نیشاندان:</label>
                                            <select id="draftsRecordsPerPage" class="form-select form-select-sm rounded-pill" style="width: auto;">
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
                                                <input type="text" id="draftsSearchInput" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
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
                                <table id="draftsTable" class="table table-bordered custom-table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>ژمارەی پسووڵە</th>
                                            <th>بەروار</th>
                                            <th>ناوی کڕیار</th>
                                            <th>نرخی گشتی</th>
                                            <th>کرێی گواستنەوە</th>
                                            <th>خەرجی تر</th>
                                            <th>داشکاندن</th>
                                            <th>جۆری پارەدان</th>
                                            <th>کردارەکان</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($drafts)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">هیچ پسووڵەیەکی ڕەش نووس نەدۆزرایەوە</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($drafts as $index => $draft): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($draft['invoice_number']) ?></td>
                                                    <td><?= formatDate($draft['date']) ?></td>
                                                    <td><?= htmlspecialchars($draft['customer_name'] ?? '-') ?></td>
                                                    <td><?= number_format($draft['total_amount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($draft['shipping_cost'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($draft['other_costs'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td><?= number_format($draft['discount'] ?? 0, 0, '.', ',') ?> د.ع</td>
                                                    <td>
                                                        <?php if($draft['payment_type'] == 'cash'): ?>
                                                            <span class="badge bg-success">نەقد</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">قەرز</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="<?php echo (isset($draft['is_delivery']) && $draft['is_delivery'] == 1) ? 
                                                                '../../Views/receipt/delivery_receipt.php?sale_id=' . $draft['id'] : 
                                                                '../../Views/receipt/print_receipt.php?sale_id=' . $draft['id']; ?>"
                                                                class="btn btn-sm btn-outline-success rounded-circle"
                                                                title="چاپکردن">
                                                                <i class="fas fa-print"></i>
                                                            </a>
                                                            <button type="button" 
                                                                class="btn btn-sm btn-outline-info rounded-circle show-receipt-items"
                                                                data-invoice="<?php echo $draft['invoice_number']; ?>"
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
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle" id="draftsPrevPage" disabled>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                    <div class="pagination-numbers" id="draftsPagination"></div>
                                    <button class="btn btn-sm btn-outline-secondary rounded-circle" id="draftsNextPage">
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

                <!-- Returns Tab -->
                <div class="tab-pane fade" id="returns" role="tabpanel" aria-labelledby="returns-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReturnModal">
                                <i class="fas fa-plus"></i> زیادکردنی گەڕاوە
                            </button>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addWastingModal">
                                <i class="fas fa-trash"></i> زیادکردنی بەفیڕۆچوو
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="returnsSearch" placeholder="گەڕان...">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" id="returnsStartDate">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" id="returnsEndDate">
                            </div>
                        </div>
                    </div>

                    <!-- Returns Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="returnsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>بەروار</th>
                                    <th>کاڵاکان</th>
                                    <th>بڕی گشتی</th>
                                    <th>تێبینی</th>
                                    <th>کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($returns as $return): ?>
                                <tr>
                                    <td><?php echo $return['id']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($return['return_date'])); ?></td>
                                    <td><?php echo $return['products_list']; ?></td>
                                    <td><?php echo number_format($return['total_amount'], 2); ?></td>
                                    <td><?php echo $return['notes']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm view-items" 
                                                data-id="<?php echo $return['id']; ?>"
                                                data-type="return">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-return" 
                                                data-id="<?php echo $return['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Wastings Table -->
                    <div class="table-responsive mt-4">
                        <h4>بەفیڕۆچووەکان</h4>
                        <table class="table table-striped table-hover" id="wastingsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>بەروار</th>
                                    <th>کاڵاکان</th>
                                    <th>بڕی گشتی</th>
                                    <th>تێبینی</th>
                                    <th>کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wastings as $wasting): ?>
                                <tr>
                                    <td><?php echo $wasting['id']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($wasting['date'])); ?></td>
                                    <td><?php echo $wasting['products_list']; ?></td>
                                    <td><?php echo number_format($wasting['total_amount'], 2); ?></td>
                                    <td><?php echo $wasting['notes']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm view-items" 
                                                data-id="<?php echo $wasting['id']; ?>"
                                                data-type="wasting">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-wasting" 
                                                data-id="<?php echo $wasting['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
        // Initialize all tables
        initializeTable('sales');
        initializeTable('drafts');
        initializeTable('delivery');

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
                totalPages = Math.ceil(totalItems / itemsPerPage);
                showPage(tableId, 1);
                updatePagination(tableId);
            });

            // Show specific page
            function showPage(tableId, page) {
                const rows = $(`#${tableId}Table tbody tr`);
                rows.hide();
                rows.slice((page - 1) * itemsPerPage, page * itemsPerPage).show();
            }

        // Update pagination info and buttons
            function updatePagination(tableId) {
                const pagination = $(`#${tableId}Pagination`);
            pagination.empty();

            const maxPagesToShow = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
                let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                    const pageButton = $('<button class="btn btn-sm ' + (i === currentPage ? 'btn-primary' : 'btn-outline-secondary') + ' rounded-circle">' + i + '</button>');
                pageButton.on('click', function () {
                        currentPage = i;
                        showPage(tableId, i);
                        updatePagination(tableId);
                });
                pagination.append(pageButton);
            }

                $(`#${tableId}PrevPage`).prop('disabled', currentPage === 1);
                $(`#${tableId}NextPage`).prop('disabled', currentPage === totalPages || totalPages === 0);
        }

        // Previous page button
            $(`#${tableId}PrevPage`).on('click', function () {
                if (currentPage > 1) {
                    currentPage--;
                    showPage(tableId, currentPage);
                    updatePagination(tableId);
            }
        });

        // Next page button
            $(`#${tableId}NextPage`).on('click', function () {
                if (currentPage < totalPages) {
                    currentPage++;
                    showPage(tableId, currentPage);
                    updatePagination(tableId);
            }
        });

        // Search functionality
            $(`#${tableId}SearchInput`).on('keyup', function () {
            const searchTerm = $(this).val().toLowerCase();
            let matchCount = 0;

                rows.each(function () {
                const rowText = $(this).text().toLowerCase();
                const showRow = rowText.indexOf(searchTerm) > -1;
                $(this).toggle(showRow);
                if (showRow) {
                    matchCount++;
                }
            });

                totalItems = matchCount;
                totalPages = Math.ceil(totalItems / itemsPerPage);
                currentPage = 1;
                showPage(tableId, 1);
                updatePagination(tableId);
            });
        }

        // Show receipt items in modal (for all tables)
        $(document).on('click', '.show-receipt-items', function() {
            const invoiceNumber = $(this).data('invoice');
            const invoiceItems = <?php echo json_encode(array_merge($sales, $draftItems, $deliveryItems)); ?>;
            const items = invoiceItems.filter(item => item.invoice_number === invoiceNumber);
            
            let itemsHtml = '<div class="table-responsive"><table class="table table-bordered">';
            itemsHtml += '<thead><tr><th>ناوی کاڵا</th><th>کۆدی کاڵا</th><th>بڕ</th><th>یەکە</th><th>نرخی تاک</th><th>نرخی گشتی</th></tr></thead>';
            itemsHtml += '<tbody>';
            
            items.forEach(item => {
                itemsHtml += `<tr>
                    <td>${item.product_name}</td>
                    <td>${item.product_code}</td>
                    <td>${item.quantity}</td>
                    <td>${item.unit_type === 'piece' ? 'دانە' : (item.unit_type === 'box' ? 'کارتۆن' : 'سێت')}</td>
                    <td>${parseInt(item.unit_price).toLocaleString()} دینار</td>
                    <td>${parseInt(item.total_price).toLocaleString()} دینار</td>
                </tr>`;
            });
            
            itemsHtml += '</tbody></table></div>';
            
            Swal.fire({
                title: `کاڵاکانی پسووڵە ${invoiceNumber}`,
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
    <script src="../../js/receiptList.js"></script>
    <script src="../../js/debtTransactions.js"></script>

    <!-- Add Wasting Modal -->
    <div class="modal fade" id="addWastingModal" tabindex="-1" aria-labelledby="addWastingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addWastingModalLabel">زیادکردنی بەفیڕۆچوو</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addWastingForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="wastingDate" class="form-label">بەروار</label>
                                <input type="date" class="form-control" id="wastingDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="wastingNotes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="wastingNotes" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">کاڵاکان</label>
                            <div id="wastingItems">
                                <div class="wasting-item row mb-2">
                                    <div class="col-md-4">
                                        <select class="form-select product-select" required>
                                            <option value="">کاڵا هەڵبژێرە</option>
                                            <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                    data-pieces-per-box="<?php echo $product['pieces_per_box']; ?>"
                                                    data-boxes-per-set="<?php echo $product['boxes_per_set']; ?>">
                                                <?php echo $product['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" class="form-control quantity" placeholder="بڕ" required>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select unit-type">
                                            <option value="piece">دانە</option>
                                            <option value="box">کارتۆن</option>
                                            <option value="set">سێت</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" class="form-control unit-price" placeholder="نرخ" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-item">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" id="addWastingItem">
                                <i class="fas fa-plus"></i> زیادکردنی کاڵا
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveWasting">پاشەکەوت</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Initialize Returns Table -->
    <script>
    const returnsTable = new DataTable('#returnsTable', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ku.json',
        },
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "هەموو"]]
    });

    // Initialize Wastings Table
    const wastingsTable = new DataTable('#wastingsTable', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ku.json',
        },
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "هەموو"]]
    });

    // Handle Wasting Item Addition
    $('#addWastingItem').click(function() {
        const itemHtml = `
            <div class="wasting-item row mb-2">
                <div class="col-md-4">
                    <select class="form-select product-select" required>
                        <option value="">کاڵا هەڵبژێرە</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" 
                                data-pieces-per-box="<?php echo $product['pieces_per_box']; ?>"
                                data-boxes-per-set="<?php echo $product['boxes_per_set']; ?>">
                            <?php echo $product['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control quantity" placeholder="بڕ" required>
                </div>
                <div class="col-md-2">
                    <select class="form-select unit-type">
                        <option value="piece">دانە</option>
                        <option value="box">کارتۆن</option>
                        <option value="set">سێت</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control unit-price" placeholder="نرخ" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-item">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        $('#wastingItems').append(itemHtml);
    });

    // Handle Wasting Item Removal
    $(document).on('click', '.remove-item', function() {
        $(this).closest('.wasting-item').remove();
    });

    // Save Wasting
    $('#saveWasting').click(function() {
        const wastingData = {
            date: $('#wastingDate').val(),
            notes: $('#wastingNotes').val(),
            products: []
        };

        $('.wasting-item').each(function() {
            const productId = $(this).find('.product-select').val();
            const quantity = $(this).find('.quantity').val();
            const unitType = $(this).find('.unit-type').val();
            const unitPrice = $(this).find('.unit-price').val();

            if (productId && quantity && unitPrice) {
                wastingData.products.push({
                    product_id: productId,
                    quantity: quantity,
                    unit_type: unitType,
                    unit_price: unitPrice
                });
            }
        });

        if (wastingData.products.length === 0) {
            alert('تکایە لانیکەم یەک کاڵا زیاد بکە');
            return;
        }

        $.ajax({
            url: '../api/save_wasting.php',
            method: 'POST',
            data: JSON.stringify(wastingData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    alert('بەفیڕۆچووەکە بە سەرکەوتوویی پاشەکەوت کرا');
                    location.reload();
                } else {
                    alert(response.message || 'هەڵەیەک ڕوویدا');
                }
            },
            error: function() {
                alert('هەڵەیەک ڕوویدا لە کاتی پاشەکەوتکردن');
            }
        });
    });

    // Delete Wasting
    $(document).on('click', '.delete-wasting', function() {
        if (confirm('دڵنیای لە سڕینەوەی ئەم بەفیڕۆچووە؟')) {
            const wastingId = $(this).data('id');
            $.ajax({
                url: '../api/receipts/delete_wasting.php',
                method: 'POST',
                data: { wasting_id: wastingId },
                success: function(response) {
                    if (response.success) {
                        alert('بەفیڕۆچووەکە بە سەرکەوتوویی سڕایەوە');
                        location.reload();
                    } else {
                        alert(response.message || 'هەڵەیەک ڕوویدا');
                    }
                },
                error: function() {
                    alert('هەڵەیەک ڕوویدا لە کاتی سڕینەوە');
                }
            });
        }
    });

    // View Wasting Items
    $(document).on('click', '.view-items[data-type="wasting"]', function() {
        const wastingId = $(this).data('id');
        $.ajax({
            url: '../api/receipts/get_wasting_details.php',
            method: 'POST',
            data: { wasting_id: wastingId },
            success: function(response) {
                if (response.status === 'success') {
                    const wasting = response.wasting;
                    let itemsHtml = '';
                    wasting.items.forEach(item => {
                        itemsHtml += `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>${item.unit_type === 'piece' ? 'دانە' : item.unit_type === 'box' ? 'کارتۆن' : 'سێت'}</td>
                                <td>${item.unit_price}</td>
                                <td>${item.total_price}</td>
                            </tr>
                        `;
                    });
                    $('#itemsModalBody').html(`
                        <div class="mb-3">
                            <strong>بەروار:</strong> ${wasting.date}
                        </div>
                        <div class="mb-3">
                            <strong>تێبینی:</strong> ${wasting.notes || '-'}
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>کاڵا</th>
                                    <th>بڕ</th>
                                    <th>یەکە</th>
                                    <th>نرخ</th>
                                    <th>کۆی گشتی</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>کۆی گشتی:</strong></td>
                                    <td>${wasting.total_amount}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `);
                    $('#itemsModal').modal('show');
                } else {
                    alert(response.message || 'هەڵەیەک ڕوویدا');
                }
            },
            error: function() {
                alert('هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان');
            }
        });
    });
    </script>
</body>

</html> 