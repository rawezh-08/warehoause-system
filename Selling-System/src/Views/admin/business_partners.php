<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get all customers that are business partners
$query = "SELECT * FROM customers WHERE is_business_partner = 1 ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all suppliers that are business partners
$query = "SELECT * FROM suppliers WHERE is_business_partner = 1 ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine the data for display
$businessPartners = array();

// Add customers with their supplier info
foreach ($customers as $customer) {
    $partnerData = array(
        'id' => $customer['id'],
        'name' => $customer['name'],
        'phone1' => $customer['phone1'],
        'phone2' => $customer['phone2'],
        'customer_id' => $customer['id'],
        'supplier_id' => null,
        'customer_debt' => $customer['debit_on_business'],
        'supplier_debt' => 0,
        'notes' => $customer['notes']
    );

    if (!empty($customer['supplier_id'])) {
        // Get the linked supplier information
        $supplierQuery = "SELECT * FROM suppliers WHERE id = :supplier_id";
        $supplierStmt = $conn->prepare($supplierQuery);
        $supplierStmt->bindParam(':supplier_id', $customer['supplier_id']);
        $supplierStmt->execute();
        $supplierInfo = $supplierStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($supplierInfo) {
            $partnerData['supplier_id'] = $supplierInfo['id'];
            $partnerData['supplier_debt'] = $supplierInfo['debt_on_myself'];
        }
    }

    $businessPartners[] = $partnerData;
}

// Add suppliers that don't have a customer link
foreach ($suppliers as $supplier) {
    // Check if this supplier is already added through a customer
    $isAlreadyAdded = false;
    foreach ($businessPartners as $partner) {
        if ($partner['supplier_id'] == $supplier['id']) {
            $isAlreadyAdded = true;
            break;
        }
    }

    if (!$isAlreadyAdded) {
        $businessPartners[] = array(
            'id' => $supplier['id'],
            'name' => $supplier['name'],
            'phone1' => $supplier['phone1'],
            'phone2' => $supplier['phone2'],
            'customer_id' => null,
            'supplier_id' => $supplier['id'],
            'customer_debt' => 0,
            'supplier_debt' => $supplier['debt_on_myself'],
            'notes' => $supplier['notes']
        );
    }
}

