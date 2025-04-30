<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێستی پیشاندانی کاڵاکان</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">تێستی پیشاندانی کاڵاکان</h2>
        
        <div class="card">
            <div class="card-header">
                <h5>تێستی کاڵاکانی پسووڵە</h5>
            </div>
            <div class="card-body">
                <p>فۆرمی تێست بۆ هێنانی کاڵاکانی پسووڵە</p>
                
                <div class="form-group mb-3">
                    <label for="invoiceNumber">ژمارەی پسووڵە:</label>
                    <input type="text" id="invoiceNumber" class="form-control" placeholder="ژمارەی پسووڵە بنووسە">
                </div>
                
                <button id="testButton" class="btn btn-primary">هێنانی کاڵاکان</button>
                
                <hr>
                
                <div id="resultContainer" class="mt-4">
                    <h6>ئەنجامی تێست:</h6>
                    <pre id="testResult" class="bg-light p-3 rounded">ئەنجام لێرە پیشان دەدرێت...</pre>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>تێستی ڕاستەوخۆ</h5>
            </div>
            <div class="card-body">
                <button id="directTestButton" class="btn btn-info">تێستی ڕاستەوخۆ</button>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Test button click
            $('#testButton').on('click', function() {
                const invoiceNumber = $('#invoiceNumber').val();
                
                if (!invoiceNumber) {
                    alert('تکایە ژمارەی پسووڵە بنووسە!');
                    return;
                }
                
                // Show loading status
                $('#testResult').text('داواکاری دەنێردرێت...');
                
                // Send AJAX request
                $.ajax({
                    url: '../../includes/get_invoice_items.php',
                    type: 'POST',
                    data: { invoice_number: invoiceNumber },
                    dataType: 'json',
                    success: function(response) {
                        $('#testResult').text(JSON.stringify(response, null, 2));
                        
                        if (response.status === 'success') {
                            // Create table with items
                            displayItems(response.items, invoiceNumber);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#testResult').text('خەتا: ' + error + '\n\nوەڵام: ' + xhr.responseText);
                    }
                });
            });
            
            // Direct test button
            $('#directTestButton').on('click', function() {
                // Create sample data for testing
                const sampleData = {
                    status: 'success',
                    items: [
                        {
                            product_name: 'کاڵای تێست ١',
                            quantity: 5,
                            unit_type: 'piece',
                            unit_price: 10000,
                            total_price: 50000
                        },
                        {
                            product_name: 'کاڵای تێست ٢',
                            quantity: 2,
                            unit_type: 'box',
                            unit_price: 25000,
                            total_price: 50000
                        }
                    ]
                };
                
                displayItems(sampleData.items, 'TEST123');
            });
            
            // Function to display items in a modal
            function displayItems(items, invoiceNumber) {
                // Create table with items
                let itemsHtml = `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>ناوی کاڵا</th>
                                    <th>بڕ</th>
                                    <th>یەکە</th>
                                    <th>نرخی تاک</th>
                                    <th>کۆی گشتی</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                if (items.length === 0) {
                    itemsHtml += `<tr><td colspan="6" class="text-center">هیچ کاڵایەک نەدۆزرایەوە</td></tr>`;
                } else {
                    items.forEach((item, index) => {
                        let unitName = '-';
                        switch (item.unit_type) {
                            case 'piece': unitName = 'دانە'; break;
                            case 'box': unitName = 'کارتۆن'; break;
                            case 'set': unitName = 'سێت'; break;
                            default: unitName = item.unit_type || '-';
                        }
                        
                        itemsHtml += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>${unitName}</td>
                                <td>${Number(item.unit_price).toLocaleString()} د.ع</td>
                                <td>${Number(item.total_price).toLocaleString()} د.ع</td>
                            </tr>`;
                    });
                }
                
                itemsHtml += `</tbody></table></div>`;
                
                // Show modal with items
                Swal.fire({
                    title: `ناوەرۆکی پسووڵەی <strong dir="ltr">#${invoiceNumber}</strong>`,
                    html: itemsHtml,
                    width: '80%',
                    confirmButtonText: 'داخستن'
                });
            }
        });
    </script>
</body>
</html> 