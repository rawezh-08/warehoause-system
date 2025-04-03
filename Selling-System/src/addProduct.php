<?php
// You can add PHP logic here if needed
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

    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="css/addProduct.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
</head>
<div>
<div id="navbar-container"></div>

<!-- Sidebar container - will be populated by JavaScript -->
<div id="sidebar-container"></div>
    <!-- Main Content Wrapper -->
    <div id="content">
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
                        <div class="col-lg-8 col-md-12 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    
                                    <div class="tab-header d-flex flex-wrap mb-4">
                                        <div class="tab-item active me-3 mb-2" data-tab="basic-info">زانیاری بنەڕەتی</div>
                                        <div class="tab-item me-3 mb-2" data-tab="price-info">نرخ</div>
                                        <div class="tab-item mb-2" data-tab="location-info">شوێن لە کۆگا</div>
                                    </div>
                                    
                                    <form id="addProductForm">
                                        <!-- Tab Content: Basic Info -->
                                        <div class="tab-content" id="basic-info-content">
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="productName" class="form-label">ناوی کاڵا</label>
                                                    <input type="text" id="productName" class="form-control" placeholder="ناوی کاڵا">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="productCategory" class="form-label">جۆری کاڵا</label>
                                                    <select id="productCategory" class="form-select">
                                                        <option value="" selected disabled>جۆر</option>
                                                        <option value="1">ناوماڵ</option>
                                                        <option value="2">ئەلیکترۆنیات</option>
                                                        <option value="3">جلوبەرگ</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="productCode" class="form-label">کۆدی کاڵا</label>
                                                    <input type="text" id="productCode" class="form-control" placeholder="کۆدی کاڵا">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="unit" class="form-label">یەکە</label>
                                                    <select id="unit" class="form-select">
                                                        <option value="" selected disabled>یەکە</option>
                                                        <option value="piece">دانە</option>
                                                        <option value="box">سندوق</option>
                                                        <option value="kg">کیلۆگرام</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-12 mb-3">
                                                    <label for="description" class="form-label">وەسف</label>
                                                    <textarea id="description" class="form-control" rows="3" placeholder="وەسفی کاڵا لێرە بنووسە..."></textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">وێنەی کاڵا</label>
                                                    <div class="product-image-upload">
                                                        <div class="image-preview">
                                                            <i class="fas fa-cloud-upload-alt"></i>
                                                            <p>وێنە هەڵبژێرە</p>
                                                        </div>
                                                        <input type="file" id="productImage" class="form-control d-none">
                                                        <button type="button" id="uploadBtn" class="btn btn-light upload-btn mt-2 w-100">وێنە هەڵبژێرە</button>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">تایبەتمەندییەکان</label>
                                                    <div class="product-attributes">
                                                        <div class="attribute-item d-flex align-items-center mb-2">
                                                            <input type="text" class="form-control me-2" placeholder="ناو">
                                                            <input type="text" class="form-control" placeholder="بەها">
                                                            <button type="button" class="btn btn-link text-danger remove-attribute">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <button type="button" id="addAttributeBtn" class="btn btn-outline-primary btn-sm mt-2">
                                                            <i class="fas fa-plus me-1"></i> زیادکردنی تایبەتمەندی
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
                                        
                                        <!-- Tab Content: Price Info (Initially Hidden) -->
                                        <div class="tab-content" id="price-info-content" style="display: none;">
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="buyingPrice" class="form-label">نرخی کڕین</label>
                                                    <div class="input-group">
                                                        <input type="number" id="buyingPrice" class="form-control" placeholder="نرخی کڕین">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="sellingPrice" class="form-label">نرخی فرۆشتن</label>
                                                    <div class="input-group">
                                                        <input type="number" id="sellingPrice" class="form-control" placeholder="نرخی فرۆشتن">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="wholesalePrice" class="form-label">نرخی کۆمەڵ</label>
                                                    <div class="input-group">
                                                        <input type="number" id="wholesalePrice" class="form-control" placeholder="نرخی کۆمەڵ">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                </div>
                                                <!-- <div class="col-md-6 mb-3">
                                                    <label for="minWholesaleQty" class="form-label">کەمترین بڕ بۆ کۆمەڵ</label>
                                                    <input type="number" id="minWholesaleQty" class="form-control" placeholder="کەمترین بڕ">
                                                </div> -->
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="discountPercent" class="form-label">ڕێژەی داشکاندن</label>
                                                    <div class="input-group">
                                                        <input type="number" id="discountPercent" class="form-control" placeholder="ڕێژەی داشکاندن">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="tax" class="form-label">باج</label>
                                                    <div class="input-group">
                                                        <input type="number" id="tax" class="form-control" placeholder="باج">
                                                        <span class="input-group-text">%</span>
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
                                                <div class="col-md-6 mb-3">
                                                    <label for="warehouse" class="form-label">کۆگا</label>
                                                    <select id="warehouse" class="form-select">
                                                        <option value="" selected disabled>کۆگا هەڵبژێرە</option>
                                                        <option value="1">کۆگای سەرەکی</option>
                                                        <option value="2">کۆگای دووەم</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="shelf" class="form-label">ڕەف</label>
                                                    <input type="text" id="shelf" class="form-control" placeholder="ڕەفی کاڵا">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-3">
                                                    <label for="minQuantity" class="form-label">کەمترین بڕ</label>
                                                    <input type="number" id="minQuantity" class="form-control" placeholder="کەمترین بڕ بۆ ئاگادارکردنەوە">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="initialQuantity" class="form-label">بڕی سەرەتایی</label>
                                                    <input type="number" id="initialQuantity" class="form-control" placeholder="بڕی سەرەتایی">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-12 mb-3">
                                                    <label for="notes" class="form-label">تێبینی</label>
                                                    <textarea id="notes" class="form-control" rows="3" placeholder="تێبینی لێرە بنووسە..."></textarea>
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
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                <div class="product-icon me-3 bg-light rounded p-2">
                                                    <i class="fas fa-chair text-primary"></i>
                                                </div>
                                                <div class="product-info flex-grow-1">
                                                    <h6 class="mb-0">کورسی ئۆفیس</h6>
                                                    <small class="text-muted">زیادکرا: ١٠ خولەک لەمەوبەر</small>
                                                </div>
                                                <span class="badge bg-success">نوێ</span>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                <div class="product-icon me-3 bg-light rounded p-2">
                                                    <i class="fas fa-couch text-danger"></i>
                                                </div>
                                                <div class="product-info flex-grow-1">
                                                    <h6 class="mb-0">قەنەفەی ڕاکشان</h6>
                                                    <small class="text-muted">زیادکرا: ٢ کاتژمێر لەمەوبەر</small>
                                                </div>
                                                <span class="badge bg-success">نوێ</span>
                                            </div>
                                        </li>
                                        <li class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                <div class="product-icon me-3 bg-light rounded p-2">
                                                    <i class="fas fa-table text-warning"></i>
                                                </div>
                                                <div class="product-info flex-grow-1">
                                                    <h6 class="mb-0">مێزی خواردن</h6>
                                                    <small class="text-muted">زیادکرا: ٥ کاتژمێر لەمەوبەر</small>
                                                </div>
                                                <span class="badge bg-success">نوێ</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent text-center">
                                    <a href="product-list.php" class="btn btn-sm btn-link text-primary">بینینی هەموو کاڵاکان</a>
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
    <!-- Custom JavaScript -->
    <script src="js/include-components.js"></script>
    <script src="js/addProduct.js"></script>
</body>
</html> 