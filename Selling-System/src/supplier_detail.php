<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وردەکاری فرۆشیار</title>
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
        /* Add improved button styles */
        .btn-outline-secondary {
            color: #495057;
            border-color: #6c757d;
            font-weight: 500;
        }
        
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: #fff;
        }
        
        .supplier-info-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .supplier-info-card .card-header {
            background-color: #f0f2f5;
            border-bottom: 1px solid rgba(0,0,0,.125);
            padding: 15px 20px;
            color: #212529;
            font-weight: 600;
        }
        
        .supplier-info-card .card-body {
            padding: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 150px;
            color: #495057;
        }
        
        .info-value {
            flex: 1;
            color: #212529;
            font-weight: 500;
        }
        
        .balance-card {
            background-color: #ffffff;
            text-align: center;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #dee2e6;
        }
        
        .balance-card h5 {
            color: #343a40;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .positive-balance {
            background-color: #effaf3;
            border: 2px solid #28a745;
            color: #155724;
        }
        
        .negative-balance {
            background-color: #fcf0f2;
            border: 2px solid #dc3545;
            color: #721c24;
        }
        
        .balance-amount {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 15px 0;
            text-shadow: 0px 1px 1px rgba(255,255,255,0.8);
            letter-spacing: 0.5px;
        }
        
        /* Add classes for currency display */
        .currency-positive {
            color: #0d6832;
            background-color: rgba(40, 167, 69, 0.1);
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
        }
        
        .currency-negative {
            color: #a71d2a;
            background-color: rgba(220, 53, 69, 0.1);
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
        }
        
        .currency-neutral {
            color: #343a40;
            background-color: rgba(108, 117, 125, 0.1);
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
        }
        
        .transaction-history {
            margin-top: 30px;
        }
        
        .transaction-card {
            
            border-left: 4px solid;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .transaction-positive {
            border-left-color: #218838;
            background-color: #f8fffa;
        }
        
        .transaction-negative {
            border-left-color: #c82333;
            background-color: #fff9f9;
        }
        
        .card-header {
            background-color: #f0f2f5;
            font-weight: bold;
        }
        
        .text-muted {
            color: #495057 !important;
        }
        
        @media (max-width: 768px) {
            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-label {
                margin-bottom: 5px;
            }
            
            .balance-amount {
                font-size: 1.5rem;
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
                    <div class="d-flex align-items-center">
                        <a href="bank.php" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <h4 class="mb-0">وردەکاری فرۆشیار</h4>
                    </div>
                    <div class="d-flex">
                        <button class="btn btn-outline-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> نوێکردنەوە
                        </button>
                    </div>
                </div>
            </div>

            <!-- Supplier Information -->
            <div class="row">
                <div class="col-md-4">
                    <div class="supplier-info-card">
                        <div class="card-header">
                            <h5 class="mb-0">زانیاری فرۆشیار</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-label">ناو:</div>
                                <div class="info-value" id="supplierName"></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">ژمارەی مۆبایل ١:</div>
                                <div class="info-value" id="supplierPhone1"></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">ژمارەی مۆبایل ٢:</div>
                                <div class="info-value" id="supplierPhone2"></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">تێبینی:</div>
                                <div class="info-value" id="supplierNotes"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="balance-card" id="balanceCard">
                        <h5>باڵانسی ئێستا</h5>
                        <div class="balance-amount" id="currentBalance"></div>
                        <div id="balanceStatus"></div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">مێژووی پارەدانەکان</h5>
                        </div>
                        <div class="card-body">
                            <div class="transaction-history" id="transactionHistory">
                                <!-- Transactions will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/include-components.js"></script>
    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const supplierId = urlParams.get('id');
            
            if (!supplierId) {
                window.location.href = 'bank.php';
                return;
            }
            
            // Load supplier details
            loadSupplierDetails();
            
            // Refresh button
            $('#refreshBtn').on('click', function() {
                loadSupplierDetails();
            });
            
            // Add payment button
            $('.add-payment-btn').on('click', function() {
                const supplierId = $(this).data('id');
                const supplierName = $(this).data('name');
                const currentBalance = $(this).data('balance');
                
                $('#supplierId').val(supplierId);
                $('#supplierName').val(supplierName);
                $('#currentBalance').val(formatCurrency(currentBalance));
                
                if (currentBalance < 0) {
                    $('#paymentDirection').val('to_supplier');
                } else {
                    $('#paymentDirection').val('from_supplier');
                }
                
                $('#addPaymentModal').modal('show');
            });
            
            // Save payment
            $('#savePaymentBtn').on('click', function() {
                const paymentAmount = $('#paymentAmount').val();
                const paymentDirection = $('#paymentDirection').val();
                const paymentNote = $('#paymentNote').val();
                
                if (!paymentAmount || paymentAmount <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'تکایە بڕی پارەدان داخڵ بکە',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }
                
                // Simulate successful payment
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو',
                    text: 'پارەدان بە سەرکەوتوویی تۆمارکرا',
                    confirmButtonText: 'باشە'
                }).then(() => {
                    $('#addPaymentModal').modal('hide');
                    loadSupplierDetails();
                });
            });
            
            function loadSupplierDetails() {
                // Sample supplier data based on ID
                let supplier = {};
                let transactions = [];
                
                // Sample suppliers data
                const sampleSuppliers = [
                    {
                        id: 1,
                        name: "کۆمپانیای مەحمود",
                        phone1: "0750 123 4567",
                        phone2: "0770 123 4567",
                        debt_on_myself: -500000, // We owe them
                        notes: "کۆمپانیای گەورەی هێنانی کەلوپەل"
                    },
                    {
                        id: 2,
                        name: "فرۆشگای ئەحمەد",
                        phone1: "0751 234 5678",
                        phone2: "",
                        debt_on_myself: 750000, // They owe us (positive when inverted)
                        notes: "وەکیلی هێنانی کەلوپەلی ناوماڵ"
                    },
                    {
                        id: 3,
                        name: "کۆمپانیای هەولێر",
                        phone1: "0772 345 6789",
                        phone2: "0773 345 6789",
                        debt_on_myself: -1200000, // We owe them
                        notes: ""
                    },
                    {
                        id: 4,
                        name: "بازرگانی مەهدی",
                        phone1: "0773 456 7890",
                        phone2: "",
                        debt_on_myself: 0, // No debt
                        notes: "فرۆشیاری کەلوپەلی کارەبایی"
                    },
                    {
                        id: 5,
                        name: "فرۆشگای فەریق",
                        phone1: "0774 567 8901",
                        phone2: "0775 567 8901",
                        debt_on_myself: 300000, // They owe us
                        notes: "فرۆشیاری کەلوپەلی خواردەمەنی"
                    }
                ];
                
                // Sample transactions data
                const sampleTransactions = {
                    1: [
                        {
                            id: 101,
                            amount: 200000,
                            direction: 'to_supplier',
                            notes: 'پارەدان بۆ کەلوپەلی مانگی ڕابردوو',
                            created_at: '2023-12-15 14:30:45'
                        },
                        {
                            id: 102,
                            amount: 300000,
                            direction: 'to_supplier',
                            notes: 'پارەدان بۆ کەلوپەلی نوێ',
                            created_at: '2024-01-20 10:15:22'
                        }
                    ],
                    2: [
                        {
                            id: 201,
                            amount: 450000,
                            direction: 'from_supplier',
                            notes: 'گەڕاندنەوەی قەرز',
                            created_at: '2023-11-10 09:45:30'
                        },
                        {
                            id: 202,
                            amount: 300000,
                            direction: 'from_supplier',
                            notes: 'پارەدان بۆ کەلوپەلی گەڕاوە',
                            created_at: '2024-02-05 16:20:15'
                        }
                    ],
                    3: [
                        {
                            id: 301,
                            amount: 700000,
                            direction: 'to_supplier',
                            notes: 'پارەدان بۆ کەلوپەلی مانگی ڕابردوو',
                            created_at: '2023-12-01 11:30:00'
                        },
                        {
                            id: 302,
                            amount: 500000,
                            direction: 'to_supplier',
                            notes: 'پارەدان بۆ کەلوپەلی نوێ',
                            created_at: '2024-01-10 13:25:45'
                        }
                    ],
                    4: [],
                    5: [
                        {
                            id: 501,
                            amount: 300000,
                            direction: 'from_supplier',
                            notes: 'گەڕاندنەوەی قەرز',
                            created_at: '2024-01-25 15:10:30'
                        }
                    ]
                };
                
                // Find the supplier based on the ID
                supplier = sampleSuppliers.find(s => s.id == supplierId) || {};
                transactions = sampleTransactions[supplierId] || [];
                
                if (Object.keys(supplier).length > 0) {
                    const balance = parseFloat(supplier.debt_on_myself) * -1;
                    
                    // Update supplier info
                    $('#supplierName').text(supplier.name);
                    $('#supplierPhone1').text(supplier.phone1);
                    $('#supplierPhone2').text(supplier.phone2 || '-');
                    $('#supplierNotes').text(supplier.notes || '-');
                    
                    // Update balance card
                    $('#currentBalance').removeClass('text-success text-danger currency-positive currency-negative currency-neutral');
                    const balanceCard = $('#balanceCard');
                    balanceCard.removeClass('positive-balance negative-balance');
                    
                    const formattedBalance = formatCurrency(balance);
                    
                    if (balance > 0) {
                        balanceCard.addClass('positive-balance');
                        $('#currentBalance').addClass('currency-positive');
                        $('#currentBalance').html(`<span>${formattedBalance}</span>`);
                        $('#balanceStatus').html(`<strong class="text-success" style="font-size: 1.2rem;">ئەوان قەرزداری ئێمەن</strong>`);
                    } else if (balance < 0) {
                        balanceCard.addClass('negative-balance');
                        $('#currentBalance').addClass('currency-negative');
                        $('#currentBalance').html(`<span>${formattedBalance}</span>`);
                        $('#balanceStatus').html(`<strong class="text-danger" style="font-size: 1.2rem;">ئێمە قەرزداری ئەوانین</strong>`);
                    } else {
                        // Neutral balance
                        balanceCard.css({
                            'background-color': '#f8f9fa',
                            'border': '2px solid #6c757d'
                        });
                        $('#currentBalance').addClass('currency-neutral');
                        $('#currentBalance').html(`<span>${formattedBalance}</span>`);
                        $('#balanceStatus').html(`<strong class="text-dark" style="font-size: 1.2rem;">باڵانس یەکسانە</strong>`);
                    }
                    
                    // Update transaction history
                    const historyHtml = transactions.map(transaction => {
                        const amount = parseFloat(transaction.amount);
                        const isPositive = transaction.direction === 'from_supplier';
                        const transactionClass = isPositive ? 'transaction-positive' : 'transaction-negative';
                        const directionText = isPositive ? 'وەرگرتنی پارە' : 'پارەدان';
                        const amountTextColor = isPositive ? 'text-success fw-bold' : 'text-danger fw-bold';
                        
                        return `
                            <div class="card transaction-card ${transactionClass}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1 ${isPositive ? 'text-success' : 'text-danger'} fw-bold">${directionText}</h6>
                                            <p class="mb-0 text-muted">${transaction.created_at}</p>
                                        </div>
                                        <div class="text-end">
                                            <h6 class="mb-1 ${amountTextColor}">${formatCurrency(amount)}</h6>
                                            ${transaction.notes ? `<p class="mb-0 text-muted">${transaction.notes}</p>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    $('#transactionHistory').html(historyHtml || '<p class="text-center text-muted">هیچ پارەدانێک تۆمار نەکراوە</p>');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'فرۆشیار نەدۆزرایەوە',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        window.location.href = 'bank.php';
                    });
                }
            }
            
            function formatCurrency(amount) {
                return parseFloat(amount).toLocaleString('en-US') + ' دینار';
            }
        });
    </script>
</body>
</html> 