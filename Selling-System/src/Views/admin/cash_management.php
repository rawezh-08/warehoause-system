<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Get cash management transactions
$stmt = $conn->prepare("
    SELECT 
        id,
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

// Calculate total balance
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(CASE 
            WHEN transaction_type IN ('initial_balance', 'deposit') THEN amount
            WHEN transaction_type = 'withdrawal' THEN -amount
            ELSE 0
        END), 0) as total_balance
    FROM cash_management
");
$stmt->execute();
$totalBalance = $stmt->fetch(PDO::FETCH_ASSOC)['total_balance'];
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
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-12">
                            <h3 class="page-title mb-0">بەڕێوەبردنی پارە</h3>
                            <p class="text-muted mb-0">بەڕێوەبردنی پارەی دەستی و دامەزراوە</p>
                        </div>
                    </div>

                    <!-- Balance Card -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">کۆی پارەی بەردەست</h5>
                                    <h2 class="text-primary"><?php echo number_format($totalBalance); ?> د.ع</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Transaction Button -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                                <i class="fas fa-plus"></i> زیادکردنی مامەڵە
                            </button>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">مامەڵەکان</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="transactionsTable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>بەروار</th>
                                                    <th>جۆری مامەڵە</th>
                                                    <th>بڕ</th>
                                                    <th>تێبینی</th>
                                                    <th>دروستکراوە لەلایەن</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transactions as $index => $transaction): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo date('Y/m/d H:i', strtotime($transaction['created_at'])); ?></td>
                                                    <td>
                                                        <?php
                                                        switch ($transaction['transaction_type']) {
                                                            case 'initial_balance':
                                                                echo 'پارەی دەستی';
                                                                break;
                                                            case 'deposit':
                                                                echo 'زیادکردنەوە';
                                                                break;
                                                            case 'withdrawal':
                                                                echo 'کەمکردنەوە';
                                                                break;
                                                            case 'adjustment':
                                                                echo 'گۆڕانکاری';
                                                                break;
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="<?php echo $transaction['amount'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo number_format(abs($transaction['amount'])); ?> د.ع
                                                    </td>
                                                    <td><?php echo $transaction['notes']; ?></td>
                                                    <td><?php echo $transaction['created_by']; ?></td>
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

    <!-- Add Transaction Modal -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">زیادکردنی مامەڵە</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTransactionForm">
                        <div class="mb-3">
                            <label for="transactionType" class="form-label">جۆری مامەڵە</label>
                            <select class="form-select" id="transactionType" name="transaction_type" required>
                                <option value="initial_balance">پارەی دەستی</option>
                                <option value="deposit">زیادکردنەوە</option>
                                <option value="withdrawal">کەمکردنەوە</option>
                                <option value="adjustment">گۆڕانکاری</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">بڕ</label>
                            <input type="number" class="form-control" id="amount" name="amount" required min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">تێبینی</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="button" class="btn btn-primary" id="saveTransaction">پاشەکەوت</button>
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
            $('#transactionsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ku.json'
                }
            });

            // Handle form submission
            $('#saveTransaction').click(function() {
                const formData = {
                    transaction_type: $('#transactionType').val(),
                    amount: parseFloat($('#amount').val()),
                    notes: $('#notes').val()
                };

                $.ajax({
                    url: '../../api/cash_management.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(response) {
                        if (response.success) {
                            // Reload page to show new transaction
                            location.reload();
                        } else {
                            alert('هەڵەیەک ڕوویدا: ' + response.error);
                        }
                    },
                    error: function(xhr) {
                        alert('هەڵەیەک ڕوویدا: ' + xhr.responseJSON.error);
                    }
                });
            });
        });
    </script>
</body>
</html> 