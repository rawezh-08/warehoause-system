<?php
require_once __DIR__ . '/config/database.php';

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

// Get customers and suppliers
$customerStmt = $conn->query("SELECT * FROM customers");
$customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);

$supplierStmt = $conn->query("SELECT * FROM suppliers");
$suppliers = $supplierStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێستی سڕینەوەی کڕیار و دابینکەر</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="page-title">تێستی سڕینەوەی کڕیار و دابینکەر</h3>
            </div>
        </div>

        <!-- Test Customers -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">لیستی کڕیارەکان</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>ناوی کڕیار</th>
                                        <th>ژمارەی مۆبایل</th>
                                        <th>قەرز</th>
                                        <th>کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $index => $customer): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['phone1']); ?></td>
                                            <td><?php echo number_format($customer['debit_on_business'], 0); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm delete-customer" 
                                                            data-id="<?php echo $customer['id']; ?>">
                                                        سڕینەوە
                                                    </button>
                                                </div>
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

        <!-- Test Suppliers -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">لیستی دابینکەرەکان</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>ناوی دابینکەر</th>
                                        <th>ژمارەی مۆبایل</th>
                                        <th>قەرز</th>
                                        <th>کردارەکان</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliers as $index => $supplier): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                            <td><?php echo htmlspecialchars($supplier['phone1']); ?></td>
                                            <td><?php echo number_format($supplier['debt_on_myself'], 0); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm delete-supplier" 
                                                            data-id="<?php echo $supplier['id']; ?>">
                                                        سڕینەوە
                                                    </button>
                                                </div>
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

        <!-- Test Output -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">وەڵامی سێرڤەر</h5>
                    </div>
                    <div class="card-body">
                        <pre id="server-response" class="bg-light p-3 rounded">ئەنجامی کردارەکان لێرە دەردەکەوێت...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Customer delete button handlers
            document.querySelectorAll('.delete-customer').forEach(button => {
                button.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-id');
                    
                    Swal.fire({
                        title: 'دڵنیای لە سڕینەوە؟',
                        text: 'ئەم کردارە ناتوانرێت گەڕێنرێتەوە!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'بەڵێ، بیسڕەوە',
                        cancelButtonText: 'نا، هەڵوەشێنەوە'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'تکایە چاوەڕێ بکە...',
                                text: 'سڕینەوەی کڕیار',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            // Send delete request
                            fetch('process/delete_customer.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ id: customerId })
                            })
                            .then(response => response.text())
                            .then(text => {
                                // Display raw response for testing
                                document.getElementById('server-response').textContent = text;
                                
                                try {
                                    const data = JSON.parse(text);
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'سەرکەوتوو بوو!',
                                            text: data.message,
                                            confirmButtonText: 'باشە'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'هەڵە!',
                                            text: data.message,
                                            confirmButtonText: 'باشە'
                                        });
                                    }
                                } catch (e) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە!',
                                        text: 'داتای گەڕاوە ناتوانرێت شیکار بکرێت',
                                        confirmButtonText: 'باشە'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                document.getElementById('server-response').textContent = error.toString();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                                    confirmButtonText: 'باشە'
                                });
                            });
                        }
                    });
                });
            });
            
            // Supplier delete button handlers
            document.querySelectorAll('.delete-supplier').forEach(button => {
                button.addEventListener('click', function() {
                    const supplierId = this.getAttribute('data-id');
                    
                    Swal.fire({
                        title: 'دڵنیای لە سڕینەوە؟',
                        text: 'ئەم کردارە ناتوانرێت گەڕێنرێتەوە!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'بەڵێ، بیسڕەوە',
                        cancelButtonText: 'نا، هەڵوەشێنەوە'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'تکایە چاوەڕێ بکە...',
                                text: 'سڕینەوەی دابینکەر',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            // Send delete request
                            fetch('process/delete_supplier.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ id: supplierId })
                            })
                            .then(response => response.text())
                            .then(text => {
                                // Display raw response for testing
                                document.getElementById('server-response').textContent = text;
                                
                                try {
                                    const data = JSON.parse(text);
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'سەرکەوتوو بوو!',
                                            text: data.message,
                                            confirmButtonText: 'باشە'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'هەڵە!',
                                            text: data.message,
                                            confirmButtonText: 'باشە'
                                        });
                                    }
                                } catch (e) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'هەڵە!',
                                        text: 'داتای گەڕاوە ناتوانرێت شیکار بکرێت',
                                        confirmButtonText: 'باشە'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                document.getElementById('server-response').textContent = error.toString();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە!',
                                    text: 'هەڵەیەک ڕوویدا لە پەیوەندیکردن بە سێرڤەرەوە',
                                    confirmButtonText: 'باشە'
                                });
                            });
                        }
                    });
                });
            });
        });
    </script>
</body>
</html> 