<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../controllers/receipts/WastingReceiptsController.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit;
}

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Initialize wastings controller
$wastingController = new WastingReceiptsController($conn);

// Get wasting data
$wastings = $wastingController->getWastingData();

// Page title
$title = "لیستی پسووڵەکان";
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - سیستەمی فرۆشتن</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Datatables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="../../assets/css/style.css" rel="stylesheet">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
        .tab-content {
            padding: 20px 0;
        }
        .nav-tabs .nav-link {
            font-weight: 600;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include_once '../../components/header.php'; ?>
    
    <!-- Main Content -->
    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-receipt me-2"></i> <?php echo $title; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" id="receiptsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" 
                                        type="button" role="tab" aria-controls="sales" aria-selected="true">
                                    <i class="fas fa-shopping-cart me-1"></i> فرۆشتنەکان
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" 
                                        type="button" role="tab" aria-controls="purchases" aria-selected="false">
                                    <i class="fas fa-truck me-1"></i> کڕینەکان
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="wastings-tab" data-bs-toggle="tab" data-bs-target="#wastings" 
                                        type="button" role="tab" aria-controls="wastings" aria-selected="false">
                                    <i class="fas fa-trash-alt me-1"></i> کاڵا بەفیڕۆچووەکان
                                </button>
                            </li>
                        </ul>
                        
                        <!-- Tab content -->
                        <div class="tab-content" id="receiptsTabsContent">
                            <!-- Sales Tab -->
                            <div class="tab-pane fade show active" id="sales" role="tabpanel" aria-labelledby="sales-tab">
                                <div class="table-responsive">
                                    <table id="salesTable" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ژ.پسووڵە</th>
                                                <th>بەروار</th>
                                                <th>کڕیار</th>
                                                <th>شێوازی پارەدان</th>
                                                <th>کۆی گشتی</th>
                                                <th>کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Sales data will be loaded using AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Purchases Tab -->
                            <div class="tab-pane fade" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                                <div class="table-responsive">
                                    <table id="purchasesTable" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ژ.پسووڵە</th>
                                                <th>بەروار</th>
                                                <th>دابینکەر</th>
                                                <th>شێوازی پارەدان</th>
                                                <th>کۆی گشتی</th>
                                                <th>کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Purchases data will be loaded using AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Wastings Tab -->
                            <div class="tab-pane fade" id="wastings" role="tabpanel" aria-labelledby="wastings-tab">
                                <div class="mb-3 text-end">
                                    <a href="../transactions/wasting.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-1"></i> زیادکردنی کاڵای بەفیڕۆچوو
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table id="wastingsTable" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>بەروار</th>
                                                <th>کاڵاکان</th>
                                                <th>تێبینی</th>
                                                <th>کۆی گشتی</th>
                                                <th>کردارەکان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($wastings as $wasting): ?>
                                            <tr>
                                                <td><?php echo $wasting['id']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($wasting['date'])); ?></td>
                                                <td><?php echo $wasting['products_list']; ?></td>
                                                <td><?php echo $wasting['notes']; ?></td>
                                                <td><?php echo number_format($wasting['total_amount']); ?> دینار</td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-sm btn-info view-wasting" 
                                                            data-id="<?php echo $wasting['id']; ?>" title="نیشاندان">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-wasting" 
                                                            data-id="<?php echo $wasting['id']; ?>" title="سڕینەوە">
                                                        <i class="fas fa-trash"></i>
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
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once '../../components/footer.php'; ?>
    
    <!-- View Wasting Modal -->
    <div class="modal fade" id="viewWastingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>وردەکاری کاڵای بەفیڕۆچوو
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="wastingDetails">
                    <!-- Wasting details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary print-wasting-receipt">
                        <i class="fas fa-print me-1"></i> چاپکردن
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#salesTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ku.json',
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../../ajax/get_sales_list.php',
                    type: 'POST'
                },
                columns: [
                    { data: 'invoice_number' },
                    { data: 'date' },
                    { data: 'customer_name' },
                    { data: 'payment_type' },
                    { data: 'total_amount' },
                    { data: 'actions', orderable: false, searchable: false }
                ]
            });
            
            $('#purchasesTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ku.json',
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../../ajax/get_purchases_list.php',
                    type: 'POST'
                },
                columns: [
                    { data: 'invoice_number' },
                    { data: 'date' },
                    { data: 'supplier_name' },
                    { data: 'payment_type' },
                    { data: 'total_amount' },
                    { data: 'actions', orderable: false, searchable: false }
                ]
            });
            
            $('#wastingsTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ku.json',
                }
            });
            
            // View Wasting Details
            $(document).on('click', '.view-wasting', function() {
                const wastingId = $(this).data('id');
                
                $.ajax({
                    url: '../../ajax/get_wasting_details.php',
                    type: 'POST',
                    data: { wasting_id: wastingId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            const wasting = response.wasting;
                            
                            // Build details HTML
                            let detailsHtml = `
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>ژمارەی تۆمار:</strong> ${wasting.id}</p>
                                        <p><strong>بەروار:</strong> ${wasting.date}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>کۆی گشتی:</strong> ${wasting.total_amount.toLocaleString()} دینار</p>
                                        <p><strong>تێبینی:</strong> ${wasting.notes || 'هیچ'}</p>
                                    </div>
                                </div>
                                
                                <h5 class="mt-4 mb-3">کاڵاکان</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>ناوی کاڵا</th>
                                                <th>بڕ</th>
                                                <th>نرخی تاک</th>
                                                <th>نرخی گشتی</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                            
                            wasting.items.forEach((item, index) => {
                                let unitType = '';
                                switch(item.unit_type) {
                                    case 'piece': unitType = 'دانە'; break;
                                    case 'box': unitType = 'کارتۆن'; break;
                                    case 'set': unitType = 'سێت'; break;
                                    default: unitType = 'دانە';
                                }
                                
                                detailsHtml += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.product_name}</td>
                                        <td>${item.quantity} ${unitType}</td>
                                        <td>${item.unit_price.toLocaleString()} دینار</td>
                                        <td>${item.total_price.toLocaleString()} دینار</td>
                                    </tr>`;
                            });
                            
                            detailsHtml += `
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-end">کۆی گشتی:</th>
                                                <th>${wasting.total_amount.toLocaleString()} دینار</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>`;
                            
                            // Set wasting details to modal
                            $('#wastingDetails').html(detailsHtml);
                            $('#viewWastingModal').modal('show');
                            
                            // Store wasting ID for printing
                            $('.print-wasting-receipt').data('id', wastingId);
                        } else {
                            Swal.fire({
                                title: 'هەڵە!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە وەرگرتنی زانیارییەکان',
                            icon: 'error',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            });
            
            // Print Wasting Receipt
            $(document).on('click', '.print-wasting-receipt', function() {
                const wastingId = $(this).data('id');
                const printWindow = window.open(`../receipt/wasting_receipt.php?id=${wastingId}`, '_blank');
                
                if (printWindow) {
                    printWindow.addEventListener('load', function() {
                        printWindow.print();
                    });
                } else {
                    Swal.fire({
                        title: 'ئاگاداری',
                        text: 'تکایە ڕێگە بدە بە کردنەوەی پەنجەرەی نوێ بۆ چاپکردن',
                        icon: 'warning',
                        confirmButtonText: 'باشە'
                    });
                }
            });
            
            // Delete Wasting
            $(document).on('click', '.delete-wasting', function() {
                const wastingId = $(this).data('id');
                
                Swal.fire({
                    title: 'ئایا دڵنیایت؟',
                    text: 'ئەم کردارە ناگەڕێتەوە!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ، بیسڕەوە!',
                    cancelButtonText: 'نەخێر، هەڵوەشاندنەوە'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../../ajax/delete_wasting.php',
                            type: 'POST',
                            data: { wasting_id: wastingId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'سڕایەوە!',
                                        text: response.message,
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
                            },
                            error: function(xhr, status, error) {
                                Swal.fire({
                                    title: 'هەڵە!',
                                    text: 'هەڵەیەک ڕوویدا لە سڕینەوەدا',
                                    icon: 'error',
                                    confirmButtonText: 'باشە'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>