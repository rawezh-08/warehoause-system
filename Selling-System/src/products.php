<?php
require_once 'config/database.php';

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_id = isset($_GET['category']) ? $_GET['category'] : '';
$unit_id = isset($_GET['unit']) ? $_GET['unit'] : '';

// Build base query for total count
$count_query = "SELECT COUNT(*) as total 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN units u ON p.unit_id = u.id 
                WHERE 1=1";

// Build main query
$query = "SELECT p.*, c.name as category_name, u.name as unit_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN units u ON p.unit_id = u.id 
          WHERE 1=1";

$params = array();

if (!empty($search)) {
    $where_clause = " AND (p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?)";
    $query .= $where_clause;
    $count_query .= $where_clause;
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_id)) {
    $where_clause = " AND p.category_id = ?";
    $query .= $where_clause;
    $count_query .= $where_clause;
    $params[] = $category_id;
}

if (!empty($unit_id)) {
    $where_clause = " AND p.unit_id = ?";
    $query .= $where_clause;
    $count_query .= $where_clause;
    $params[] = $unit_id;
}

// Get total records count
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to main query
$query .= " ORDER BY p.id DESC LIMIT $offset, $records_per_page";

// Get categories for filter
$categories_query = "SELECT id, name FROM categories ORDER BY name";
$categories = $conn->query($categories_query)->fetchAll(PDO::FETCH_ASSOC);

// Get units for filter
$units_query = "SELECT id, name FROM units ORDER BY name";
$units = $conn->query($units_query)->fetchAll(PDO::FETCH_ASSOC);

// Get products for Select2
$products_query = "SELECT id, name, code, barcode FROM products ORDER BY name";
$products_list = $conn->query($products_query)->fetchAll(PDO::FETCH_ASSOC);

