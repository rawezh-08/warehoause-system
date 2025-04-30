<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all sales with details
$salesQuery = "SELECT s.*, 
               c.name as customer_name, 
               c.phone1 as customer_phone,
               SUM(si.total_price) as total_amount
               FROM sales s 
               LEFT JOIN customers c ON s.customer_id = c.id
               LEFT JOIN sale_items si ON s.id = si.sale_id 
               GROUP BY s.id
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
        /* Table styles */
        .table-search-input {
            background-color: transparent !important;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        .custom-table {
            margin-bottom: 0;
        }

        .custom-table td,
        .custom-table th {
            padding: 0.75rem;
            vertical-align: middle;
            border: 1px solid #dee2e6;
            white-space: nowrap;
            font-size: 0.9rem;
        }

        .custom-table thead th {
            background-color: #f8f9fa;
            font-weight: 500;
            text-align: center;
            border-bottom: 2px solid #dee2e6;
        }

        .custom-table tbody td {
            text-align: center;
        }

        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }

        /* Action buttons */
        .action-buttons .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
        }

        .action-buttons .btn i {
            font-size: 0.875rem;
        }

        /* Status badges */
        .badge {
            padding: 0.4em 0.8em;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 0.25rem;
        }

        /* Card styles */
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem;
        }

        /* Search input group */
        .input-group .input-group-text {
            border-radius: 0 0.25rem 0.25rem 0;
        }

        .input-group .form-control {
            border-radius: 0.25rem 0 0 0.25rem;
        }

        /* Pagination styles */
        .pagination-wrapper {
            padding: 0.75rem 1rem;
            background-color: #fff;
            border-top: 1px solid #dee2e6;
        }

        .pagination-controls .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .pagination-numbers .btn {
            min-width: 32px;
            height: 32px;
            padding: 0;
            margin: 0 2px;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Main content spacing */
        .main-content {
            margin-top: 60px !important;
            padding: 1.5rem;
        }

        @media (max-width: 768px) {
            .card-header .row {
                flex-direction: column;
            }
            
            .card-header .col-md-6 {
                width: 100%;
                margin-bottom: 1rem;
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
                        <i class="fas fa-undo"></i> پسووڵەکانی گەڕانەوە
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
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" id="salesSearchInput" class="form-control table-search-input" placeholder="گەڕان بۆ پسووڵە...">
                                        <span class="input-group-text bg-primary text-white">
                                            <i class="fas fa-search"></i>
                                        </span>
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
                                            <th>ناوی کڕیار</th>
                                            <th>ژمارەی پەیوەندی</th>
                                            <th>بەروار</th>
                                            <th>جۆری پارەدان</th>
                                            <th>کۆی گشتی</th>
                                            <th>دۆخ</th>
                                            <th>کردارەکان</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($sales)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">هیچ پسووڵەیەک نەدۆزرایەوە</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($sales as $index => $sale): ?>
                                                <?php 
                                                    $total = $sale['total_amount'] ?? 0;
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
                                                    <td><?= htmlspecialchars($sale['customer_name'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($sale['customer_phone'] ?? 'N/A') ?></td>
                                                    <td><?= formatDate($sale['date']) ?></td>
                                                    <td>
                                                        <?php if($sale['payment_type'] == 'cash'): ?>
                                                            <span class="badge bg-success">نەقد</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">قەرز</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= number_format($total, 0, '.', ',') ?> IQD</td>
                                                    <td>
                                                        <?php if($paymentStatus == 'paid'): ?>
                                                            <span class="badge bg-success">پارەدراوە</span>
                                                        <?php elseif($paymentStatus == 'partial'): ?>
                                                            <span class="badge bg-warning">بەشێکی دراوە</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">پارە نەدراوە</span>
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
                                                                    class="btn btn-sm btn-outline-primary rounded-circle edit-btn"
                                                                    data-id="<?php echo $sale['id']; ?>"
                                                                    title="دەستکاری پسووڵە">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-info rounded-circle show-invoice-items"
                                                                    data-invoice="<?php echo $sale['invoice_number']; ?>"
                                                                    title="بینینی هەموو کاڵاکان">
                                                                    <i class="fas fa-list"></i>
                                                                </button>
                                                                <!-- Add Return Button -->
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-warning rounded-circle return-products-btn"
                                                                    data-id="<?php echo $sale['id']; ?>"
                                                                    data-invoice="<?php echo $sale['invoice_number']; ?>"
                                                                    title="گەڕاندنەوەی کاڵا">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                                <?php
                                                                // Check if sale can be returned (no payments made)
                                                                $canReturn = true;
                                                                $canDelete = true;
                                                                
                                                                // Check for debt transactions
                                                                foreach ($debtTransactions as $transaction) {
                                                                    if ($transaction['reference_id'] == $sale['id'] && 
                                                                        ($transaction['transaction_type'] == 'collection' || 
                                                                         $transaction['transaction_type'] == 'payment')) {
                                                                        $canReturn = false;
                                                                        $canDelete = false;
                                                                        break;
                                                                    }
                                                                }
                                                                
                                                                // Check for product returns
                                                                $returnQuery = "SELECT COUNT(*) as count FROM product_returns WHERE receipt_id = :sale_id AND receipt_type = 'selling'";
                                                                $returnStmt = $conn->prepare($returnQuery);
                                                                $returnStmt->bindParam(':sale_id', $sale['id']);
                                                                $returnStmt->execute();
                                                                $returnCount = $returnStmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                                
                                                                if ($returnCount > 0) {
                                                                    $canDelete = false;
                                                                }
                                                                
                                                                if ($canDelete): ?>
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger rounded-circle delete-sale"
                                                                    data-id="<?php echo $sale['id']; ?>"
                                                                    data-invoice="<?php echo $sale['invoice_number']; ?>"
                                                                    title="سڕینەوە">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                                <?php endif; ?>
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
                                    <span id="salesShowing">نیشاندانی <span id="salesFrom">1</span> بۆ <span id="salesTo">10</span> لە <span id="salesTotalItems">0</span> پسووڵە</span>
                                </div>
                                <div class="pagination-controls">
                                    <button class="btn btn-sm btn-outline-secondary" id="salesPrevPage" disabled>
                                        <i class="fas fa-chevron-right"></i> پێشوو
                                    </button>
                                    <div class="pagination-numbers" id="salesPagination"></div>
                                    <button class="btn btn-sm btn-outline-secondary" id="salesNextPage">
                                        دواتر <i class="fas fa-chevron-left"></i>
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
    <script src="../../js/receiptList.js"></script>
</body>

</html> 