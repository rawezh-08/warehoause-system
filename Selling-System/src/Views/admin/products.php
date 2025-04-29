<?php
require_once '../../process/products_logic.php';
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/employeePayment/style.css">
    <link rel="stylesheet" href="../../css/products.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
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
                <div class="filter-section" style="border-radius: 24px;">
                    <form id="filterForm" class="row g-3" method="GET" action="" autocomplete="off" aria-label="بەگەڕانی کاڵاکان">
                        <div class="col-md-4">
                            <label for="search" class="form-label">گەڕان بە ناو ، کۆد، بارکۆد</label>
                            <div class="search-wrapper">
                                <div class="input-group">
                                    <input type="text" class="form-control search-input" id="search" name="search" style="border-radius: 24px;"
                                           placeholder="ناوی کاڵا، کۆد یان بارکۆد..." 
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           aria-label="گەڕان بە ناو ، کۆد، بارکۆد">
                                    <button type="button" class="btn btn-primary search-btn" style="border-radius: 24px; margin-right: 8px;" aria-label="گەڕان">
                                        <img src="../../assets/icons/search.svg" alt="گەڕان">
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
                            <select class="form-select select2" id="category" name="category" style="border-radius: 24px;  display: flex; justify-content: center; align-items: center;" aria-label="هەڵبژاردنی جۆری کاڵا" title="جۆری کاڵا">
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
                            <select class="form-select select2" id="unit" name="unit" aria-label="هەڵبژاردنی یەکە" title="یەکە">
                                <option value="">هەموو یەکەکان</option>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?php echo htmlspecialchars($unit['id']); ?>" <?php echo $unit_id == $unit['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($unit['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary w-100" id="resetFilter" style="border-radius: 24px;">
                                <i class="fas fa-redo me-2"></i> ڕیسێت
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Products Table -->
                <div class="card shadow-sm" style="border-radius: 24px; border: 1px solid #9ec5ff;">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">لیستی کاڵاکان</h5>
                        <a href="../../Views/admin/addProduct.php" class="btn btn-primary add-product-btn">
                           زیادکردنی کاڵای نوێ  <img src="../../assets/icons/add-square.svg" alt="">
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
                                                <select id="recordsPerPage" class="form-select form-select-sm rounded-pill" aria-label="نیشاندانی تۆمارەکان لە هەر پەڕەیەک" title="نیشاندانی تۆمارەکان">
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
                                            <th style="background-color: #cde1ff; border: none;">بارودۆخی کۆگا</th>
                                            <th style="background-color: #cde1ff; border: none;">کردارەکان</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTableBody">
                                        <?php foreach ($products as $index => $product): ?>
                                        <tr>
                                            <td><?php echo ($page - 1) * $records_per_page + $index + 1; ?></td>
                                            <td>
                                                <?php if (!empty($product['image'])): ?>
                                                    <?php
                                                    // Extract just the filename from the image path
                                                    $imagePath = $product['image'];
                                                    $filename = basename($imagePath);
                                                    // Use our new API endpoint with absolute path
                                                    $imageUrl = "../../api/product_image.php?filename=" . urlencode($filename);
                                                    ?>
                                                    <img src="<?php echo $imageUrl; ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         class="product-image"
                                                         style="cursor: pointer;"
                                                         onclick="showLargeImage(this.src, '<?php echo htmlspecialchars($product['name']); ?>')"
                                                         data-bs-toggle="tooltip"
                                                         data-bs-placement="top"
                                                         title="<?php echo htmlspecialchars($product['name']); ?>">
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
                                                <?php 
                                                $current_quantity = isset($product['current_quantity']) ? (int)$product['current_quantity'] : 0;
                                                if ($current_quantity < 10): ?>
                                                    <span class="badge bg-danger rounded-pill">مەترسیدارە (<?php echo $current_quantity; ?>)</span>
                                                <?php elseif ($current_quantity >= 10 && $current_quantity <= 50): ?>
                                                    <span class="badge bg-warning rounded-pill">بڕێکی کەم بەردەستە (<?php echo $current_quantity; ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success rounded-pill">کۆنتڕۆڵ (<?php echo $current_quantity; ?>)</span>
                                                <?php endif; ?>
                                            </td>
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
                                                            data-notes="<?php echo htmlspecialchars($product['notes']); ?>"
                                                            aria-label="گۆڕینی <?php echo htmlspecialchars($product['name']); ?>"
                                                            title="گۆڕین">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-circle view-notes" 
                                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                            data-notes="<?php echo htmlspecialchars($product['notes'] ?: 'هیچ تێبینیەک نییە'); ?>"
                                                            aria-label="بینینی تێبینیەکانی <?php echo htmlspecialchars($product['name']); ?>"
                                                            title="بینینی تێبینیەکان">
                                                        <i class="fas fa-sticky-note"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-product" 
                                                            data-id="<?php echo $product['id']; ?>"
                                                            aria-label="سڕینەوەی <?php echo htmlspecialchars($product['name']); ?>"
                                                            title="سڕینەوە">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">گۆڕینی زانیاری کاڵا</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" autocomplete="on">
                        <input type="hidden" id="edit_product_id" name="id">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_name" class="form-label">ناوی کاڵا</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required autocomplete="off">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_code" class="form-label">کۆدی کاڵا</label>
                                <input type="text" class="form-control" id="edit_code" name="code" required autocomplete="off">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_barcode" class="form-label">بارکۆد</label>
                                <input type="text" class="form-control" id="edit_barcode" name="barcode" autocomplete="off">
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
                                <label for="edit_pieces_per_box" class="form-label">دانە لە کارتۆن</label>
                                <input type="number" class="form-control" id="edit_pieces_per_box" name="pieces_per_box" min="0" autocomplete="off">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_boxes_per_set" class="form-label">کارتۆن لە سێت</label>
                                <input type="number" class="form-control" id="edit_boxes_per_set" name="boxes_per_set" min="0" autocomplete="off">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_purchase_price" class="form-label">نرخی کڕین</label>
                                <input type="number" class="form-control" id="edit_purchase_price" name="purchase_price" required autocomplete="off">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_selling_price_single" class="form-label">نرخی فرۆشتن (دانە)</label>
                                <input type="number" class="form-control" id="edit_selling_price_single" name="selling_price_single" required autocomplete="off">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_selling_price_wholesale" class="form-label">نرخی فرۆشتن (کۆمەڵ)</label>
                                <input type="number" class="form-control" id="edit_selling_price_wholesale" name="selling_price_wholesale" autocomplete="off">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_min_quantity" class="form-label">کەمترین بڕ</label>
                                <input type="number" class="form-control" id="edit_min_quantity" name="min_quantity" required autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_image" class="form-label">وێنەی کاڵا</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                    <img id="current_product_image" src="" alt="وێنەی کاڵا" style="width: 50px; height: 50px; object-fit: contain; display: none;">
                                </div>
                                <small class="text-muted">بۆ گۆڕینی وێنە، وێنەیەکی نوێ هەڵبژێرە</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="edit_notes" class="form-label">تێبینی</label>
                                <textarea class="form-control" id="edit_notes" name="notes" rows="3" autocomplete="off" aria-label="تێبینیەکان"></textarea>
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../../js/include-components.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Products JS -->
    <script src="../../js/products.js"></script>
</body>
</html> 