// Get products
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیستی کاڵاکان</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/employeePayment/style.css">
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
        }

    

        
        /* Adjust pagination display for many pages */
        .pagination-numbers {
            flex-wrap: wrap;
            max-width: 300px;
            overflow: hidden;
        }
        
        .pagination-numbers .btn {
            margin-bottom: 5px;
        }

        /* RTL Toast Container Styles */
        .toast-container-rtl {
            right: 0 !important;
            left: auto !important;
        }

        .toast-container-rtl .swal2-toast {
            margin-right: 1em !important;
            margin-left: 0 !important;
        }

        .toast-container-rtl .swal2-toast .swal2-title {
            text-align: right !important;
        }

        .toast-container-rtl .swal2-toast .swal2-icon {
            margin-right: 0 !important;
            margin-left: 0.5em !important;
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-top: 4px;
        }
        
        /* Fix Select2 clear button (X) styling */
        .select2-container--bootstrap-5 .select2-selection--single {
            padding-left: 2rem !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__clear {
            left: 0.5rem !important;
            right: auto !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            margin: 0 !important;
            padding: 0 !important;
            background: none !important;
            border: none !important;
            color: #6c757d !important;
            font-size: 1rem !important;
            line-height: 1 !important;
            z-index: 2;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__clear:hover {
            color: #dc3545 !important;
        }

        .filter-section .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Pagination Styles */
        .pagination-circle .page-link {
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 50% !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            margin: 0 3px;
        }

        .pagination-circle .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .pagination-circle .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #0d6efd;
        }

        .pagination-circle .page-item.active .page-link:hover {
            background-color: #0d6efd;
            color: white;
        }

        .pagination-circle .page-item.disabled .page-link {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .pagination-circle .page-link {
                width: 32px;
                height: 32px;
                font-size: 0.8rem;
                margin: 0 2px;
            }
            
            .pagination-container {
                overflow-x: auto;
                padding-bottom: 1rem;
            }
            
            .pagination {
                flex-wrap: nowrap;
                margin-bottom: 0;
            }
        }

        /* Action Buttons Style */
        .btn-group .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
            border-radius: 50% !important;
        }

        .btn-group .btn i {
            font-size: 14px;
            line-height: 1;
        }

        /* Hover effects */
        .btn-group .btn:hover {
            transform: translateY(-2px);
            transition: all 0.2s;
        }

        .btn-group .btn-outline-primary:hover {
            background-color: #0d6efd;
            color: white;
        }

        .btn-group .btn-outline-info:hover {
            background-color: #0dcaf0;
            color: white;
        }

        .btn-group .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
        }

        /* Active state */
        .btn-group .btn:active {
            transform: translateY(0);
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
        <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h3 class="page-title">لیستی کاڵاکان</h3>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form id="filterForm" class="row g-3">
                                            <div class="col-md-4">
                            <label for="search" class="form-label">گەڕان بە ناو/کۆد/بارکۆد</label>
                            <select class="form-select select2" id="search" name="search">
                                <option value="">هەموو کاڵاکان</option>
                                <?php foreach ($products_list as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo $search == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['name'] . ' - ' . $product['code'] . ' - ' . $product['barcode']); ?>
                                    </option>
                                <?php endforeach; ?>
                                                </select>
                                            </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">جۆری کاڵا</label>
                            <select class="form-select select2" id="category" name="category">
                                <option value="">هەموو جۆرەکان</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                            <div class="col-md-3">
                            <label for="unit" class="form-label">یەکە</label>
                            <select class="form-select select2" id="unit" name="unit">
                                <option value="">هەموو یەکەکان</option>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?php echo htmlspecialchars($unit['id']); ?>" <?php echo $unit_id == $unit['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($unit['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100" id="resetFilter">
                                                    <i class="fas fa-redo me-2"></i> ڕیسێت
                                                </button>
                                            </div>
                                        </form>
                        </div>

                <!-- Products Table -->
                                <div class="card shadow-sm">
                                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">لیستی کاڵاکان</h5>
                        <a href="addProduct.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> زیادکردنی کاڵای نوێ
                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-container">
                                            <!-- Table Controls -->
                                            <div class="table-controls mb-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                                        <div class="records-per-page">
                                                            <label class="me-2">نیشاندان:</label>
                                                            <div class="custom-select-wrapper">
                                                <select id="recordsPerPage" class="form-select form-select-sm rounded-pill">
                                                    <option value="5" <?php echo $records_per_page == 5 ? 'selected' : ''; ?>>5</option>
                                                    <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                                    <option value="25" <?php echo $records_per_page == 25 ? 'selected' : ''; ?>>25</option>
                                                    <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Table Content -->
                                            <div class="table-responsive">
                                <table class="table table-bordered custom-table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                            <th>وێنە</th>
                                            <th>کۆد</th>
                                            <th>بارکۆد</th>
                                            <th>ناو</th>
                                            <th>جۆر</th>
                                            <th>یەکە</th>
                                            <th>شوێن</th>
                                            <th>دانە لە کارتۆن</th>
                                            <th>کارتۆن لە سێت</th>
                                            <th>نرخی کڕین</th>
                                            <th>نرخی فرۆشتن</th>
                                            <th>نرخی فرۆشتن (کۆمەڵ)</th>
                                            <th>بڕی ئێستا</th>
                                            <th>بڕی کەمترین</th>
                                                            <th>کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                        <?php foreach ($products as $index => $product): ?>
                                        <tr>
                                            <td><?php echo ($page - 1) * $records_per_page + $index + 1; ?></td>
                                            <td>
                                                <?php if (!empty($product['image'])): ?>
                                                    <img style="border-radius: 5px;" src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="product-image">
                                                <?php else: ?>
                                                    <i class="fas fa-image text-muted"></i>
                                                <?php endif; ?>
                                                            </td>
                                            <td><?php echo htmlspecialchars($product['code']); ?></td>
                                            <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['unit_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['shelf'] ?: '-'); ?></td>
                                            <td><?php echo $product['pieces_per_box'] ?: '-'; ?></td>
                                            <td><?php echo $product['boxes_per_set'] ?: '-'; ?></td>
                                            <td><?php echo number_format($product['purchase_price'], 0); ?> د.ع</td>
                                            <td><?php echo number_format($product['selling_price_single'], 0); ?> د.ع</td>
                                            <td><?php echo number_format($product['selling_price_wholesale'], 0); ?> د.ع</td>
                                            <td><?php echo $product['current_quantity']; ?></td>
                                            <td><?php echo $product['min_quantity']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary rounded-circle edit-product" 
                                                            data-id="<?php echo $product['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                            data-code="<?php echo htmlspecialchars($product['code']); ?>"
                                                            data-barcode="<?php echo htmlspecialchars($product['barcode']); ?>"
                                                            data-category="<?php echo $product['category_id']; ?>"
                                                            data-unit="<?php echo $product['unit_id']; ?>"
                                                            data-shelf="<?php echo htmlspecialchars($product['shelf']); ?>"
                                                            data-pieces-per-box="<?php echo $product['pieces_per_box']; ?>"
                                                            data-boxes-per-set="<?php echo $product['boxes_per_set']; ?>"
                                                            data-purchase="<?php echo $product['purchase_price']; ?>"
                                                            data-selling-single="<?php echo $product['selling_price_single']; ?>"
                                                            data-selling-wholesale="<?php echo $product['selling_price_wholesale']; ?>"
                                                            data-current-qty="<?php echo $product['current_quantity']; ?>"
                                                            data-min-qty="<?php echo $product['min_quantity']; ?>"
                                                            data-notes="<?php echo htmlspecialchars($product['notes']); ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-notes" 
                                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                            data-notes="<?php echo htmlspecialchars($product['notes'] ?: 'هیچ تێبینیەک نییە'); ?>">
                                                        <i class="fas fa-sticky-note"></i>
                                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-product" data-id="<?php echo $product['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                            <!-- Edit Product Modal -->
                            <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">گۆڕینی زانیاری کاڵا</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                        <div class="modal-body">
                                            <form id="editProductForm">
                                                <input type="hidden" id="edit_product_id" name="id">
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_name" class="form-label">ناوی کاڵا</label>
                                                        <input type="text" class="form-control" id="edit_name" name="name" required>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_code" class="form-label">کۆدی کاڵا</label>
                                                        <input type="text" class="form-control" id="edit_code" name="code" required>
                                                            </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_barcode" class="form-label">بارکۆد</label>
                                                        <input type="text" class="form-control" id="edit_barcode" name="barcode">
                                                        </div>
                                                    </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="edit_category" class="form-label">جۆری کاڵا</label>
                                                        <select class="form-select" id="edit_category" name="category_id" required>
                                                            <?php foreach ($categories as $category): ?>
                                                                <option value="<?php echo $category['id']; ?>">
                                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="edit_unit" class="form-label">یەکەی کاڵا</label>
                                                        <select class="form-select" id="edit_unit" name="unit_id" required>
                                                            <?php foreach ($units as $unit): ?>
                                                                <option value="<?php echo $unit['id']; ?>">
                                                                    <?php echo htmlspecialchars($unit['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                </select>
                                            </div>
                                            </div>
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_shelf" class="form-label">شوێن</label>
                                                        <input type="text" class="form-control" id="edit_shelf" name="shelf">
                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_pieces_per_box" class="form-label">دانە لە کارتۆن</label>
                                                        <input type="number" class="form-control" id="edit_pieces_per_box" name="pieces_per_box" min="0">
                                </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_boxes_per_set" class="form-label">کارتۆن لە سێت</label>
                                                        <input type="number" class="form-control" id="edit_boxes_per_set" name="boxes_per_set" min="0">
                            </div>
                        </div>
                        <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_purchase_price" class="form-label">نرخی کڕین</label>
                                                        <input type="number" class="form-control" id="edit_purchase_price" name="purchase_price" required>
                                        </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_selling_price_single" class="form-label">نرخی فرۆشتن (دانە)</label>
                                                        <input type="number" class="form-control" id="edit_selling_price_single" name="selling_price_single" required>
                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="edit_selling_price_wholesale" class="form-label">نرخی فرۆشتن (کۆمەڵ)</label>
                                                        <input type="number" class="form-control" id="edit_selling_price_wholesale" name="selling_price_wholesale">
                                                            </div>
                                                        </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="edit_current_quantity" class="form-label">بڕی ئێستا</label>
                                                        <input type="number" class="form-control" id="edit_current_quantity" name="current_quantity" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="edit_min_quantity" class="form-label">کەمترین بڕ</label>
                                                        <input type="number" class="form-control" id="edit_min_quantity" name="min_quantity" required>
                                                            </div>
                                                        </div>
                                                <div class="row">
                                                    <div class="col-12 mb-3">
                                                        <label for="edit_notes" class="form-label">تێبینی</label>
                                                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                                                    </div>
                                                </div>
                                            </form>
                                            </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                                            <button type="button" class="btn btn-primary" id="saveProductChanges">پاشەکەوتکردن</button>
                                                                </div>
                                                                </div>
                                                                </div>
                                            </div>
                                            
                                            <!-- Table Pagination -->
                                            <div class="table-pagination mt-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6 mb-2 mb-md-0">
                                                        <div class="pagination-info">
                                            نیشاندانی <span id="startRecord"><?php echo min(($page - 1) * $records_per_page + 1, $total_records); ?></span> تا 
                                            <span id="endRecord"><?php echo min($page * $records_per_page, $total_records); ?></span> لە کۆی 
                                            <span id="totalRecords"><?php echo $total_records; ?></span> تۆمار
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="pagination-controls d-flex justify-content-md-end">
                                            <button id="prevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                            <div id="paginationNumbers" class="pagination-numbers d-flex">
                                                <?php
                                                // Show first page
                                                if ($page > 3) {
                                                    echo '<button class="btn btn-sm btn-outline-primary rounded-circle me-2" onclick="changePage(1)">1</button>';
                                                    if ($page > 4) {
                                                        echo '<span class="btn btn-sm rounded-circle me-2 disabled">...</span>';
                                                    }
                                                }

                                                // Show pages around current page
                                                for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
                                                    echo '<button class="btn btn-sm ' . ($page == $i ? 'btn-primary' : 'btn-outline-primary') . ' rounded-circle me-2" onclick="changePage(' . $i . ')">' . $i . '</button>';
                                                }

                                                // Show last page
                                                if ($page < $total_pages - 2) {
                                                    if ($page < $total_pages - 3) {
                                                        echo '<span class="btn btn-sm rounded-circle me-2 disabled">...</span>';
                                                    }
                                                    echo '<button class="btn btn-sm btn-outline-primary rounded-circle me-2" onclick="changePage(' . $total_pages . ')">' . $total_pages . '</button>';
                                                }
                                                ?>
                                                            </div>
                                            <button id="nextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                                                                <i class="fas fa-chevron-left"></i>
                                                            </button>
                                        </div>
                                    </div>
                                </div>
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
    <!-- Custom JavaScript -->
    <script src="js/include-components.js"></script>
    <script src="js/expensesHistory/script.js"></script>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'هەڵبژاردن بکە',
                allowClear: true,
                language: {
                    noResults: function() {
                        return "هیچ ئەنجامێک نەدۆزرایەوە";
                    }
                }
            });

            // Auto filter on change
            $('#search, #category, #unit').on('change', function() {
                $('#filterForm').submit();
            });

            // Records per page change
            $('#recordsPerPage').on('change', function() {
                const params = new URLSearchParams(window.location.search);
                params.set('per_page', this.value);
                params.set('page', '1');
                window.location.href = '?' + params.toString();
            });

            // Reset filter
            $('#resetFilter').on('click', function() {
                $('#search').val('').trigger('change');
                $('#category').val('').trigger('change');
                $('#unit').val('').trigger('change');
                window.location.href = 'products.php';
            });

            // Initialize edit product buttons
            $('.edit-product').on('click', function() {
                const data = $(this).data();
                
                // Fill the form with product data
                $('#edit_product_id').val(data.id);
                $('#edit_name').val(data.name);
                $('#edit_code').val(data.code);
                $('#edit_barcode').val(data.barcode);
                $('#edit_category').val(data.category);
                $('#edit_unit').val(data.unit);
                $('#edit_shelf').val(data.shelf);
                $('#edit_pieces_per_box').val(data.piecesPerBox);
                $('#edit_boxes_per_set').val(data.boxesPerSet);
                $('#edit_purchase_price').val(data.purchase);
                $('#edit_selling_price_single').val(data.sellingSingle);
                $('#edit_selling_price_wholesale').val(data.sellingWholesale);
                $('#edit_current_quantity').val(data.currentQty);
                $('#edit_min_quantity').val(data.minQty);
                $('#edit_notes').val(data.notes);
                
                // Show/hide unit-specific inputs based on selected unit
                toggleUnitInputs($('#edit_unit').val());
                
                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                editModal.show();
            });

            // Handle unit change in edit modal
            $('#edit_unit').on('change', function() {
                toggleUnitInputs($(this).val());
            });

            // Function to toggle unit-specific inputs
            function toggleUnitInputs(unitId) {
                const piecesPerBoxInput = $('#edit_pieces_per_box').closest('.col-md-4');
                const boxesPerSetInput = $('#edit_boxes_per_set').closest('.col-md-4');

                // Hide both inputs by default
                piecesPerBoxInput.hide();
                boxesPerSetInput.hide();

                // Show relevant inputs based on unit type
                switch(unitId) {
                    case '1': // دانە
                        break;
                    case '2': // دانە و کارتۆن
                        piecesPerBoxInput.show();
                        break;
                    case '3': // دانە و کارتۆن و سێت
                        piecesPerBoxInput.show();
                        boxesPerSetInput.show();
                        break;
                }
            }

            // Handle save changes button
            $('#saveProductChanges').on('click', function() {
                const formData = new FormData(document.getElementById('editProductForm'));
                
                fetch('process/updateProduct.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'سەرکەوتوو بوو!',
                            text: 'زانیاریەکانی کاڵاکە بە سەرکەوتوویی گۆڕدرا.',
                            icon: 'success',
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: data.message || 'کێشەیەک ڕوویدا لە گۆڕینی زانیاریەکان.',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'کێشەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                });
            });

            // Initialize view notes buttons
            $('.view-notes').on('click', function() {
                const data = $(this).data();
                Swal.fire({
                    title: 'تێبینیەکانی ' + data.name,
                    text: data.notes,
                    icon: 'info',
                    confirmButtonText: 'باشە'
                });
            });
        });

        // Pagination functionality
        function changePage(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            window.location.href = '?' + params.toString();
        }

        // Previous page button
        document.getElementById('prevPageBtn').addEventListener('click', function() {
            if (<?php echo $page; ?> > 1) {
                changePage(<?php echo $page - 1; ?>);
            }
        });

        // Next page button
        document.getElementById('nextPageBtn').addEventListener('click', function() {
            if (<?php echo $page; ?> < <?php echo $total_pages; ?>) {
                changePage(<?php echo $page + 1; ?>);
            }
        });

        // Load navbar and sidebar
        document.addEventListener('DOMContentLoaded', function() {
            fetch('components/navbar.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('navbar-container').innerHTML = data;
                });

            fetch('components/sidebar.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('sidebar-container').innerHTML = data;
                });

            // Initialize delete product buttons
            document.querySelectorAll('.delete-product').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    Swal.fire({
                        title: 'دڵنیای لە سڕینەوەی ئەم کاڵایە؟',
                        text: "ئەم کردارە ناگەڕێتەوە!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'بەڵێ، بسڕەوە',
                        cancelButtonText: 'پاشگەزبوونەوە'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Send delete request
                            fetch('process/deleteProduct.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'id=' + productId
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'سڕایەوە!',
                                        text: 'کاڵاکە بە سەرکەوتوویی سڕایەوە.',
                                        icon: 'success',
                                        confirmButtonText: 'باشە'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'هەڵە!',
                                        text: data.message || 'کێشەیەک ڕوویدا لە سڕینەوەی کاڵاکە.',
                                        icon: 'error',
                                        confirmButtonText: 'باشە'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    title: 'هەڵە!',
                                    text: 'کێشەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە.',
                                    icon: 'error',
                                    confirmButtonText: 'باشە'
                                });
                            });
                        }
                    });
                });
            });
        });
    </script>
</body>
</html> 