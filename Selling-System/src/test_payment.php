<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێستی سیستەمی پارەدان</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">تێستی سیستەمی پارەدان</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">تێستی پەیوەندی</h5>
                    </div>
                    <div class="card-body">
                        <button id="testConnection" class="btn btn-primary">تێستی پەیوەندی</button>
                        <div id="connectionResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">تێستی پارەدان</h5>
                    </div>
                    <div class="card-body">
                        <form id="testPaymentForm">
                            <div class="mb-3">
                                <label for="supplier" class="form-label">فرۆشیار</label>
                                <select id="supplier" class="form-select" required></select>
                            </div>
                            <div class="mb-3">
                                <label for="amount" class="form-label">بڕی پارە</label>
                                <input type="number" id="amount" class="form-control" value="1000" required>
                            </div>
                            <div class="mb-3">
                                <label for="paymentType" class="form-label">جۆری پارەدان</label>
                                <select id="paymentType" class="form-select" required>
                                    <option value="business_pay_supplier">ئێمە دەدەین بە فرۆشیار</option>
                                    <option value="handle_supplier_payment">فرۆشیار دەدات بە ئێمە</option>
                                    <option value="adjust_balance">ڕێکخستنی باڵانس</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">تێبینی</label>
                                <input type="text" id="notes" class="form-control" value="تێست">
                            </div>
                            <button type="submit" class="btn btn-success">تێستی پارەدان</button>
                        </form>
                        <div id="paymentResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">لۆگەکان</h5>
                    </div>
                    <div class="card-body">
                        <pre id="logs" class="bg-dark text-light p-3" style="max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load suppliers
            $.ajax({
                url: 'api/get_suppliers_with_balance.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    // Handle both response formats
                    if (response.status === 'success' || response.success === true) {
                        const $supplierSelect = $('#supplier');
                        $supplierSelect.empty();
                        
                        response.suppliers.forEach(function(supplier) {
                            $supplierSelect.append(`<option value="${supplier.id}">${supplier.name}</option>`);
                        });
                    } else {
                        logMessage('Error loading suppliers: ' + response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    logMessage('Error loading suppliers: ' + error, 'error');
                }
            });
            
            // Test connection
            $('#testConnection').on('click', function() {
                const $result = $('#connectionResult');
                $result.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
                
                $.ajax({
                    url: 'api/test_db_connection.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let html = `<div class="alert alert-success">پەیوەندی سەرکەوتوو بوو</div>`;
                            html += `<div class="mt-2"><strong>داتابەیس:</strong> ${response.database.name}</div>`;
                            html += `<div><strong>سێرڤەر:</strong> ${response.server.version}</div>`;
                            
                            if (response.procedures.status === 'OK') {
                                html += `<div class="alert alert-success mt-2">هەموو پڕۆسیجەرەکان بەردەستن</div>`;
                            } else {
                                html += `<div class="alert alert-danger mt-2">ئەم پڕۆسیجەرانە بەردەست نین: ${response.procedures.missing.join(', ')}</div>`;
                            }
                            
                            $result.html(html);
                            logMessage('Connection test successful', 'success');
                        } else {
                            $result.html(`<div class="alert alert-danger">${response.message}<br>${response.error}</div>`);
                            logMessage('Connection test failed: ' + response.error, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        $result.html(`<div class="alert alert-danger">هەڵە لە پەیوەندی کردن: ${error}</div>`);
                        logMessage('Connection test error: ' + error, 'error');
                    }
                });
            });
            
            // Test payment
            $('#testPaymentForm').on('submit', function(e) {
                e.preventDefault();
                
                const supplierId = $('#supplier').val();
                const amount = $('#amount').val();
                const paymentType = $('#paymentType').val();
                const notes = $('#notes').val();
                const $result = $('#paymentResult');
                
                $result.html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
                
                let apiEndpoint;
                if (paymentType === 'business_pay_supplier') {
                    apiEndpoint = 'api/business_pay_supplier.php';
                } else if (paymentType === 'handle_supplier_payment') {
                    apiEndpoint = 'api/handle_supplier_payment.php';
                } else if (paymentType === 'adjust_balance') {
                    apiEndpoint = 'api/adjust_supplier_balance.php';
                }
                
                logMessage(`Sending request to ${apiEndpoint}`, 'info');
                logMessage(`Data: supplier_id=${supplierId}, amount=${amount}, notes=${notes}`, 'info');
                
                $.ajax({
                    url: apiEndpoint,
                    type: 'POST',
                    data: {
                        supplier_id: supplierId,
                        amount: amount,
                        notes: notes
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html(`<div class="alert alert-success">${response.message}</div>`);
                            logMessage('Payment successful: ' + response.message, 'success');
                        } else {
                            $result.html(`<div class="alert alert-danger">${response.message}</div>`);
                            logMessage('Payment failed: ' + response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMsg = 'هەڵە لە پەیوەندی کردن: ' + error;
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMsg = response.message;
                            }
                        } catch(e) {}
                        
                        $result.html(`<div class="alert alert-danger">${errorMsg}</div>`);
                        logMessage('Payment error: ' + error, 'error');
                        logMessage('Response text: ' + xhr.responseText, 'error');
                    }
                });
            });
            
            // Log message function
            function logMessage(message, type) {
                const timestamp = new Date().toLocaleTimeString();
                const $logs = $('#logs');
                let color = '';
                
                switch(type) {
                    case 'error':
                        color = 'text-danger';
                        break;
                    case 'success':
                        color = 'text-success';
                        break;
                    case 'info':
                        color = 'text-info';
                        break;
                    default:
                        color = 'text-light';
                }
                
                $logs.prepend(`<div class="${color}">[${timestamp}] ${message}</div>`);
            }
        });
    </script>
</body>
</html> 