<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>باڵانسی ئێمە لەگەڵ فرۆشیارەکان</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <style>
        .positive-balance {
            color: #198754; /* Bootstrap success green */
            font-weight: bold;
        }
        
        .negative-balance {
            color: #dc3545; /* Bootstrap danger red */
            font-weight: bold;
        }
        
        .balance-card {
            background-color: #f8f9fa;
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .balance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 15px;
            font-weight: bold;
        }
        
        .we-owe-them {
            border-left: 5px solid #dc3545;
        }
        
        .they-owe-us {
            border-left: 5px solid #198754;
        }
        
        .total-balances {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            overflow: hidden;
        }
        
        .total-balances:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .total-balances .card-body {
            padding: 2rem 1.5rem;
        }
        
        .total-balances .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .total-balances .balance-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .total-balances.overall-balance {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .total-balances.positive-card {
            background: linear-gradient(135deg, #48c6ef 0%, #6f86d6 100%);
        }
        
        .total-balances.negative-card {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
        }
        
        .total-balances.overall-balance *,
        .total-balances.positive-card *,
        .total-balances.negative-card * {
            color: white !important;
        }
        
        .balance-amount {
            font-size: 2rem;
            font-weight: 700;
            margin: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .balance-amount.animate {
            animation: numberChange 0.5s ease-out;
        }
        
        @keyframes numberChange {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .small-text {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .view-toggle-btn {
            margin-right: 10px;
        }
        
        /* Card styles */
        .supplier-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }
        
        .supplier-card {
            background: white;
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .supplier-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }
        
        .supplier-card .card-header {
            padding: 1.5rem;
            background: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .supplier-card .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .supplier-card .card-title i {
            color: #4a5568;
            font-size: 1.1rem;
        }
        
        .supplier-card .contact-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #718096;
            font-size: 0.95rem;
        }
        
        .supplier-card .status-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .supplier-card .status-badge.positive {
            background: #def7ec;
            color: #03543f;
        }
        
        .supplier-card .status-badge.negative {
            background: #fde8e8;
            color: #9b1c1c;
        }
        
        .supplier-card .status-badge.neutral {
            background: #e5e7eb;
            color: #4b5563;
        }
        
        .supplier-card .card-body {
            padding: 1.5rem;
        }
        
        .supplier-card .balance-section {
            text-align: center;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .supplier-card .balance-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            z-index: 0;
            background: linear-gradient(45deg, transparent, currentColor);
        }
        
        .supplier-card .balance-amount {
            position: relative;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            z-index: 1;
        }
        
        .supplier-card .balance-amount i {
            font-size: 1.5rem;
        }
        
        .supplier-card .balance-positive {
            color: #059669;
            background: #ecfdf5;
        }
        
        .supplier-card .balance-negative {
            color: #dc2626;
            background: #fef2f2;
        }
        
        .supplier-card .balance-zero {
            color: #6b7280;
            background: #f9fafb;
        }
        
        .supplier-card .balance-label {
            position: relative;
            color: currentColor;
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
            z-index: 1;
        }
        
        .supplier-card .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding: 0 0.5rem;
        }
        
        .supplier-card .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.875rem 1.25rem;
            font-size: 0.95rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            font-weight: 600;
        }
        
        .supplier-card .btn i {
            font-size: 1.1rem;
        }
        
        .supplier-card .btn-primary {
            background: #3b82f6;
            border: none;
            color: white;
        }
        
        .supplier-card .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        
        .supplier-card .btn-secondary {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #4b5563;
        }
        
        .supplier-card .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .supplier-cards-container {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0.75rem;
            }
            
            .supplier-card .card-actions {
                grid-template-columns: 1fr;
            }
            
            .supplier-card .card-header,
            .supplier-card .card-body {
                padding: 1.25rem;
            }
            
            .supplier-card .balance-amount {
                font-size: 1.75rem;
            }

            .supplier-card .status-badge {
                top: 1.25rem;
                right: 1.25rem;
                padding: 0.375rem 0.75rem;
                font-size: 0.8125rem;
            }
        }

        /* Add these styles for the tabs */
        .nav-tabs {
            border-bottom: 2px solid #e5e7eb;
        }

        .nav-tabs .nav-link {
            color: #6b7280;
            border: none;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            color: #3b82f6;
        }

        .nav-tabs .nav-link.active {
            color: #3b82f6;
            border: none;
            border-bottom: 2px solid #3b82f6;
            margin-bottom: -2px;
        }

        .nav-tabs .nav-link i {
            font-size: 1rem;
        }

        /* Modal styles */
        .modal-lg {
            max-width: 800px;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
        }

        .input-group-text {
            background-color: #f3f4f6;
            color: #6b7280;
            border-color: #e5e7eb;
        }

        /* Add these styles for select2 enhancement */
        .select2-container--bootstrap-5 {
            width: 100% !important;
        }

        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            min-height: 38px;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            padding: 0.375rem 0.75rem;
        }

        .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #212529;
        }

        .select2-container--bootstrap-5 .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div id="content">
        <!-- Navbar -->
        <div id="navbar-container"></div>

        <!-- Sidebar container -->
        <div id="sidebar-container"></div>

        <!-- Main Content Area -->
        <div class="main-content mt-5">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">باڵانسی ئێمە لەگەڵ فرۆشیارەکان</h4>
                    <div class="d-flex">
                        <button class="btn btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> نوێکردنەوە
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card total-balances overall-balance">
                            <div class="card-body text-center">
                                <i class="fas fa-balance-scale balance-icon"></i>
                                <h5 class="card-title">کۆی باڵانس</h5>
                                <p class="text-muted small-text mb-2">(جیاوازی نێوان قەرزی ئێمە و ئەوان)</p>
                                <h2 class="balance-amount" id="totalBalance">0 دینار</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card total-balances positive-card">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-circle-down balance-icon"></i>
                                <h5 class="card-title">پارەی لای ئێمە</h5>
                                <p class="text-muted small-text mb-2">(ئەو پارەیەی کە دابینکەرەکان دەبێت بیدەن بە ئێمە)</p>
                                <h2 class="balance-amount" id="totalTheyOweUs">0 دینار</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card total-balances negative-card">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-circle-up balance-icon"></i>
                                <h5 class="card-title">پارەی لای ئەوان</h5>
                                <p class="text-muted small-text mb-2">(ئەو پارەیەی کە ئێمە دەبێت بیدەین بە دابینکەرەکان)</p>
                                <h2 class="balance-amount" id="totalWeOweThem">0 دینار</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card total-balances" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <div class="card-body text-center">
                                <i class="fas fa-hand-holding-usd balance-icon"></i>
                                <h5 class="card-title">کۆی پارەی پێشەکی</h5>
                                <p class="text-muted small-text mb-2">(کۆی پارەی پێشەکییەکان)</p>
                                <h2 class="balance-amount" id="totalInitialPayments">0 دینار</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="searchInput" placeholder="گەڕان بە ناوی فرۆشیار، ژمارە تەلەفۆن...">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select mb-2" id="supplierFilter">
                                    <option value="">هەموو دابینکەرەکان</option>
                                    <!-- دابینکەرەکان بە دینامیکی زیاد دەکرێن -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select mb-2" id="sortSelect">
                                    <option value="name">ڕیزکردن بە ناو</option>
                                    <option value="balance-high">زۆرترین باڵانس</option>
                                    <option value="balance-low">کەمترین باڵانس</option>
                                    <option value="they-owe-us">ئەوانەی قەرزداری ئێمەن</option>
                                    <option value="we-owe-them">ئەوانەی قەرزمان لایانە</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button class="btn btn-secondary flex-grow-1" id="resetFilterBtn">
                                    <i class="fas fa-undo"></i> ڕیسێت
                                </button>
                               
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card View for supplier balances -->
                <div id="supplierCardsView" class="mb-4">
                    <div class="supplier-cards-container" id="supplierCardsContainer">
                        <!-- Example of enhanced supplier card structure -->
                        <div class="supplier-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="fas fa-store"></i>
                                    <span>ناوی فرۆشیار</span>
                                </div>
                                <div class="contact-info">
                                    <i class="fas fa-phone"></i>
                                    <span>ژمارە تەلەفۆن</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="balance-section balance-positive">
                                    <div class="balance-amount">
                                        <i class="fas fa-arrow-up"></i>
                                        <span>1,500,000 دینار</span>
                                    </div>
                                    <div class="balance-label">باڵانسی کۆتایی</div>
                                </div>
                                <div class="card-actions">
                                    <button type="button" class="btn btn-primary" onclick="openPaymentModal('1')">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>زیادکردنی پارەدان</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="viewHistory('1')">
                                        <i class="fas fa-history"></i>
                                        <span>مێژووی پارەدان</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true" 
         data-keyboard="false" data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">زیادکردنی پارەدان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPaymentForm">
                        <input type="hidden" id="supplierId">
                        <div class="mb-3">
                            <label for="supplierName" class="form-label">ناوی فرۆشیار</label>
                            <input type="text" class="form-control" id="supplierName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="currentBalance" class="form-label">باڵانسی ئێستا</label>
                            <input type="text" class="form-control" id="currentBalance" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">بڕی پارەدان</label>
                            <input type="number" class="form-control" id="paymentAmount" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="paymentDirection" class="form-label">جۆری پارەدان</label>
                            <select class="form-select" id="paymentDirection" required>
                                <option value="to_supplier">پارەدان بە دابینکەر (کەمکردنەوەی قەرزی ئێمە لەسەر دابینکەر)</option>
                                <option value="from_supplier">وەرگرتنی پارە لە دابینکەر (کەمکردنەوەی قەرزی دابینکەر لەسەر ئێمە)</option>
                                <option value="adjust_balance">ڕێکخستنی باڵانس (ڕاستکردنەوەی هەڵە)</option>
                            </select>
                            <div class="form-text text-muted mt-2">
                                <ul class="mb-0 small">
                                    <li>پارەدان بە دابینکەر: کاتێک ئێمە پارە دەدەین بە دابینکەر بۆ کەمکردنەوەی قەرزی خۆمان</li>
                                    <li>وەرگرتنی پارە لە دابینکەر: کاتێک دابینکەر پارە دەدات بە ئێمە بۆ کەمکردنەوەی قەرزی خۆی</li>
                                    <li>ڕێکخستنی باڵانس: بۆ ڕاستکردنەوەی هەر هەڵەیەک لە تۆمارکردنی قەرزەکان</li>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="paymentNote" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="paymentNote" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="button" class="btn btn-primary" id="savePaymentBtn">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Balance Modal -->
    <div class="modal fade" id="addBalanceModal" tabindex="-1" aria-labelledby="addBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBalanceModalLabel">زیادکردنی باڵانسی نوێ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="balanceTypeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="supplier-tab" data-bs-toggle="tab" 
                                    data-bs-target="#supplier-tab-pane" type="button" role="tab">
                                <i class="fas fa-truck"></i> دابینکەر
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="client-tab" data-bs-toggle="tab" 
                                    data-bs-target="#client-tab-pane" type="button" role="tab">
                                <i class="fas fa-user"></i> کڕیار
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="balanceTypeContent">
                        <!-- Supplier Tab -->
                        <div class="tab-pane fade show active" id="supplier-tab-pane" role="tabpanel" tabindex="0">
                            <form id="addSupplierBalanceForm">
                                <div class="mb-3">
                                    <label for="supplierSelect" class="form-label">دابینکەر</label>
                                    <select class="form-select" id="supplierSelect" required>
                                        <option value="">هەڵبژاردنی دابینکەر</option>
                                        <!-- Suppliers will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="initialBalance" class="form-label">باڵانسی سەرەتایی</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="initialBalance" required>
                                        <span class="input-group-text">دینار</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="balanceType" class="form-label">جۆری باڵانس</label>
                                    <select class="form-select" id="balanceType" required>
                                        <option value="we_owe">ئێمە قەرزارین (ئێمە دەبێت بیدەینەوە)</option>
                                        <option value="they_owe">ئەوان قەرزارن (ئەوان دەبێت بیدەنەوە)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="supplierNote" class="form-label">تێبینی</label>
                                    <textarea class="form-control" id="supplierNote" rows="2"></textarea>
                                </div>
                            </form>
                        </div>

                        <!-- Client Tab -->
                        <div class="tab-pane fade" id="client-tab-pane" role="tabpanel" tabindex="0">
                            <form id="addClientBalanceForm">
                                <div class="mb-3">
                                    <label for="clientSelect" class="form-label">کڕیار</label>
                                    <select class="form-select" id="clientSelect" required>
                                        <option value="">هەڵبژاردنی کڕیار</option>
                                        <!-- Clients will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="clientInitialBalance" class="form-label">باڵانسی سەرەتایی</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="clientInitialBalance" required>
                                        <span class="input-group-text">دینار</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="clientBalanceType" class="form-label">جۆری باڵانس</label>
                                    <select class="form-select" id="clientBalanceType" required>
                                        <option value="they_owe">ئەوان قەرزارن (ئەوان دەبێت بیدەنەوە)</option>
                                        <option value="we_owe">ئێمە قەرزارین (ئێمە دەبێت بیدەینەوە)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="clientNote" class="form-label">تێبینی</label>
                                    <textarea class="form-control" id="clientNote" rows="2"></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">پاشگەزبوونەوە</button>
                    <button type="button" class="btn btn-primary" id="saveNewBalance">پاشەکەوتکردن</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../js/include-components.js"></script>
    <script src="../../js/bank/script.js"></script>
    <script>
        $(document).ready(function() {
            // Make the supplier cards clickable to view supplier details
            $(document).on('click', '.supplier-card', function(e) {
                // Check if the click was on a button or within a button
                if (!$(e.target).closest('button').length) {
                    const supplierId = $(this).data('id');
                    window.location.href = `supplier_detail.php?id=${supplierId}`;
                }
            });
            
            // Make view-history button open supplier details page
            $(document).on('click', '.view-history-btn', function() {
                const supplierId = $(this).data('id');
                window.location.href = `supplier_detail.php?id=${supplierId}`;
            });

            // Initialize the modal
            const addBalanceModal = new bootstrap.Modal(document.getElementById('addBalanceModal'));
            
            // Initialize select2 for enhanced dropdowns
            $('#supplierSelect, #clientSelect').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'گەڕان و هەڵبژاردن...',
                allowClear: true
            });

            // Load suppliers into dropdown
            function loadSuppliers() {
                // Example AJAX call - replace with your actual API endpoint
                $.get('/api/suppliers', function(suppliers) {
                    const select = $('#supplierSelect');
                    select.empty().append('<option value="">هەڵبژاردنی دابینکەر</option>');
                    suppliers.forEach(supplier => {
                        select.append(`<option value="${supplier.id}">${supplier.name}</option>`);
                    });
                });
            }

            // Load clients into dropdown
            function loadClients() {
                // Example AJAX call - replace with your actual API endpoint
                $.get('/api/clients', function(clients) {
                    const select = $('#clientSelect');
                    select.empty().append('<option value="">هەڵبژاردنی کڕیار</option>');
                    clients.forEach(client => {
                        select.append(`<option value="${client.id}">${client.name}</option>`);
                    });
                });
            }

            // Load data when modal opens
            $('#addBalanceModal').on('show.bs.modal', function () {
                loadSuppliers();
                loadClients();
            });

            // Handle form submission
            $('#saveNewBalance').click(function() {
                const activeTab = $('#balanceTypeTabs .nav-link.active').attr('id');
                let formData;
                
                if (activeTab === 'supplier-tab') {
                    formData = {
                        type: 'supplier',
                        supplierId: $('#supplierSelect').val(),
                        initialBalance: $('#initialBalance').val(),
                        balanceType: $('#balanceType').val(),
                        note: $('#supplierNote').val()
                    };
                } else {
                    formData = {
                        type: 'client',
                        clientId: $('#clientSelect').val(),
                        initialBalance: $('#clientInitialBalance').val(),
                        balanceType: $('#clientBalanceType').val(),
                        note: $('#clientNote').val()
                    };
                }
                
                // Validate required fields
                const form = activeTab === 'supplier-tab' ? 
                    $('#addSupplierBalanceForm')[0] : 
                    $('#addClientBalanceForm')[0];
                    
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                // Here you would typically send the data to your server
                console.log('Form Data:', formData);
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو',
                    text: 'باڵانسی نوێ زیاد کرا',
                    confirmButtonText: 'باشە'
                }).then(() => {
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addBalanceModal'));
                    modal.hide();
                    form.reset();
                    $('#supplierSelect, #clientSelect').val('').trigger('change');
                });
            });
        });
    </script>
</body>
</html> 