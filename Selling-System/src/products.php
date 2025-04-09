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
    <link rel="stylesheet" href="css/products.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Custom styles for this page -->
    
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
                <div class="filter-section" style="border-radius: 24px;">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">گەڕان بە ناو ، کۆد، بارکۆد</label>
                            <div class="search-wrapper">
                                <div class="input-group">
                                    <input type="text" class="form-control search-input" id="search" name="search" style="border-radius: 24px;"
                                           placeholder="ناوی کاڵا، کۆد یان بارکۆد..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="button" class="btn btn-primary search-btn" style="border-radius: 24px; margin-right: 8px;">
                                        <img src="assets/icons/search.svg" alt="">
                                    </button>
                                </div>
                                <div class="search-suggestions" style="display: none;">
                                    <div class="suggestion-header">
                                        <i class="fas fa-clock"></i>
                                        <span>دواترین کاڵاکان</span>
                                    </div>
                                    <div class="suggestions-list"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3" >
                            <label for="category" class="form-label">جۆری کاڵا</label>
                            <select class="form-select select2" id="category" name="category" style="border-radius: 24px;  display: flex; justify-content: center; align-items: center;">
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
                        <!-- <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100" id="resetFilter">
                                <i class="fas fa-redo me-2"></i> ڕیسێت
                            </button>
                        </div> -->
                    </form>
                </div>

                <!-- Products Table -->
                                <div class="card shadow-sm" style="border-radius: 24px; border: 1px solid #9ec5ff;">
                                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">لیستی کاڵاکان</h5>
                        <a href="addProduct.php" class="btn btn-primary add-product-btn">
                           زیادکردنی کاڵای نوێ  <img src="assets/icons/add-square.svg" alt="">
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
                                <table class="table table-bordered custom-table table-hover" style="border-radius: 16px;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="background-color: #cde1ff; border: none;">#</th>
                                            <th style="background-color:#cde1ff; border: none;">وێنە</th>
                                            <th style="background-color: #cde1ff; border: none;">کۆد</th>
                                            <th style="background-color: #cde1ff; border: none;">بارکۆد</th>
                                            <th style="background-color: #cde1ff; border: none;">ناو</th>
                                                <th style="background-color: #cde1ff; border: none;">جۆر</th>
                                            <th style="background-color: #cde1ff; border: none;">یەکە</th>
                                
                                            <th style="background-color: #cde1ff; border: none;">دانە لە کارتۆن</th>
                                            <th style="background-color: #cde1ff; border: none;">کارتۆن لە سێت</th>
                                            <th style="background-color: #cde1ff; border: none;">نرخی کڕین</th>
                                            <th style="background-color: #cde1ff; border: none;">نرخی فرۆشتن</th>
                                            <th style="background-color: #cde1ff; border: none;">نرخی فرۆشتن (کۆمەڵ)</th>
                                      
                                                    <th style="background-color: #cde1ff; border: none;">بڕی کەمترین</th>
                                                            <th style="background-color: #cde1ff; border: none;">کردارەکان</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                        <?php foreach ($products as $index => $product): ?>
                                        <tr>
                                            <td><?php echo ($page - 1) * $records_per_page + $index + 1; ?></td>
                                            <td>
                                                <?php if (!empty($product['image'])): ?>
                                                    <div class="product-image-container">
                                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                             class="product-image"
                                                             data-bs-toggle="tooltip"
                                                             data-bs-placement="top"
                                                             title="<?php echo htmlspecialchars($product['name']); ?>">
                                                                </div>
                                                <?php else: ?>
                                                    <div class="no-image-placeholder">
                                                        <i class="fas fa-image"></i>
                                                                </div>
                                                <?php endif; ?>
                                                            </td>
                                            <td><?php echo htmlspecialchars($product['code']); ?></td>
                                            <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['unit_name']); ?></td>
                                        
                                            <td><?php echo $product['pieces_per_box'] ?: '-'; ?></td>
                                            <td><?php echo $product['boxes_per_set'] ?: '-'; ?></td>
                                            <td><?php echo number_format($product['purchase_price'], 0); ?> د.ع</td>
                                            <td><?php echo number_format($product['selling_price_single'], 0); ?> د.ع</td>
                                            <td><?php echo number_format($product['selling_price_wholesale'], 0); ?> د.ع</td>
                                          
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
                                                
                                                            data-pieces-per-box="<?php echo $product['pieces_per_box']; ?>"
                                                            data-boxes-per-set="<?php echo $product['boxes_per_set']; ?>"
                                                            data-purchase="<?php echo $product['purchase_price']; ?>"
                                                            data-selling-single="<?php echo $product['selling_price_single']; ?>"
                                                            data-selling-wholesale="<?php echo $product['selling_price_wholesale']; ?>"
                                                         
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
        $(document).ready(function() {
            // Initialize other Select2 dropdowns
            $('#category, #unit').select2({
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

            // Debounce function
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Handle search functionality
            const $searchWrapper = $('.search-wrapper');
            const $searchInput = $('.search-input');
            const $searchBtn = $('.search-btn');
            const $suggestions = $('.search-suggestions');
            const $suggestionsList = $('.suggestions-list');

            // Load initial suggestions when input is focused
            $searchInput.on('focus', function() {
                if (!$searchInput.val()) {
                    loadSuggestions('', true);
                }
                $suggestions.show();
            });

            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.search-wrapper').length) {
                    $suggestions.hide();
                }
            });

            // Handle search input
            $searchInput.on('input', debounce(function() {
                const query = $(this).val();
                if (query) {
                    loadSuggestions(query, false);
                } else {
                    loadSuggestions('', true);
                }
            }, 300));

            // Handle search button click
            $searchBtn.on('click', function() {
                const query = $searchInput.val();
                if (query) {
                    $('#filterForm').submit();
                }
            });

            // Load suggestions from server
            function loadSuggestions(query, showInitial) {
                $searchWrapper.addClass('loading');
                
                $.ajax({
                    url: 'process/search_products.php',
                    data: {
                        term: query,
                        show_initial: showInitial ? '1' : '0'
                    },
                    success: function(response) {
                        $suggestionsList.empty();
                        
                        if (response.results && response.results.length > 0) {
                            response.results.forEach(function(product) {
                                const $item = $('<div class="suggestion-item">')
                                    .html(`
                                        <div class="product-name">${product.name}</div>
                                        <div class="product-details">
                                            <span><i class="fas fa-barcode"></i>${product.code}</span>
                                            <span><i class="fas fa-folder"></i>${product.category}</span>
                                            <span><i class="fas fa-tag"></i>${product.selling_price}</span>
                                        </div>
                                    `)
                                    .on('click', function() {
                                        $searchInput.val(product.name);
                                        $suggestions.hide();
                                        $('#filterForm').submit();
                                    });
                                
                                $suggestionsList.append($item);
                            });
                        }
                    },
                    complete: function() {
                        $searchWrapper.removeClass('loading');
                    }
                });
            }

            // Handle category and unit changes
            $('#category, #unit').on('change', function() {
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
                $searchInput.val('');
                $('#category').val(null).trigger('change');
                $('#unit').val(null).trigger('change');
                window.location.href = 'products.php';
            });

            // Handle Enter key in search input
            $searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#filterForm').submit();
                }
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
            
                $('#edit_pieces_per_box').val(data.piecesPerBox);
                $('#edit_boxes_per_set').val(data.boxesPerSet);
                $('#edit_purchase_price').val(data.purchase);
                $('#edit_selling_price_single').val(data.sellingSingle);
                $('#edit_selling_price_wholesale').val(data.sellingWholesale);
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
                
                // Remove commas from number fields before sending
                const numberFields = ['purchase_price', 'selling_price_single', 'selling_price_wholesale', 'min_quantity'];
                numberFields.forEach(field => {
                    const value = formData.get(field);
                    if (value) {
                        formData.set(field, value.replace(/,/g, ''));
                    }
                });

                fetch('process/updateProduct.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid server response');
                    }
                    
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
                        throw new Error(data.message || 'کێشەیەک ڕوویدا لە گۆڕینی زانیاریەکان.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'هەڵە!',
                        text: error.message || 'کێشەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە.',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                });
            });

            // Initialize delete product buttons
            $('.delete-product').on('click', function() {
                const productId = $(this).data('id');
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

        // Pagination functionality
        function changePage(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            window.location.href = '?' + params.toString();
        }

        // Document ready event for navigation elements
        document.addEventListener('DOMContentLoaded', function() {
            // Load navbar and sidebar
            if (document.getElementById('navbar-container')) {
                fetch('components/navbar.php')
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('navbar-container').innerHTML = data;
                    });
            }

            if (document.getElementById('sidebar-container')) {
                fetch('components/sidebar.php')
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('sidebar-container').innerHTML = data;
                        
                        // Initialize sidebar dropdowns after loading
                        const dropdownItems = document.querySelectorAll('.sidebar-menu .menu-item > a');
                        
                        dropdownItems.forEach(item => {
                            if (item.getAttribute('href') && item.getAttribute('href').startsWith('#')) {
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    
                                    const submenuId = this.getAttribute('href');
                                    const submenu = document.querySelector(submenuId);
                                    
                                    if (submenu) {
                                        // Toggle current submenu
                                        submenu.classList.toggle('show');
                                        
                                        // Toggle dropdown icon
                                        const dropdownIcon = this.querySelector('.dropdown-icon');
                                        if (dropdownIcon) {
                                            dropdownIcon.classList.toggle('rotate');
                                        }
                                    }
                                });
                            }
                        });
                    });
            }

            // Initialize pagination buttons if they exist
            const prevPageBtn = document.getElementById('prevPageBtn');
            const nextPageBtn = document.getElementById('nextPageBtn');
            
            if (prevPageBtn) {
                prevPageBtn.addEventListener('click', function() {
                    if (<?php echo $page; ?> > 1) {
                        changePage(<?php echo $page - 1; ?>);
                    }
                });
            }

            if (nextPageBtn) {
                nextPageBtn.addEventListener('click', function() {
                    if (<?php echo $page; ?> < <?php echo $total_pages; ?>) {
                        changePage(<?php echo $page + 1; ?>);
                    }
                });
            }

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Add click event to product images
            document.querySelectorAll('.product-image-container').forEach(container => {
                container.addEventListener('click', function() {
                    const img = this.querySelector('img');
                    if (img) {
                        const modal = document.createElement('div');
                        modal.className = 'modal fade image-modal';
                        modal.innerHTML = `
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="${img.src}" alt="${img.alt}">
                                    </div>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        const modalInstance = new bootstrap.Modal(modal);
                        modalInstance.show();
                        
                        modal.addEventListener('hidden.bs.modal', function () {
                            modal.remove();
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 