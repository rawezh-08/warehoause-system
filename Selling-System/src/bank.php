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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/global.css">
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
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .view-toggle-btn {
            margin-right: 10px;
        }
        
        /* Card styles */
        .supplier-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 10px;
        }
        
        .supplier-card {
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-radius: 10px;
            margin-bottom: 0;
            cursor: pointer;
        }
        
        .supplier-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .supplier-card.positive {
            border-left: 5px solid #198754;
        }
        
        .supplier-card.negative {
            border-left: 5px solid #dc3545;
        }
        
        .supplier-card.zero {
            border-left: 5px solid #6c757d;
        }
        
        .card-icons {
            font-size: 1.2rem;
            margin-left: 5px;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .card-actions .btn {
            flex: 1;
            min-width: 120px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .card-title {
            margin: 0;
            font-size: 1.1rem;
            flex: 1;
        }
        
        .card-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        
        .card-body {
            padding: 15px;
        }
        
        .card-text {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .card-balance {
            font-size: 1.2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .supplier-cards-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .card-actions .btn {
                min-width: 100px;
            }
            
            .card-title {
                font-size: 1rem;
            }
            
            .card-balance {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 576px) {
            .supplier-cards-container {
                gap: 10px;
            }
            
            .card-actions {
                flex-direction: column;
            }
            
            .card-actions .btn {
                width: 100%;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-badge {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content" style="margin-top: 60px;">
        <div class="container">
            <!-- Navbar -->
            <div id="navbar-container"></div>

            <!-- Page Header -->
            <div class="top-nav">
                <!-- Sidebar container -->
                <div id="sidebar-container"></div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">باڵانسی ئێمە لەگەڵ فرۆشیارەکان</h4>
                    <div class="d-flex">
                        <button class="btn btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> نوێکردنەوە
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light total-balances">
                        <div class="card-body text-center">
                            <h5 class="card-title">باڵانسی گشتی</h5>
                            <h2 class="mt-3" id="totalBalance">0 دینار</h2>
                            <p id="balanceDescription" class="mb-0"></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light total-balances">
                        <div class="card-body text-center">
                            <h5 class="card-title">کۆی قەرزی ئەوان لە ئێمە</h5>
                            <h2 class="mt-3 positive-balance" id="totalTheyOweUs">0 دینار</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light total-balances">
                        <div class="card-body text-center">
                            <h5 class="card-title">کۆی قەرزی ئێمە لە ئەوان</h5>
                            <h2 class="mt-3 negative-balance" id="totalWeOweThem">0 دینار</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchInput" placeholder="گەڕان بە ناوی فرۆشیار، ژمارە تەلەفۆن...">
                                <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                    <i class="fas fa-search"></i> گەڕان
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="sortSelect">
                                <option value="name">ڕیزکردن بە ناو</option>
                                <option value="balance-high">زۆرترین باڵانس</option>
                                <option value="balance-low">کەمترین باڵانس</option>
                                <option value="they-owe-us">ئەوانەی قەرزداری ئێمەن</option>
                                <option value="we-owe-them">ئەوانەی قەرزمان لایانە</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card View for supplier balances -->
            <div id="supplierCardsView" class="mb-4">
                <div class="supplier-cards-container" id="supplierCardsContainer">
                    <!-- Supplier cards will be loaded dynamically -->
                </div>
            </div>

            <!-- Table View for supplier balances -->
            <!-- <div id="supplierTableView" class="card mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="balanceTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ناوی فرۆشیار</th>
                                    <th>ژمارەی مۆبایل</th>
                                    <th>باڵانس</th>
                                    <th>بارودۆخ</th>
                                    <th>کردارەکان</th>
                                </tr>
                            </thead>
                            <tbody id="balanceTableBody">
                             </tbody>
                        </table>
                    </div>
                </div>
            </div> -->
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
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
                                <option value="to_supplier">پارەدان بۆ فرۆشیار (ئێمە دەدەین بەوان)</option>
                                <option value="from_supplier">وەرگرتنی پارە لە فرۆشیار (ئەوان دەدەن بە ئێمە)</option>
                            </select>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/include-components.js"></script>
    <script src="js/bank/script.js"></script>
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
        });
    </script>
    
</body>
</html> 