// Sort business partners by name
usort($businessPartners, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Calculate summary statistics
$totalPartners = count($businessPartners);
$totalCustomerDebt = 0;
$totalSupplierDebt = 0;
$partnersWithDebt = 0;

foreach ($businessPartners as $partner) {
    if ($partner['customer_debt'] > 0) {
        $totalCustomerDebt += $partner['customer_debt'];
        $partnersWithDebt++;
    }
    if ($partner['supplier_debt'] > 0) {
        $totalSupplierDebt += $partner['supplier_debt'];
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کڕیار و دابینکەرەکان - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/employeePayment/style.css">
    <link rel="stylesheet" href="../../css/staff.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Custom styles for this page -->
    <style>
           /* Transparent search input */
           .table-search-input {
            background-color: transparent !important;
            border: 1px solid #dee2e6;
        }
        
        .custom-table td,
        th {
            white-space: normal;
            word-wrap: break-word;
            vertical-align: middle;
            padding: 0.75rem;
        }

        #partnersTable td {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #partnersTable th {
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Summary Cards Styles */
        .summary-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: default;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-card .card-body {
            padding: 1.5rem;
        }
        
        .summary-card .card-title {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .summary-card .card-value {
            font-size: 1.75rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        
        .summary-card .icon-bg {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
      
        /* Adjust pagination display for many pages */
        .pagination-numbers {
            flex-wrap: wrap;
            max-width: 300px;
            overflow: hidden;
        }
        
        .pagination-numbers .btn {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>
            
        <!-- Main content -->
        <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h3 class="page-title">لیستی کڕیار و دابینکەرەکان</h3>
                        <a href="addStaff.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> زیادکردنی کڕیار و دابینکەر
                        </a>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-primary me-3">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کۆی کڕیار و دابینکەر</h5>
                                    <p class="card-value"><?php echo number_format($totalPartners); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-danger me-3">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کۆی قەرز (کڕیار)</h5>
                                    <p class="card-value"><?php echo number_format($totalCustomerDebt); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card summary-card bg-white border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-bg bg-warning me-3">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">کۆی قەرز (دابینکەر)</h5>
                                    <p class="card-value"><?php echo number_format($totalSupplierDebt); ?> دینار</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Partners Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">هەموو کڕیار و دابینکەرەکان</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary refresh-btn me-2">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <!-- Table Controls -->
                                    <div class="table-controls mb-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                                <div class="records-per-page">
                                                    <label class="me-2">نیشاندان:</label>
                                                    <div class="custom-select-wrapper">
                                                        <select id="partnersRecordsPerPage" class="form-select form-select-sm rounded-pill">
                                                            <option value="5">5</option>
                                                            <option value="10" selected>10</option>
                                                            <option value="25">25</option>
                                                            <option value="50">50</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-sm-6">
                                                <div class="search-container">
                                                    <div class="input-group">
                                                        <input type="text" id="partnersTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                        <span class="input-group-text rounded-pill-end bg-light">
                                                            <i class="fas fa-search"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Table Content -->
                                    <div class="table-responsive">
                                        <table id="partnersTable" class="table table-bordered table-hover custom-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>ناو</th>
                                                    <th>ژمارەی تەلەفۆن</th>
                                                    <th>ژ. مۆبایلی ٢</th>
                                                    <th>بڕی قەرز (کڕیار)</th>
                                                    <th>بڕی قەرز (دابینکەر)</th>
                                                    <th>تێبینی</th>
                                                    <th>کردارەکان</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($businessPartners) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($businessPartners as $partner): ?>
                                                    <tr>
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo htmlspecialchars($partner['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($partner['phone1']); ?></td>
                                                        <td><?php echo !empty($partner['phone2']) ? htmlspecialchars($partner['phone2']) : '-'; ?></td>
                                                        <td>
                                                            <?php if ($partner['customer_debt'] > 0): ?>
                                                                <span class="text-danger">
                                                                    <?php echo number_format($partner['customer_debt']); ?> دینار
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-success">0 دینار</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($partner['supplier_debt'] > 0): ?>
                                                                <span class="text-danger">
                                                                    <?php echo number_format($partner['supplier_debt']); ?> دینار
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-success">0 دینار</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo !empty($partner['notes']) ? htmlspecialchars($partner['notes']) : '-'; ?></td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="businessPartnerProfile.php?<?php echo ($partner['customer_id'] ? 'customer_id=' . $partner['customer_id'] : '') . ($partner['supplier_id'] ? ($partner['customer_id'] ? '&' : '') . 'supplier_id=' . $partner['supplier_id'] : ''); ?>" class="btn btn-sm btn-outline-info rounded-circle" title="پڕۆفایلی گشتگیر">
                                                                    <i class="fas fa-id-card"></i>
                                                                </a>
                                                                
                                                                <?php if ($partner['customer_id']): ?>
                                                                <a href="customerProfile.php?id=<?php echo $partner['customer_id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="پرۆفایلی کڕیار">
                                                                    <i class="fas fa-user"></i>
                                                                </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($partner['supplier_id']): ?>
                                                                <a href="supplierProfile.php?id=<?php echo $partner['supplier_id']; ?>" class="btn btn-sm btn-outline-success rounded-circle" title="پرۆفایلی دابینکەر">
                                                                    <i class="fas fa-truck"></i>
                                                                </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($partner['customer_id']): ?>
                                                                <a href="../../Views/receipt/customer_history_receipt.php?customer_id=<?php echo $partner['customer_id']; ?>" class="btn btn-sm btn-outline-warning rounded-circle" target="_blank" title="بینینی مێژووی کڕیار">
                                                                    <i class="fas fa-history"></i>
                                                                </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center">هیچ داتایەک نەدۆزرایەوە</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Table Pagination -->
                                    <div class="table-pagination mt-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-6 mb-2 mb-md-0">
                                                <div class="pagination-info">
                                                    نیشاندانی <span id="partnersStartRecord">1</span> تا <span id="partnersEndRecord">10</span> لە کۆی <span id="partnersTotalRecords"><?php echo count($businessPartners); ?></span> تۆمار
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pagination-controls d-flex justify-content-md-end">
                                                    <button id="partnersPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                    <div id="partnersPaginationNumbers" class="pagination-numbers d-flex">
                                                        <!-- Will be populated by JavaScript -->
                                                    </div>
                                                    <button id="partnersNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Global AJAX Configuration -->
    <script src="../../js/ajax-config.js"></script>
    <!-- Initialize common elements -->
    <script src="../../js/include-components.js"></script>
    
    <script>
        $(document).ready(function() {
            // Table pagination functionality
            let currentPage = 1;
            const recordsPerPageSelect = $('#partnersRecordsPerPage');
            let recordsPerPage = parseInt(recordsPerPageSelect.val());
            const tableRows = $('#partnersTable tbody tr');
            const totalRecords = tableRows.length;
            
            // Update records per page when select changes
            recordsPerPageSelect.on('change', function() {
                recordsPerPage = parseInt($(this).val());
                currentPage = 1; // Reset to first page
                updateTable();
            });
            
            // Update the table display based on current page and records per page
            function updateTable() {
                const startIndex = (currentPage - 1) * recordsPerPage;
                const endIndex = startIndex + recordsPerPage;
                
                // Hide all rows
                tableRows.hide();
                
                // Show only rows for current page
                tableRows.slice(startIndex, endIndex).show();
                
                // Update pagination info
                $('#partnersStartRecord').text(totalRecords > 0 ? startIndex + 1 : 0);
                $('#partnersEndRecord').text(Math.min(endIndex, totalRecords));
                $('#partnersTotalRecords').text(totalRecords);
                
                // Enable/disable pagination buttons
                $('#partnersPrevPageBtn').prop('disabled', currentPage === 1);
                $('#partnersNextPageBtn').prop('disabled', endIndex >= totalRecords);
                
                // Update pagination numbers
                updatePaginationNumbers();
            }
            
            // Create pagination number buttons
            function updatePaginationNumbers() {
                const totalPages = Math.ceil(totalRecords / recordsPerPage);
                const paginationNumbersContainer = $('#partnersPaginationNumbers');
                
                // Clear existing pagination numbers
                paginationNumbersContainer.empty();
                
                // Calculate which page numbers to show
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                
                if (endPage - startPage < 4 && startPage > 1) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                // Add first page button if needed
                if (startPage > 1) {
                    paginationNumbersContainer.append(`
                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1 page-number" data-page="1">1</button>
                    `);
                    
                    if (startPage > 2) {
                        paginationNumbersContainer.append(`
                            <span class="d-flex align-items-center mx-1">...</span>
                        `);
                    }
                }
                
                // Add page number buttons
                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = i === currentPage ? 'btn-primary text-white' : 'btn-outline-primary';
                    paginationNumbersContainer.append(`
                        <button class="btn btn-sm ${activeClass} rounded-pill me-1 page-number" data-page="${i}">${i}</button>
                    `);
                }
                
                // Add last page button if needed
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        paginationNumbersContainer.append(`
                            <span class="d-flex align-items-center mx-1">...</span>
                        `);
                    }
                    
                    paginationNumbersContainer.append(`
                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1 page-number" data-page="${totalPages}">${totalPages}</button>
                    `);
                }
            }
            
            // Handle pagination button clicks
            $(document).on('click', '.page-number', function() {
                currentPage = parseInt($(this).data('page'));
                updateTable();
            });
            
            // Next page button
            $('#partnersNextPageBtn').on('click', function() {
                if (currentPage < Math.ceil(totalRecords / recordsPerPage)) {
                    currentPage++;
                    updateTable();
                }
            });
            
            // Previous page button
            $('#partnersPrevPageBtn').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    updateTable();
                }
            });
            
            // Search functionality
            $('#partnersTableSearch').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                // If search term is empty, reset table
                if (searchTerm === '') {
                    tableRows.show();
                    updateTable();
                    return;
                }
                
                // Filter rows based on search term
                tableRows.hide();
                const filteredRows = tableRows.filter(function() {
                    const rowText = $(this).text().toLowerCase();
                    return rowText.includes(searchTerm);
                });
                
                filteredRows.show();
                
                // Update pagination information
                $('#partnersStartRecord').text(filteredRows.length > 0 ? 1 : 0);
                $('#partnersEndRecord').text(filteredRows.length);
                $('#partnersTotalRecords').text(filteredRows.length);
                
                // Disable pagination when searching
                $('#partnersPrevPageBtn, #partnersNextPageBtn').prop('disabled', true);
                $('#partnersPaginationNumbers').empty();
            });
            
            // Refresh button
            $('.refresh-btn').on('click', function() {
                location.reload();
            });
            
            // Initialize table on page load
            updateTable();
        });
    </script>
</body>
</html> 