<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get purchases data
$purchaseController = new PurchaseReceiptsController($conn);
$purchases = $purchaseController->getPurchasesData();
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیستی پسووڵەکانی کڕین</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">لیستی پسووڵەکانی کڕین</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="addPurchase.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> پسووڵەی نوێ
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="startDate" placeholder="بەرواری دەستپێک">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="endDate" placeholder="بەرواری کۆتایی">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="supplierName" placeholder="ناوی دابینکەر">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="applyFilters()">
                            <i class="bi bi-search"></i> گەڕان
                        </button>
                    </div>
                </div>

                <!-- Purchases Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="purchasesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ژمارەی پسووڵە</th>
                                <th>بەروار</th>
                                <th>ناوی کاڵا</th>
                                <th>کۆدی کاڵا</th>
                                <th>بڕ</th>
                                <th>نرخی یەکە</th>
                                <th>کۆی نرخ</th>
                                <th>کرێی گواستنەوە</th>
                                <th>خەرجی تر</th>
                                <th>داشکاندن</th>
                                <th>جۆری پارەدان</th>
                                <th>کردارەکان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $index => $purchase): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($purchase['invoice_number']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($purchase['date'])); ?></td>
                                <td><?php echo htmlspecialchars($purchase['products_list']); ?></td>
                                <td><?php echo htmlspecialchars($purchase['product_code'] ?? ''); ?></td>
                                <td><?php echo number_format($purchase['quantity']); ?></td>
                                <td><?php echo number_format($purchase['unit_price']); ?> دینار</td>
                                <td><?php echo number_format($purchase['total_amount']); ?> دینار</td>
                                <td><?php echo number_format($purchase['shipping_cost']); ?> دینار</td>
                                <td><?php echo number_format($purchase['other_cost']); ?> دینار</td>
                                <td><?php echo number_format($purchase['discount']); ?> دینار</td>
                                <td>
                                    <?php if ($purchase['payment_type'] == 'cash'): ?>
                                        <span class="badge bg-success">نقدی</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">قەرز</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info show-purchase-items" 
                                                data-invoice="<?php echo $purchase['invoice_number']; ?>">
                                            <i class="bi bi-list"></i>
                                        </button>
                                        <a href="editPurchase.php?id=<?php echo $purchase['id']; ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-purchase" 
                                                data-id="<?php echo $purchase['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    <script>
        // Initialize pagination
        $(document).ready(function() {
            $('#purchasesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Kurdish.json"
                }
            });
        });

        // Show purchase items in modal
        document.querySelectorAll('.show-purchase-items').forEach(button => {
            button.addEventListener('click', function() {
                const invoiceNumber = this.dataset.invoice;
                
                // Get purchase items from the server
                fetch('../../api/receipts/get_purchase_items.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `purchase_id=${invoiceNumber}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Create table HTML
                        let tableHtml = `
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ناوی کاڵا</th>
                                        <th>کۆدی کاڵا</th>
                                        <th>بڕ</th>
                                        <th>جۆری یەکە</th>
                                        <th>نرخی یەکە</th>
                                        <th>کۆی نرخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        data.items.forEach(item => {
                            const unitType = {
                                'piece': 'دانە',
                                'box': 'کارتۆن',
                                'set': 'سێت'
                            }[item.unit_type] || item.unit_type;
                            
                            tableHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.product_code}</td>
                                    <td>${parseInt(item.quantity).toLocaleString()}</td>
                                    <td>${unitType}</td>
                                    <td>${parseInt(item.unit_price).toLocaleString()} دینار</td>
                                    <td>${parseInt(item.total_price).toLocaleString()} دینار</td>
                                </tr>
                            `;
                        });
                        
                        tableHtml += `
                                </tbody>
                            </table>
                        `;
                        
                        // Show modal
                        Swal.fire({
                            title: `وردەکاری پسووڵە: ${invoiceNumber}`,
                            html: tableHtml,
                            width: '80%',
                            showCloseButton: true,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیارییەکان', 'error');
                });
            });
        });

        // Delete purchase
        document.querySelectorAll('.delete-purchase').forEach(button => {
            button.addEventListener('click', function() {
                const purchaseId = this.dataset.id;
                
                Swal.fire({
                    title: 'دڵنیای لە سڕینەوە؟',
                    text: "ئەم کردارە گەڕانەوەی نییە!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'بەڵێ، بسڕەوە!',
                    cancelButtonText: 'نەخێر'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('../../ajax/delete_purchase.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `purchase_id=${purchaseId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('سڕایەوە!', 'پسووڵەکە بە سەرکەوتوویی سڕایەوە.', 'success')
                                .then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('هەڵە', data.message || 'هەڵەیەک ڕوویدا لە کاتی سڕینەوە', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('هەڵە', 'هەڵەیەک ڕوویدا لە کاتی سڕینەوە', 'error');
                        });
                    }
                });
            });
        });

        // Apply filters
        function applyFilters() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const supplierName = document.getElementById('supplierName').value;
            
            // Reload the page with filters
            window.location.href = `purchaseList.php?start_date=${startDate}&end_date=${endDate}&supplier_name=${supplierName}`;
        }
    </script>
</body>
</html> 