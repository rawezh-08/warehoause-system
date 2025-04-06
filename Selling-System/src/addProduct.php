<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Unit.php';
require_once __DIR__ . '/models/Product.php';

// Initialize models
$categoryModel = new Category($conn);
$unitModel = new Unit($conn);
$productModel = new Product($conn);

// Get categories and units
$categories = $categoryModel->getAll();
$units = $unitModel->getAll();

// Get latest products (last 5)
try {
    $latestProducts = $productModel->getLatest(5);
    if ($latestProducts === null) {
        $latestProducts = [];
    }
} catch (Exception $e) {
    // If there's an error, set an empty array
    $latestProducts = [];
    error_log("Error loading latest products: " . $e->getMessage());
}
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
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="css/addProduct.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
    <style>
        .product-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-icon {
            width: 60px;
            height: 60px;
            /* display: flex; */
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8f9fa;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-icon i {
            font-size: 1.5rem;
            color: #0d6efd;
        }
        .list-group-item {
            transition: all 0.3s ease;
            padding: 1rem;
            border-left: none;
            border-right: none;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
        .badge.bg-success {
            padding: 0.5rem 0.75rem;
            font-weight: 500;
        }
        .product-info h6 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .product-info small {
            font-size: 0.8rem;
        }
    </style>
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
                <div class="container">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3 class="page-title">زیادکردنی کاڵا</h3>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left column - Product form -->
                        <div class="col-lg-8 col-md-12 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    
                                    <div class="tab-header d-flex flex-wrap mb-4">
                                        <div class="tab-item active me-3 mb-2" data-tab="basic-info">زانیاری بنەڕەتی</div>
                                        <div class="tab-item me-3 mb-2" data-tab="price-info">نرخ</div>
                                        <div class="tab-item mb-2" data-tab="location-info">شوێن لە کۆگا</div>
                                    </div>
                                    
                                    <form id="addProductForm" action="process/add_product.php" method="POST" enctype="multipart/form-data">
                                        <!-- Tab Content: Basic Info -->
                                        <div class="tab-content" id="basic-info-content">
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="productName" class="form-label">ناوی کاڵا</label>
                                                    <input type="text" id="productName" name="name" class="form-control" placeholder="ناوی کاڵا">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="category_id" class="form-label">جۆری کاڵا</label>
                                                    <select id="category_id" name="category_id" class="form-select">
                                                        <option value="" selected disabled>جۆر</option>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-4 mb-3">
                                                    <label for="productCode" class="form-label">کۆدی کاڵا</label>
                                                    <div class="input-group">
                                                        <input type="text" id="productCode" name="code" class="form-control" placeholder="کۆدی کاڵا">
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
                                                    <select id="unit_id" name="unit_id" class="form-select">
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
                                                    <input type="text" id="piecesPerBox" name="pieces_per_box" class="form-control" placeholder="ژمارەی دانە لە کارتۆن" oninput="formatNumber(this)">
                                                </div>
                                                <div class="col-md-4 mb-3" id="boxesPerSetContainer" style="display: none;">
                                                    <label for="boxesPerSet" class="form-label">ژمارەی کارتۆن لە سێت</label>
                                                    <input type="text" id="boxesPerSet" name="boxes_per_set" class="form-control" placeholder="ژمارەی کارتۆن لە سێت" oninput="formatNumber(this)">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4" style="text-align: center;">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">وێنەی کاڵا</label>
                                                    <div class="product-image-upload">
                                                        <div class="image-preview">
                                                            <i class="fas fa-cloud-upload-alt"></i>
                                                            <p>وێنە هەڵبژێرە</p>
                                                        </div>
                                                        <input type="file" id="productImage" name="image" class="form-control d-none">
                                                        <button type="button" id="uploadBtn" class="btn btn-light upload-btn mt-2 w-100">وێنە هەڵبژێرە</button>
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
                                        
                                        <!-- Tab Content: Price Info (Initially Hidden) -->
                                        <div class="tab-content" id="price-info-content" style="display: none;">
                                            <div class="row mb-4">
                                                <div class="col-md-4 mb-3" style="direction: rtl;">
                                                    <label for="buyingPrice" class="form-label">نرخی کڕین</label>
                                                    <div class="input-group">
                                                        <input type="text" id="buyingPrice" name="purchase_price" class="form-control" placeholder="نرخی کڕین" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="sellingPrice" class="form-label">نرخی فرۆشتن</label>
                                                    <div class="input-group">
                                                        <input type="text" id="sellingPrice" name="selling_price_single" class="form-control" placeholder="نرخی فرۆشتن" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="selling_price_wholesale" class="form-label">نرخی فرۆشتن (کۆمەڵ)</label>
                                                    <div class="input-group">
                                                        <input type="text" id="selling_price_wholesale" name="selling_price_wholesale" class="form-control" placeholder="نرخی فرۆشتن (کۆمەڵ)" oninput="formatNumber(this)">
                                                        <span class="input-group-text">د.ع</span>
                                                    </div>
                                                </div>
                                              
                                            </div>
                                            

                                            
                                            
                                            
                                            <hr class="my-4">
                                            
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <button type="button" id="prevTabBtn2" class="btn btn-outline-primary mb-2">
                                                    <i class="fas fa-arrow-right me-2"></i> پێشوو
                                                </button>
                                                <button type="button" id="nextTabBtn2" class="btn btn-outline-primary mb-2">
                                                    دواتر <i class="fas fa-arrow-left ms-2"></i>
                                                </button>
                                                <button type="submit" id="submitBtn2" class="btn btn-primary mb-2" style="display: none;">
                                                    <i class="fas fa-save me-2"></i> زیادکردنی کاڵا
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab Content: Location Info (Initially Hidden) -->
                                        <div class="tab-content" id="location-info-content" style="display: none;">
                                       
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-4 mb-3">
                                                    <label for="min_quantity" class="form-label">کەمترین بڕ</label>
                                                    <input type="text" id="min_quantity" name="min_quantity" class="form-control" placeholder="کەمترین بڕ" oninput="formatNumber(this)">
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
                                                <button type="button" id="prevTabBtn3" class="btn btn-outline-primary mb-2">
                                                    <i class="fas fa-arrow-right me-2"></i> پێشوو
                                                </button>
                                                <button type="submit" id="submitBtn3" class="btn btn-primary mb-2">
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
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">کاڵا نوێکان</h5>
                                    <button class="btn btn-sm btn-outline-primary refresh-products">
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
                                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                                     class="product-thumbnail">
                                                            <?php else: ?>
                                                                <i class="fas fa-box"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="product-info flex-grow-1">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                            <small class="text-muted">
                                                                کۆد: <?php echo htmlspecialchars($product['code']); ?> | 
                                                                زیادکرا: <?php echo date('Y/m/d H:i', strtotime($product['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <span class="badge bg-success">نوێ</span>
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
                            
                            <!-- Instructions Card -->
                            <div class="card shadow-sm">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">ڕێنماییەکان</h5>
                                </div>
                                <div class="card-body">
                                    <div class="instructions">
                                        <div class="instruction-item mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                    <small>1</small>
                                                </div>
                                                <h6 class="mb-0">زانیاری بنەڕەتی</h6>
                                            </div>
                                            <p class="text-muted small mb-0">ناو، جۆر، کۆد و زانیاری بنەڕەتی کاڵاکە بنووسە.</p>
                                        </div>
                                        <div class="instruction-item mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                    <small>2</small>
                                                </div>
                                                <h6 class="mb-0">نرخ و باج</h6>
                                            </div>
                                            <p class="text-muted small mb-0">نرخی کڕین، فرۆشتن، بەکۆمەڵ و ڕێژەی باج دیاری بکە.</p>
                                        </div>
                                        <div class="instruction-item">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="instruction-icon me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                    <small>3</small>
                                                </div>
                                                <h6 class="mb-0">شوێن و بڕ</h6>
                                            </div>
                                            <p class="text-muted small mb-0">دیاریکردنی شوێنی کاڵا لە کۆگا و بڕی سەرەتایی.</p>
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
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="js/include-components.js"></script>
    <script src="js/addProduct.js"></script>
    <script>
    // زیادکردنی فانکشنی فۆرماتی ژمارەکان
    function formatNumber(input) {
        // سڕینەوەی هەموو نەژمارەکان و کۆماکان
        let value = input.value.replace(/[^\d]/g, '');
        
        // زیادکردنی کۆما هەر سێ ژمارەیەک
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        
        // نوێکردنەوەی نرخی ئینپووت
        input.value = value;
    }

    // زیادکردنی event listener بۆ ئینپووتەکان
    document.addEventListener('DOMContentLoaded', function() {
        // ئینپووتەکانی ژمارە
        const numberInputs = [
            'buyingPrice',
            'sellingPrice',
            'selling_price_wholesale',
            'piecesPerBox',
            'boxesPerSet',
            'min_quantity',
            'initialQuantity'
        ];

        // زیادکردنی event listener بۆ هەر ئینپووتێک
        numberInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                // گۆڕینی تایپی ئینپووت
                input.setAttribute('type', 'text');
                
                input.addEventListener('input', function() {
                    formatNumber(this);
                });
            }
        });
        
        // نوێکردنەوەی لیستی کاڵاکان
        document.querySelector('.refresh-products').addEventListener('click', function() {
            window.location.reload();
        });
    });
    </script>
    <style>
    /* زیادکردنی CSS بۆ ئینپووتەکانی ژمارە */
    input[type="text"].form-control {
        text-align: right;
        direction: rtl;
    }
    </style>
</body>
</html> 