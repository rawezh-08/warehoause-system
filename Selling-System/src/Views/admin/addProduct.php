<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../process/addProduct_logic.php';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زیادکردنی کاڵا - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Global CSS -->
    <!-- <link rel="stylesheet" href="../../assets/css/custom.css"> -->
    <!-- Page CSS -->
    <!-- <link rel="stylesheet" href="../../css/global.css"> -->
    <!-- <link rel="stylesheet" href="../../css/addProduct.css"> -->
    <link rel="stylesheet" href="../../test/main.css">
</head>
<body>
<div>
<div id="navbar-container"></div>
    
<!-- Sidebar container - will be populated by JavaScript -->
<div id="sidebar-container"></div>
    <!-- Main Content Wrapper -->
    <div id="content" class="content-wrapper">
        <!-- Navbar container - will be populated by JavaScript -->
       
            
   
            <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3 class="page-title">زیادکردنی کاڵا</h3>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left column - Product form -->
                        <div class="col-lg-8 col-md-12 mb-4" >
                            <div class="card " style="border: 1px solid var(--blue-border-color);">
                                <div class="card-body">
                                    
                                    <div class="tab-header d-flex flex-wrap mb-4">
                                        <div class="tab-item active me-3 mb-2" data-tab="basic-info">زانیاری بنەڕەتی</div>
                                        <div class="tab-item me-3 mb-2" data-tab="price-info">نرخ و بڕی کاڵا</div>
                                    </div>
                                    
                                    <form id="addProductForm" action="#" method="POST" enctype="multipart/form-data">
                                        <!-- Tab Content: Basic Info -->
                                        <div class="tab-content" id="basic-info-content">
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="productName" class="form-label">ناوی کاڵا</label>
                                                    <input type="text" id="productName" name="name" class="form-control" placeholder="ناوی کاڵا" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="category_id" class="form-label">جۆری کاڵا</label>
                                                    <div class="input-group">
                                                        <select id="category_id" name="category_id" class="form-select" required>
                                                            <option value="" selected disabled>جۆر</option>
                                                            <?php foreach ($categories as $category): ?>
                                                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-4 mb-3">
                                                    <label for="productCode" class="form-label">کۆدی کاڵا</label>
                                                    <div class="input-group">
                                                        <input type="text" id="productCode" name="code" class="form-control" placeholder="کۆدی کاڵا" required>
                                                        <button type="button" class="btn btn-primary" id="generateCode">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="barCode" class="form-label">بارکۆد</label>
                                                    <div class="input-group">
                                                        <input type="text" id="barCode" name="barcode" class="form-control" placeholder="بارکۆد">
                                                        <button type="button" class="btn btn-primary" id="generateBarcode">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="unit_id" class="form-label">یەکە</label>
                                                    <select id="unit_id" name="unit_id" class="form-select" required>
                                                        <option value="" selected disabled>یەکە</option>
                                                        <?php foreach ($units as $unit): ?>
                                                            <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <!-- Dynamic unit quantity inputs -->
                                            <div class="row mb-4" id="unitQuantityContainer" style="display: none;">
                                                <div class="col-md-4 mb-3" id="piecesPerBoxContainer" style="display: none;">
                                                    <label for="piecesPerBox" class="form-label">ژمارەی دانە لە کارتۆن</label>
                                                    <input type="text" id="piecesPerBox" name="pieces_per_box" class="form-control" placeholder="ژمارەی دانە لە کارتۆن" required>
                                                </div>
                                                <div class="col-md-4 mb-3" id="boxesPerSetContainer" style="display: none;">
                                                    <label for="boxesPerSet" class="form-label">ژمارەی کارتۆن لە سێت</label>
                                                    <input type="text" id="boxesPerSet" name="boxes_per_set" class="form-control" placeholder="ژمارەی کارتۆن لە سێت" required>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4" style="text-align: center;">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">وێنەی کاڵا</label>
                                                    <div class="product-image-upload">
                                                    <button type="button" id="uploadBtn" class="image-upload mt-2 w-100">
                                                        <div class="image-preview">
                                                            <i class="fas fa-cloud-upload-alt"></i>
                                                            <p>وێنە هەڵبژێرە</p>
                                                        </div>
                                                        <input type="file" id="productImage" name="image" class="form-control d-none">
                                                        </button>
                                                    </div>
                                                </div>
                                         
                                            </div>
                                            
                                            <hr class="my-4">
                                            
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <button type="button" id="prevTabBtn" class="btn btn-outline-primary mb-2" style="display: none;">
                                                    <i class="fas fa-arrow-right me-2"></i> پێشوو
                                                </button>
                                                <button type="button" id="nextTabBtn" class="btn btn-outline-primary mb-2">
                                                    دواتر <i class="fas fa-arrow-left ms-2"></i>
                                                </button>
                                                <button type="submit" id="submitBtn" class="btn btn-primary mb-2" style="display: none;">
                                                    <i class="fas fa-save me-2"></i> زیادکردنی کاڵا
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab Content: Price Info with Location fields added -->
                                        <div class="tab-content" id="price-info-content" style="display: none;">
                                            <div class="row mb-4">
                                                <div class="col-md-4 mb-3" style="direction: rtl;">
                                                    <label for="buyingPrice" class="form-label">نرخی کڕین</label>
                                                    <div class="input-group">
                                                        <input type="text" id="buyingPrice" name="purchase_price" class="form-control decimal-input" placeholder="نرخی کڕین" required>
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="sellingPrice" class="form-label">نرخی فرۆشتن</label>
                                                    <div class="input-group">
                                                        <input type="text" id="sellingPrice" name="selling_price_single" class="form-control decimal-input" placeholder="نرخی فرۆشتن" required>
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="selling_price_wholesale" class="form-label">نرخی فرۆشتن (کۆمەڵ)</label>
                                                    <div class="input-group">
                                                        <input type="text" id="selling_price_wholesale" name="selling_price_wholesale" class="form-control decimal-input" placeholder="نرخی فرۆشتن (کۆمەڵ)" required>
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        تکایە نرخی فرۆشتن (کۆمەڵ) بنووسە
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Added location fields here -->
                                            <div class="row mb-4">
                                                <div class="col-md-4 mb-3">
                                                    <label for="min_quantity" class="form-label">کەمترین بڕ</label>
                                                    <input type="text" id="min_quantity" name="min_quantity" class="form-control" placeholder="کەمترین بڕ" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="current_quantity" class="form-label">بڕی بەردەست</label>
                                                    <input type="text" id="current_quantity" name="current_quantity" class="form-control" placeholder="بڕی بەردەست" required>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-12 mb-3">
                                                    <label for="notes" class="form-label">تێبینی</label>
                                                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="تێبینی لێرە بنووسە..."></textarea>
                                                </div>
                                            </div>
                                            
                                            <hr class="my-4">
                                            
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <button type="button" id="prevTabBtn2" class="btn btn-outline-primary mb-2">
                                                    <i class="fas fa-arrow-right me-2"></i> پێشوو
                                                </button>
                                                <button type="submit" id="submitBtn2" class="btn btn-primary mb-2">
                                                    <i class="fas fa-save me-2"></i> زیادکردنی کاڵا
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right column - Product list -->
                        <div class="col-lg-4 col-md-12">
    
                            <div class="card  mb-4" style="border: 1px solid var(--blue-border-color);">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">کاڵا نوێکان</h5>
                                    <button class="btn btn-sm btn-outline-primary refresh-products" style="padding: 4px 18px ;">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <?php if (!empty($latestProducts)): ?>
                                            <?php foreach ($latestProducts as $product): ?>
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-center">
                                                        <div class="product-icon me-3">
                                                            <?php if (!empty($product['image'])): ?>
                                                                <?php
                                                                // Extract just the filename from the image path
                                                                $filename = basename($product['image']);
                                                                // Use our new API endpoint with absolute path
                                                                $imageUrl = "../../api/product_image.php?filename=" . urlencode($filename);
                                                                ?>
                                                                <img src="<?php echo $imageUrl; ?>" 
                                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                                     class="product-thumbnail">
                                                            <?php else: ?>
                                                                <i class="fas fa-box"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="product-info flex-grow-1">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                            <small class="" style="color: grey">
                                                                کۆد: <?php echo htmlspecialchars($product['code']); ?> | 
                                                                زیادکرا: <?php echo date('Y/m/d H:i', strtotime($product['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item text-center text-muted">
                                                هیچ کاڵایەک نەدۆزرایەوە
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent text-center">
                                    <a href="products.php" class="btn btn-sm btn-link text-primary">بینینی هەموو کاڵاکان</a>
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
    <!-- Global AJAX Configuration -->
    <script src="../../js/ajax-config.js"></script>
    <!-- Component loading script -->
    <script src="../../js/include-components.js"></script>
    <!-- Add Product Script -->
    <script src="../../js/addProduct.js"></script>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">زیادکردنی جۆری نوێ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">ناوی جۆر</label>
                            <input type="text" class="form-control" id="categoryName" name="name" required>
                        </div>
                    
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveCategoryBtn">زیادکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Management Script -->
    <script>
        $(document).ready(function() {
            // Save category button click handler
            $('#saveCategoryBtn').on('click', function() {
                const categoryName = $('#categoryName').val();
                const categoryDescription = $('#categoryDescription').val();
                
                if (!categoryName) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'تکایە ناوی جۆر بنووسە'
                    });
                    return;
                }
                
                // Send AJAX request to add the category
                $.ajax({
                    url: '../../api/add_category.php',
                    type: 'POST',
                    data: {
                        name: categoryName,
                        description: categoryDescription
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Close the modal
                            $('#addCategoryModal').modal('hide');
                            
                            // Add the new category to the dropdown
                            $('#category_id').append(
                                $('<option>', {
                                    value: response.category_id,
                                    text: categoryName,
                                    selected: true
                                })
                            );
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو',
                                text: 'جۆر بە سەرکەوتوویی زیادکرا'
                            });
                            
                            // Reset the form
                            $('#addCategoryForm')[0].reset();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message || 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی جۆر'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندی بە سێرڤەر'
                        });
                    }
                });
            });
            
            // Clear the form when the modal is closed
            $('#addCategoryModal').on('hidden.bs.modal', function() {
                $('#addCategoryForm')[0].reset();
            });
        });
    </script>

    <!-- Add this before the closing </body> tag -->
    <script>
        $(document).ready(function() {
            // Function to handle decimal input
            function handleDecimalInput(input) {
                // Replace Arabic decimal with English decimal
                let value = input.value.replace(/٫/g, '.');
                
                // Remove any non-numeric characters except decimal point
                value = value.replace(/[^\d.]/g, '');
                
                // Ensure only one decimal point
                let parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                
                // Limit to 2 decimal places
                if (parts.length > 1 && parts[1].length > 2) {
                    value = parts[0] + '.' + parts[1].substring(0, 2);
                }
                
                input.value = value;
            }

            // Handle input events
            $('.decimal-input').on('input', function(e) {
                handleDecimalInput(this);
            });

            // Handle keypress events
            $('.decimal-input').on('keypress', function(e) {
                // Get the pressed key
                let charCode = e.which ? e.which : e.keyCode;
                
                // Allow special keys (backspace, delete, arrows, etc)
                if (e.ctrlKey || e.altKey || e.metaKey ||
                    charCode < 32 || // Control characters
                    (charCode >= 37 && charCode <= 40)) { // Arrow keys
                    return true;
                }
                
                // Get the pressed character
                let char = String.fromCharCode(charCode);
                
                // Allow numbers
                if (/[0-9]/.test(char)) {
                    return true;
                }
                
                // Allow decimal point (both English and Arabic)
                if (char === '.' || char === '٫') {
                    // Check if there's already a decimal point
                    let value = this.value;
                    if (!value.includes('.') && !value.includes('٫')) {
                        return true;
                    }
                }
                
                // Block all other characters
                e.preventDefault();
                return false;
            });

            // Handle paste events
            $('.decimal-input').on('paste', function(e) {
                e.preventDefault();
                let pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                
                // Clean the pasted data
                pastedData = pastedData.replace(/[^\d.٫]/g, ''); // Remove anything that's not a number or decimal
                pastedData = pastedData.replace(/٫/g, '.'); // Replace Arabic decimal with English decimal
                
                // Insert at cursor position
                let startPos = this.selectionStart;
                let endPos = this.selectionEnd;
                let value = this.value;
                
                this.value = value.substring(0, startPos) + pastedData + value.substring(endPos);
                handleDecimalInput(this);
                
                // Set cursor position after pasted text
                this.setSelectionRange(startPos + pastedData.length, startPos + pastedData.length);
            });
        });
    </script>
</body>
</html> 