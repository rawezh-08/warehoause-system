<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

// Get all sales with their items and return status
$stmt = $conn->prepare("
    SELECT 
        s.id as sale_id,
        s.invoice_number,
        s.sale_date,
        s.total_amount,
        s.paid_amount,
        s.remaining_amount,
        c.name as customer_name,
        c.phone as customer_phone,
        GROUP_CONCAT(
            CONCAT(
                p.name, ' (', si.quantity, ' ', si.unit_type, ')',
                ' - گەڕاوە: ', COALESCE(si.returned_quantity, 0)
            ) SEPARATOR ' | '
        ) as items_details
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN products p ON si.product_id = p.id
    GROUP BY s.id
    ORDER BY s.sale_date DESC
");

$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیستی پسووڵەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #2c3e50;
        }
        .navbar-brand {
            color: #ecf0f1 !important;
            font-weight: bold;
        }
        .nav-link {
            color: #ecf0f1 !important;
        }
        .nav-link:hover {
            color: #3498db !important;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #2c3e50;
            color: #ecf0f1;
            border-radius: 10px 10px 0 0 !important;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .btn-danger {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        .btn-success {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
        }
        .badge-success {
            background-color: #2ecc71;
        }
        .badge-warning {
            background-color: #f1c40f;
        }
        .badge-danger {
            background-color: #e74c3c;
        }
        .badge-info {
            background-color: #3498db;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            padding-right: 40px;
            border-radius: 20px;
        }
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">سیستەمی کۆگا</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">داشبۆرد</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">کاڵاکان</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sales.php">فرۆشتن</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="purchases.php">کڕین</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customers.php">کڕیارەکان</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="suppliers.php">دابینکارەکان</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inventory.php">کۆگا</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">ڕاپۆرتەکان</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../../logout.php">چوونەدەرەوە</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">لیستی پسووڵەکان</h5>
                <div class="search-box">
                    <input type="text" class="form-control" id="searchInput" placeholder="گەڕان...">
                    <i class='bx bx-search'></i>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="salesTable">
                        <thead>
                            <tr>
                                <th>ژمارەی پسووڵە</th>
                                <th>بەروار</th>
                                <th>کڕیار</th>
                                <th>کاڵاکان</th>
                                <th>کۆی گشتی</th>
                                <th>پارەی دراو</th>
                                <th>بڕی ماوە</th>
                                <th>کردارەکان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($sale['sale_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($sale['customer_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($sale['items_details']); ?></td>
                                <td><?php echo number_format($sale['total_amount'], 2); ?> دینار</td>
                                <td><?php echo number_format($sale['paid_amount'], 2); ?> دینار</td>
                                <td><?php echo number_format($sale['remaining_amount'], 2); ?> دینار</td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-info view-sale" data-id="<?php echo $sale['sale_id']; ?>">
                                        <i class='bx bx-show'></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning return-sale" 
                                            data-id="<?php echo $sale['sale_id']; ?>"
                                            data-invoice="<?php echo $sale['invoice_number']; ?>">
                                        <i class='bx bx-undo'></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Search functionality
            $("#searchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#salesTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // View sale details
            $(document).on('click', '.view-sale', function() {
                const saleId = $(this).data('id');
                // Implement view sale details functionality
                Swal.fire({
                    title: 'زانیارییەکانی پسووڵە',
                    text: 'ئەم بەشە لە ژێر پەرەپێدانە',
                    icon: 'info',
                    confirmButtonText: 'باشە'
                });
            });

            // Return sale button handler
            $(document).on('click', '.return-sale', function() {
                const saleId = $(this).data('id');
                const invoiceNumber = $(this).data('invoice');
                
                // Show loading
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'زانیارییەکان وەردەگیرێن',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Get sale items
                $.ajax({
                    url: '../../ajax/get_sale_items.php',
                    type: 'POST',
                    data: {
                        sale_id: saleId
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            // Check if sale has payments
                            if (response.has_payments) {
                                Swal.fire({
                                    title: 'هەڵە!',
                                    text: 'ناتوانرێت ئەم پسووڵە بگەڕێتەوە چونکە پارەدانەوەی لەسەر تۆمار کراوە',
                                    icon: 'error',
                                    confirmButtonText: 'باشە'
                                });
                                return;
                            }
                            
                            // Create return form
                            let itemsHtml = '<form id="returnSaleForm">';
                            itemsHtml += '<input type="hidden" name="sale_id" value="' + saleId + '">';
                            itemsHtml += '<input type="hidden" name="receipt_type" value="selling">';
                            itemsHtml += '<div class="table-responsive"><table class="table table-bordered">';
                            itemsHtml += '<thead><tr><th>ناوی کاڵا</th><th>بڕی کڕین</th><th>بڕی گەڕانەوە</th></tr></thead>';
                            itemsHtml += '<tbody>';
                            
                            response.items.forEach(item => {
                                // Calculate max returnable amount (total quantity - already returned quantity)
                                const maxReturnable = item.quantity - (item.returned_quantity || 0);
                                
                                if (maxReturnable > 0) {
                                    itemsHtml += `<tr>
                                        <td>${item.product_name}</td>
                                        <td>${item.quantity} (${item.returned_quantity || 0} گەڕاوە پێشتر)</td>
                                        <td>
                                            <input type="number" class="form-control return-quantity" 
                                                name="return_quantities[${item.id}]" 
                                                min="0" max="${maxReturnable}" value="0">
                                        </td>
                                    </tr>`;
                                }
                            });
                            
                            itemsHtml += '</tbody></table></div>';
                            itemsHtml += '<div class="mb-3">';
                            itemsHtml += '<label for="returnReason" class="form-label">هۆکاری گەڕانەوە</label>';
                            itemsHtml += '<select class="form-select" name="reason" id="returnReason">';
                            itemsHtml += '<option value="damaged">شکاو/خراپ</option>';
                            itemsHtml += '<option value="wrong_product">کاڵای هەڵە</option>';
                            itemsHtml += '<option value="other">هۆکاری تر</option>';
                            itemsHtml += '</select>';
                            itemsHtml += '</div>';
                            itemsHtml += '<div class="mb-3">';
                            itemsHtml += '<label for="returnNotes" class="form-label">تێبینی</label>';
                            itemsHtml += '<textarea class="form-control" id="returnNotes" name="notes" rows="3"></textarea>';
                            itemsHtml += '</div>';
                            itemsHtml += '</form>';
                            
                            Swal.fire({
                                title: `گەڕاندنەوەی کاڵا - پسووڵە ${invoiceNumber}`,
                                html: itemsHtml,
                                showCancelButton: true,
                                confirmButtonText: 'گەڕاندنەوە',
                                cancelButtonText: 'هەڵوەشاندنەوە',
                                showLoaderOnConfirm: true,
                                preConfirm: () => {
                                    const formData = new FormData(document.getElementById('returnSaleForm'));
                                    return $.ajax({
                                        url: '../../ajax/return_sale.php',
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        dataType: 'json'
                                    });
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    const response = result.value;
                                    if (response.success) {
                                        // Create summary HTML
                                        let summaryHtml = '<div class="return-summary mt-3">';
                                        summaryHtml += '<h5 class="mb-3">کورتەی گەڕانەوە</h5>';
                                        
                                        // Original total
                                        summaryHtml += `<div class="mb-2">
                                            <strong>کۆی گشتی پسووڵە:</strong> 
                                            ${response.summary.original_total.toLocaleString()} دینار
                                        </div>`;
                                        
                                        // Return count
                                        summaryHtml += `<div class="mb-2">
                                            <strong>ژمارەی گەڕانەوەکان:</strong> 
                                            ${response.summary.return_count}
                                        </div>`;
                                        
                                        // Returned amount
                                        summaryHtml += `<div class="mb-2">
                                            <strong>کۆی گشتی گەڕاوە:</strong> 
                                            ${response.summary.returned_amount.toLocaleString()} دینار
                                        </div>`;
                                        
                                        // Remaining items
                                        summaryHtml += '<div class="mb-2"><strong>کاڵاکانی گەڕاوە:</strong></div>';
                                        summaryHtml += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                                        summaryHtml += '<thead><tr><th>ناوی کاڵا</th><th>بڕی گەڕانەوە</th><th>نرخی تاک</th><th>نرخی گشتی</th></tr></thead>';
                                        summaryHtml += '<tbody>';
                                        
                                        response.summary.returned_items.forEach(item => {
                                            summaryHtml += `<tr>
                                                <td>${item.product_name}</td>
                                                <td>${item.returned_quantity}</td>
                                                <td>${item.unit_price.toLocaleString()} دینار</td>
                                                <td>${item.total_price.toLocaleString()} دینار</td>
                                            </tr>`;
                                        });
                                        
                                        summaryHtml += '</tbody></table></div>';
                                        summaryHtml += '</div>';
                                        
                                        Swal.fire({
                                            title: 'سەرکەوتوو بوو!',
                                            html: response.message + summaryHtml,
                                            icon: 'success',
                                            confirmButtonText: 'باشە'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'هەڵە!',
                                            text: response.message,
                                            icon: 'error',
                                            confirmButtonText: 'باشە'
                                        });
                                    }
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message || 'هەڵەیەک ڕوویدا لە وەرگرتنی کاڵاکان',
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        console.error('Ajax error:', error);
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەر',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 