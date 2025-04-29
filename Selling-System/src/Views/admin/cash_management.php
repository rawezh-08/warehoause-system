<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $amount = floatval($_POST['amount']);
        $transaction_type = $_POST['transaction_type'];
        $notes = $_POST['notes'];
        $created_by = $_SESSION['user_id'];

        // For withdrawals, make amount negative
        if ($transaction_type === 'withdrawal') {
            $amount = -$amount;
        }

        try {
            $stmt = $conn->prepare("
                INSERT INTO cash_management (amount, transaction_type, notes, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$amount, $transaction_type, $notes, $created_by]);
            
            // Redirect to prevent form resubmission
            header("Location: cash_management.php?success=1");
            exit;
        } catch (PDOException $e) {
            $error = "هەڵەیەک ڕوویدا: " . $e->getMessage();
        }
    }
}

// Get current cash balance
$stmt = $conn->prepare("SELECT SUM(amount) as total_cash FROM cash_management");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCash = $result['total_cash'] ?? 0;

// Get transaction history
$stmt = $conn->prepare("
    SELECT 
        amount,
        transaction_type,
        notes,
        created_at,
        created_by
    FROM cash_management
    ORDER BY created_at DESC
");
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get admin usernames for display
$adminIds = array_unique(array_column($transactions, 'created_by'));
$adminUsernames = [];
if (!empty($adminIds)) {
    $placeholders = str_repeat('?,', count($adminIds) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, username FROM admin_accounts WHERE id IN ($placeholders)");
    $stmt->execute($adminIds);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $adminUsernames[$row['id']] = $row['username'];
    }
}
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
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/employeePayment/style.css">
    <link rel="stylesheet" href="../../css/products.css">
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

                    <!-- Success Message -->
                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        ئەمەڕەکە بە سەرکەوتوویی تۆمارکرا
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Error Message -->
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Current Balance Card -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">پارەی بەردەست</h5>
                                    <h2 class="text-primary mb-0"><?php echo number_format($totalCash); ?> د.ع</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Form -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">زیادکردنی پارە</h5>
                                    <form method="POST" action="">
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
                    </div>

                    <!-- Transaction History -->
                    <div class="row">
                        <div class="col-12">
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
                                                    <th>دروستکراوە لەلایەن</th>
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
                                                    <td><?php echo $adminUsernames[$transaction['created_by']] ?? 'Unknown'; ?></td>
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
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Global JS -->
    <script src="../../js/include-components.js"></script>

    <script>
        $(document).ready(function() {
            // Form validation
            $('form').on('submit', function(e) {
                const amount = parseFloat($('#amount').val());
                const transactionType = $('#transaction_type').val();
                
                if (amount <= 0) {
                    e.preventDefault();
                    alert('تکایە بڕی پارەکە بە دروستی بنووسە');
                    return false;
                }
            });
        });
    </script>
</body>

</html> 