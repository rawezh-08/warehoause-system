<?php
// Include authentication check
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $transaction_type = $_POST['transaction_type'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // For withdrawal, make the amount negative
    if ($transaction_type === 'withdrawal') {
        $amount = -1 * abs($amount);
    }
    
    // Insert the transaction
    $stmt = $conn->prepare("
        INSERT INTO cash_management (
            amount, transaction_type, notes, created_by
        ) VALUES (
            :amount, :transaction_type, :notes, :created_by
        )
    ");
    
    $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
    $stmt->bindParam(':transaction_type', $transaction_type, PDO::PARAM_STR);
    $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $stmt->bindParam(':created_by', $_SESSION['user_id'], PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $success_message = 'پرۆسەکە بە سەرکەوتوویی ئەنجام درا';
    } else {
        $error_message = 'کێشەیەک ڕوویدا لە کاتی پاشەکەوتکردنی زانیارییەکان';
    }
}

// Get current cash balance
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_balance 
    FROM 
        cash_management
");
$stmt->execute();
$currentBalance = $stmt->fetch(PDO::FETCH_ASSOC)['total_balance'];

// Get transaction history (most recent first)
$stmt = $conn->prepare("
    SELECT 
        id, 
        amount, 
        transaction_type, 
        notes, 
        created_at 
    FROM 
        cash_management
    ORDER BY 
        created_at DESC
    LIMIT 50
");
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Translate transaction types to Kurdish
$transaction_types = [
    'initial_balance' => 'باڵانسی سەرەتایی',
    'deposit' => 'داخڵکردنی پارە',
    'withdrawal' => 'دەرهێنانی پارە',
    'adjustment' => 'ڕێکخستنەوە'
];
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی پارەی کاش - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <link rel="stylesheet" href="../../css/global.css">
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
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h3 class="page-title mb-0">بەڕێوەبردنی پارەی کاش</h3>
                            <p class="text-muted mb-0">باڵانسی قاسە و دەخیلەی پارە</p>
                        </div>
                    </div>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Current Balance Card -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title">باڵانسی ئێستای پارەی کاش</h5>
                                        <div class="stat-icon bg-success-light">
                                            <i class="fas fa-money-bill-wave text-success"></i>
                                        </div>
                                    </div>
                                    <h3 class="mb-0 mt-3 fw-bold text-success"><?php echo number_format($currentBalance); ?> د.ع</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Transaction Form -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">تۆمارکردنی جوڵانەوەی پارە</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="transaction_type" class="form-label">جۆری جوڵانەوە</label>
                                            <select class="form-select" id="transaction_type" name="transaction_type" required>
                                                <option value="initial_balance">باڵانسی سەرەتایی</option>
                                                <option value="deposit">داخڵکردنی پارە</option>
                                                <option value="withdrawal">دەرهێنانی پارە</option>
                                                <option value="adjustment">ڕێکخستنەوە</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">بڕی پارە (د.ع)</label>
                                            <input type="number" class="form-control" id="amount" name="amount" min="0" step="0.01" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="notes" class="form-label">تێبینی</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-2"></i> پاشەکەوتکردن
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction History -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">مێژووی جوڵانەوەکانی پارە</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="transactions-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>بەروار</th>
                                                    <th>جۆری جوڵانەوە</th>
                                                    <th>بڕ</th>
                                                    <th>تێبینی</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transactions as $index => $transaction): ?>
                                                    <tr>
                                                        <td><?php echo $index + 1; ?></td>
                                                        <td><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></td>
                                                        <td>
                                                            <?php 
                                                                $type = $transaction['transaction_type'];
                                                                $icon = '';
                                                                $class = '';
                                                                
                                                                switch ($type) {
                                                                    case 'initial_balance':
                                                                        $icon = 'fa-coins';
                                                                        $class = 'bg-info text-white';
                                                                        break;
                                                                    case 'deposit':
                                                                        $icon = 'fa-arrow-down';
                                                                        $class = 'bg-success text-white';
                                                                        break;
                                                                    case 'withdrawal':
                                                                        $icon = 'fa-arrow-up';
                                                                        $class = 'bg-danger text-white';
                                                                        break;
                                                                    case 'adjustment':
                                                                        $icon = 'fa-balance-scale';
                                                                        $class = 'bg-warning text-dark';
                                                                        break;
                                                                }
                                                            ?>
                                                            <span class="badge <?php echo $class; ?> rounded-pill">
                                                                <i class="fas <?php echo $icon; ?> me-1"></i>
                                                                <?php echo $transaction_types[$type]; ?>
                                                            </span>
                                                        </td>
                                                        <td class="<?php echo $transaction['amount'] >= 0 ? 'text-success' : 'text-danger'; ?> fw-bold">
                                                            <?php echo number_format($transaction['amount']); ?> د.ع
                                                        </td>
                                                        <td><?php echo $transaction['notes']; ?></td>
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
            // Initialize DataTable
            $('#transactions-table').DataTable({
                order: [[1, 'desc']], // Sort by date column descending
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ku.json'
                }
            });
        });
    </script>
</body>

</html> 