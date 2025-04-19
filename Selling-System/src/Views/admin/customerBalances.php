<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>باڵانسی ئێمە لەگەڵ کڕیارەکان</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
        .customer-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
            padding: 0.75rem;
        }
        
        .customer-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        
        .customer-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .customer-card .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            background: #f9fafb;
            border-radius: 10px 10px 0 0;
        }
        
        .customer-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .customer-card .card-title i {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .customer-card .contact-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .customer-card .card-body {
            padding: 1.25rem;
        }
        
        .customer-card .balance-section {
            text-align: center;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        
        .customer-card .balance-amount {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .customer-card .balance-amount i {
            font-size: 1.25rem;
        }
        
        .customer-card .balance-positive {
            color: #059669;
            background: #ecfdf5;
        }
        
        .customer-card .balance-negative {
            color: #dc2626;
            background: #fef2f2;
        }
        
        .customer-card .balance-zero {
            color: #6b7280;
            background: #f3f4f6;
        }
        
        .customer-card .balance-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .customer-card .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        
        .customer-card .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .customer-card .btn i {
            font-size: 0.875rem;
        }
        
        .customer-card .btn-primary {
            background: #3b82f6;
            border: none;
            color: white;
        }
        
        .customer-card .btn-primary:hover {
            background: #2563eb;
        }
        
        .customer-card .btn-secondary {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #4b5563;
        }
        
        .customer-card .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        @media (max-width: 768px) {
            .customer-cards-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .customer-card .card-actions {
                grid-template-columns: 1fr;
            }
            
            .customer-card .card-header,
            .customer-card .card-body {
                padding: 1rem;
            }
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
                    <h4 class="mb-0">باڵانسی ئێمە لەگەڵ کڕیارەکان</h4>
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
                                <p class="text-muted small-text mb-2">(جیاوازی نێوان قەرزی ئێمە و کڕیارەکان)</p>
                                <h2 class="balance-amount" id="totalBalance">0 دینار</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card total-balances positive-card">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-circle-down balance-icon"></i>
                                <h5 class="card-title">پارەی لای ئێمە</h5>
                                <p class="text-muted small-text mb-2">(ئەو پارەیەی کە کڕیارەکان دەبێت بیدەن بە ئێمە)</p>
                                <h2 class="balance-amount" id="totalTheyOweUs">0 دینار</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card total-balances negative-card">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-circle-up balance-icon"></i>
                                <h5 class="card-title">پارەی لای کڕیارەکان</h5>
                                <p class="text-muted small-text mb-2">(ئەو پارەیەی کە ئێمە دەبێت بیدەین بە کڕیارەکان)</p>
                                <h2 class="balance-amount" id="totalWeOweThem">0 دینار</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card total-balances" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                            <div class="card-body text-center">
                                <i class="fas fa-wallet balance-icon"></i>
                                <h5 class="card-title">پارەی پێشەکی</h5>
                                <p class="text-muted small-text mb-2">(ئەو پارەیەی کە کڕیارەکان بە پێشەکی داویانە)</p>
                                <h2 class="balance-amount" id="totalAdvancePayments">0 دینار</h2>
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
                                    <input type="text" class="form-control" id="searchInput" placeholder="گەڕان بە ناوی کڕیار، ژمارە تەلەفۆن...">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select mb-2" id="customerFilter">
                                    <option value="">هەموو کڕیارەکان</option>
                                    <!-- کڕیارەکان بە دینامیکی زیاد دەکرێن -->
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
                                <button class="btn btn-primary" id="addNewBalanceBtn" data-bs-toggle="modal" data-bs-target="#addBalanceModal">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card View for customer balances -->
                <div id="customerCardsView" class="mb-4">
                    <div class="customer-cards-container" id="customerCardsContainer">
                        <!-- Example of enhanced customer card structure -->
                        <div class="customer-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="fas fa-user"></i>
                                    <span>ناوی کڕیار</span>
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
                        <input type="hidden" id="customerId">
                        <div class="mb-3">
                            <label for="customerName" class="form-label">ناوی کڕیار</label>
                            <input type="text" class="form-control" id="customerName" readonly>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currentBalance" class="form-label">قەرزی ئێستا</label>
                                    <input type="text" class="form-control" id="currentBalance" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currentAdvancePayment" class="form-label">پارەی پێشەکی</label>
                                    <input type="text" class="form-control" id="currentAdvancePayment" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">بڕی پارەدان</label>
                            <input type="number" class="form-control" id="paymentAmount" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="paymentDirection" class="form-label">جۆری پارەدان</label>
                            <select class="form-select" id="paymentDirection" required>
                                <!-- Options will be populated dynamically -->
                            </select>
                            <div class="form-text text-muted mt-2">
                                <ul class="mb-0 small">
                                    <li>وەرگرتنی پارە لە کڕیار: کاتێک کڕیار پارە دەدات (بۆ قەرز یان پێشەکی)</li>
                                    <li>پارەدان بە کڕیار: کاتێک ئێمە پارە دەدەین (بۆ قەرزی خۆمان یان گەڕاندنەوەی پێشەکی)</li>
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
                    <form id="addCustomerBalanceForm">
                        <div class="mb-3">
                            <label for="customerSelect" class="form-label">کڕیار</label>
                            <select class="form-select" id="customerSelect" required>
                                <option value="">هەڵبژاردنی کڕیار</option>
                                <!-- کڕیارەکان بە دینامیکی زیاد دەکرێن -->
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
                                <option value="they_owe">ئەوان قەرزارن (ئەوان دەبێت بیدەنەوە)</option>
                                <option value="we_owe">ئێمە قەرزارین (ئێمە دەبێت بیدەینەوە)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="customerNote" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="customerNote" rows="2"></textarea>
                        </div>
                    </form>
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
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../js/include-components.js"></script>
    <script src="../../js/customerBalances/script.js"></script>
    <script>
        $(document).ready(function() {
            // Make the customer cards clickable to view customer details
            $(document).on('click', '.customer-card', function(e) {
                // Check if the click was on a button or within a button
                if (!$(e.target).closest('button').length) {
                    const customerId = $(this).data('id');
                    window.location.href = `customer_detail.php?id=${customerId}`;
                }
            });
            
            // Make view-history button open customer details page
            $(document).on('click', '.view-history-btn', function() {
                const customerId = $(this).data('id');
                window.location.href = `customer_detail.php?id=${customerId}`;
            });

            // Initialize the modal
            const addBalanceModal = new bootstrap.Modal(document.getElementById('addBalanceModal'));
            
            // Initialize select2 for enhanced dropdowns
            $('#customerSelect').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'گەڕان و هەڵبژاردن...',
                allowClear: true
            });

            // Load customers into dropdown
            function loadCustomers() {
                // Example AJAX call - replace with your actual API endpoint
                $.get('/api/customers', function(customers) {
                    const select = $('#customerSelect');
                    select.empty().append('<option value="">هەڵبژاردنی کڕیار</option>');
                    customers.forEach(customer => {
                        select.append(`<option value="${customer.id}">${customer.name}</option>`);
                    });
                });
            }

            // Load data when modal opens
            $('#addBalanceModal').on('show.bs.modal', function () {
                loadCustomers();
            });

            // Handle form submission
            $('#saveNewBalance').click(function() {
                const formData = {
                    customerId: $('#customerSelect').val(),
                    initialBalance: $('#initialBalance').val(),
                    balanceType: $('#balanceType').val(),
                    note: $('#customerNote').val()
                };
                
                // Validate required fields
                const form = $('#addCustomerBalanceForm')[0];
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
                    $('#customerSelect').val('').trigger('change');
                });
            });
        });
    </script>
</body>
</html> 