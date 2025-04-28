<?php
// Include database connection
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get a test sale
$testSaleQuery = "SELECT s.*, c.name as customer_name 
                  FROM sales s 
                  JOIN customers c ON s.customer_id = c.id 
                  ORDER BY s.id DESC LIMIT 1";
$testSaleStmt = $conn->prepare($testSaleQuery);
$testSaleStmt->execute();
$testSale = $testSaleStmt->fetch(PDO::FETCH_ASSOC);

// Get sale items
$saleItemsQuery = "SELECT si.*, p.name as product_name, p.code as product_code
                   FROM sale_items si
                   JOIN products p ON si.product_id = p.id
                   WHERE si.sale_id = :sale_id";
$saleItemsStmt = $conn->prepare($saleItemsQuery);
$saleItemsStmt->bindParam(':sale_id', $testSale['id']);
$saleItemsStmt->execute();
$saleItems = $saleItemsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێستی گەڕاندنەوەی کاڵا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">تێستی گەڕاندنەوەی کاڵا</h2>
        
        <!-- Test Sale Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>زانیاری پسووڵە</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>ژمارەی پسووڵە:</strong> <?php echo $testSale['invoice_number']; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>کڕیار:</strong> <?php echo $testSale['customer_name']; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>جۆری پارەدان:</strong> <?php echo $testSale['payment_type'] == 'cash' ? 'نەقد' : 'قەرز'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Form -->
        <div class="card">
            <div class="card-header">
                <h5>فۆرمی گەڕاندنەوە</h5>
            </div>
            <div class="card-body">
                <form id="testReturnForm">
                    <input type="hidden" name="sale_id" value="<?php echo $testSale['id']; ?>">
                    <input type="hidden" name="invoice_number" value="<?php echo $testSale['invoice_number']; ?>">
                    
                    <div class="mb-3">
                        <label for="return_date" class="form-label">بەرواری گەڕانەوە</label>
                        <input type="date" class="form-control" id="return_date" name="return_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="return_reason" class="form-label">هۆکاری گەڕانەوە</label>
                        <select class="form-select" id="return_reason" name="reason" required>
                            <option value="damaged">کاڵا زیانی پێگەیشتووە</option>
                            <option value="wrong_product">کاڵای هەڵە</option>
                            <option value="customer_request">داواکاری کڕیار</option>
                            <option value="other">هۆکاری تر</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="return_notes" class="form-label">تێبینی</label>
                        <textarea class="form-control" id="return_notes" name="notes" rows="2"></textarea>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>ناوی کاڵا</th>
                                    <th>کۆد</th>
                                    <th>بڕی فرۆشراو</th>
                                    <th>یەکە</th>
                                    <th>نرخی تاک</th>
                                    <th>بڕی گەڕاوە</th>
                                    <th>نرخی گشتی</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($saleItems as $item): ?>
                                <tr>
                                    <td>
                                        <?php echo $item['product_name']; ?>
                                        <input type="hidden" name="product_ids[]" value="<?php echo $item['product_id']; ?>">
                                        <input type="hidden" name="sale_item_ids[]" value="<?php echo $item['id']; ?>">
                                    </td>
                                    <td><?php echo $item['product_code']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>
                                        <?php
                                        switch ($item['unit_type']) {
                                            case 'piece': echo 'دانە'; break;
                                            case 'box': echo 'کارتۆن'; break;
                                            case 'set': echo 'سێت'; break;
                                        }
                                        ?>
                                        <input type="hidden" name="unit_types[]" value="<?php echo $item['unit_type']; ?>">
                                    </td>
                                    <td>
                                        <?php echo number_format($item['unit_price']); ?>
                                        <input type="hidden" name="unit_prices[]" value="<?php echo $item['unit_price']; ?>">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control return-quantity" 
                                               name="return_quantities[]" min="0" max="<?php echo $item['quantity']; ?>" 
                                               value="0" data-price="<?php echo $item['unit_price']; ?>" required>
                                    </td>
                                    <td class="item-total-price">0 دینار</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-start"><strong>کۆی گشتی:</strong></td>
                                    <td><strong id="return_total_price">0</strong> دینار</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-primary" id="testReturnBtn">
                            <i class="fas fa-save me-1"></i> تاقیکردنەوە
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Debug Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>زانیاری دیباگ</h5>
            </div>
            <div class="card-body">
                <pre id="debugInfo" class="bg-light p-3" style="max-height: 300px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            // Update totals when return quantity changes
            $('.return-quantity').on('input', function() {
                updateReturnTotals();
            });

            // Function to update total prices
            function updateReturnTotals() {
                let totalReturnPrice = 0;
                
                $('.return-quantity').each(function() {
                    const quantity = parseInt($(this).val()) || 0;
                    const unitPrice = parseFloat($(this).data('price'));
                    const itemTotal = quantity * unitPrice;
                    
                    $(this).closest('tr').find('.item-total-price').text(itemTotal.toLocaleString() + ' دینار');
                    totalReturnPrice += itemTotal;
                });
                
                $('#return_total_price').text(totalReturnPrice.toLocaleString());
            }

            // Test Return Button
            $('#testReturnBtn').on('click', function() {
                // Check if at least one item is selected for return
                let hasReturnItems = false;
                $('.return-quantity').each(function() {
                    if (parseInt($(this).val()) > 0) {
                        hasReturnItems = true;
                        return false; // break the loop
                    }
                });
                
                if (!hasReturnItems) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ئاگاداری',
                        text: 'تکایە لانی کەم یەک کاڵا دیاری بکە بۆ گەڕاندنەوە',
                        confirmButtonText: 'باشە'
                    });
                    return;
                }

                // Get form data
                const formData = new FormData(document.getElementById('testReturnForm'));
                
                // Display form data in debug section
                let debugInfo = 'Form Data:\n';
                for (let pair of formData.entries()) {
                    debugInfo += pair[0] + ': ' + pair[1] + '\n';
                }
                $('#debugInfo').text(debugInfo);

                // Send AJAX request
                $.ajax({
                    url: '../../ajax/sales/save_product_return.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        // Add response to debug info
                        $('#debugInfo').append('\nResponse:\n' + JSON.stringify(response, null, 2));
                        
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو',
                                text: 'گەڕاندنەوەی کاڵا بە سەرکەوتوویی ئەنجام درا',
                                confirmButtonText: 'باشە'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Add error details to debug info
                        $('#debugInfo').append('\nError:\nStatus: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText);
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەرەوە',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 