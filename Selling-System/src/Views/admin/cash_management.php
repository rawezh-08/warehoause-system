<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Handle form submissions and delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete_transaction') {
            // Handle delete action
            $transaction_id = intval($_POST['transaction_id']);
            try {
                $stmt = $conn->prepare("DELETE FROM cash_management WHERE id = ?");
                $stmt->execute([$transaction_id]);
                $success = true;
                $success_message = "سەرکەوتوویی سڕایەوە";
            } catch (PDOException $e) {
                $error = "هەڵەیەک ڕوویدا لە کاتی سڕینەوە: " . $e->getMessage();
            }
        } else if ($_POST['action'] === 'add_transaction') {
            // Existing add transaction code...
            $amount = floatval($_POST['amount']);
            $transaction_type = $_POST['transaction_type'];
            $notes = $_POST['notes'];
            $created_by = $_SESSION['user_id'];

            if ($transaction_type === 'withdrawal') {
                $amount = -$amount;
            }

            try {
                $stmt = $conn->prepare("
                    INSERT INTO cash_management (amount, transaction_type, notes, created_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$amount, $transaction_type, $notes, $created_by]);
                $success = true;
                $success_message = "سەرکەوتوویی تۆمارکرا";
            } catch (PDOException $e) {
                $error = "هەڵەیەک ڕوویدا: " . $e->getMessage();
            }
        }
    }
}

// Get current cash balance
$stmt = $conn->prepare("SELECT SUM(amount) as total_cash FROM cash_management");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCash = $result['total_cash'] ?? 0;

// Set up pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get transaction history with pagination
$stmt = $conn->prepare("
    SELECT 
        id,
        amount,
        transaction_type,
        notes,
        created_at
    FROM cash_management
    ORDER BY created_at DESC
    LIMIT :offset, :limit
");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of transactions
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cash_management");
$stmt->execute();
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی پارە - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/employeePayment/style.css">
    <link rel="stylesheet" href="../../css/products.css">
    <style>
        .report-table th, .report-table td {
            border: 1px solid #dee2e6;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            background: #fff;
            border: 1px solid #dc3545;
            color: #dc3545;
        }
        .action-btn:hover {
            background: #dc3545;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 3px 5px rgba(220, 53, 69, 0.2);
        }
        .action-btn i {
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div>
        <!-- Navbar container - populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main Content Wrapper -->
        <div id="content" class="content-wrapper">
            <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-12">
                            <h3 class="page-title mb-0">بەڕێوەبردنی پارە</h3>
                            <p class="text-muted mb-0">بەڕێوەبردنی پارەی دەخیلە</p>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Form and Transaction History Side by Side -->
                    <div class="row">
                        <!-- Transaction Form -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">زیادکردنی پارە</h5>
                                    <form method="POST" action="" id="cashForm">
                                        <input type="hidden" name="action" value="add_transaction">
                                        
                                        <div class="mb-3">
                                            <label for="transaction_type" class="form-label">جۆری پارە</label>
                                            <select class="form-select" id="transaction_type" name="transaction_type" required>
                                                <option value="initial_balance">پارەی سەرەتایی</option>
                                                <option value="deposit">پارەی زیادە</option>
                                           
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="amount" class="form-label">بڕی پارە</label>
                                            <input type="number" class="form-control" id="amount" name="amount" required min="0" step="0.01">
                                        </div>

                                        <div class="mb-3">
                                            <label for="notes" class="form-label">تێبینی</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>پاشەکەوت
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transaction History -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">مێژووی پارەکان</h5>
                                    <div class="table-responsive">
                                        <table class="table table-hover report-table">
                                            <thead>
                                                <tr>
                                                    <th>بەروار</th>
                                                    <th>جۆری پارە</th>
                                                    <th>بڕی پارە</th>
                                                    <th>تێبینی</th>
                                                    <th>کردارەکان</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transactions as $transaction): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></td>
                                                    <td>
                                                        <?php
                                                        switch ($transaction['transaction_type']) {
                                                            case 'initial_balance':
                                                                echo '<span class="badge bg-primary">پارەی سەرەتایی</span>';
                                                                break;
                                                            case 'deposit':
                                                                echo '<span class="badge bg-success">پارەی زیادە</span>';
                                                                break;
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="<?php echo $transaction['amount'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo number_format($transaction['amount']); ?> د.ع
                                                    </td>
                                                    <td><?php echo htmlspecialchars($transaction['notes']); ?></td>
                                                    <td>
                                                        <button class="action-btn delete-transaction" 
                                                                data-id="<?php echo $transaction['id']; ?>"
                                                                data-amount="<?php echo number_format($transaction['amount']); ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        
                                        <!-- Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                        <nav aria-label="صفحات">
                                            <ul class="pagination">
                                                <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="پێشتر">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                                <?php endfor; ?>
                                                
                                                <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="دواتر">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <!-- Global JS -->
    <script src="../../js/include-components.js"></script>

    <script>
        $(document).ready(function() {
            // Form validation
            $('#cashForm').on('submit', function(e) {
                const amount = parseFloat($('#amount').val());
                const transactionType = $('#transaction_type').val();
                
                if (amount <= 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'هەڵە!',
                        text: 'تکایە بڕی پارەکە بە دروستی بنووسە',
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                    return false;
                }
            });
            
            // Handle delete transaction
            $('.delete-transaction').on('click', function() {
                const transactionId = $(this).data('id');
                const amount = $(this).data('amount');
                
                Swal.fire({
                    title: 'دڵنیای لە سڕینەوە؟',
                    text: `ئایا دڵنیای لە سڕینەوەی ئەم تۆمارە بە بڕی ${amount} د.ع؟`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بەڵێ، بیسڕەوە',
                    cancelButtonText: 'نەخێر',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create and submit form
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': ''
                        }).append($('<input>', {
                            'type': 'hidden',
                            'name': 'action',
                            'value': 'delete_transaction'
                        })).append($('<input>', {
                            'type': 'hidden',
                            'name': 'transaction_id',
                            'value': transactionId
                        }));
                        
                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            <?php if (isset($success)): ?>
            // Show success message with SweetAlert2
            Swal.fire({
                title: 'سەرکەوتوو!',
                text: '<?php echo $success_message; ?>',
                icon: 'success',
                confirmButtonText: 'باشە'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Reload to update the table
                    window.location.href = 'cash_management.php';
                }
            });
            <?php endif; ?>
        });
    </script>
</body>

</html> 