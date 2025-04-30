<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../../includes/db.php';

// Include authentication check
require_once '../../includes/auth.php';

// Debug connection
echo "<pre>";
echo "Database Connection Status: ";
var_dump(isset($conn));
echo "\n";

// Get sale ID from URL parameter
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo "Sale ID from URL: " . $sale_id . "\n";

try {
    if (!isset($conn)) {
        throw new Exception("Database connection not established");
    }

    // Get sale details with customer information
    $stmt = $conn->prepare("
        SELECT s.*, c.name as customer_name, c.phone1 as customer_phone
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ? AND s.is_draft = 0
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Sale Query Result:\n";
    var_dump($sale);

    // Get sale items with product information
    $stmt = $conn->prepare("
        SELECT si.*, p.name as product_name, p.code as product_code
        FROM sale_items si
        LEFT JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSale Items Query Result:\n";
    var_dump($sale_items);
    echo "</pre>";

} catch (Exception $e) {
    echo "<pre>Error: " . $e->getMessage() . "</pre>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێست - بینینی پسووڵە</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 20px;
        }
        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .receipt-details {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2>تێست - بینینی پسووڵە</h2>
            </div>
        </div>

        <?php if ($sale): ?>
            <div class="receipt-details">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>زانیاری پسووڵە</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>ژمارەی پسووڵە</th>
                                <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                            </tr>
                            <tr>
                                <th>بەروار</th>
                                <td><?php echo date('Y/m/d', strtotime($sale['date'])); ?></td>
                            </tr>
                            <tr>
                                <th>کڕیار</th>
                                <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <th>ژمارەی مۆبایل</th>
                                <td><?php echo htmlspecialchars($sale['customer_phone']); ?></td>
                            </tr>
                            <tr>
                                <th>جۆری پارەدان</th>
                                <td><?php echo $sale['payment_type'] == 'cash' ? 'نقد' : 'قەرز'; ?></td>
                            </tr>
                            <tr>
                                <th>جۆری نرخ</th>
                                <td><?php echo $sale['price_type'] == 'single' ? 'تاک' : 'کۆمەڵ'; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h4>زانیاری پارە</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>بڕی پارەدراو</th>
                                <td><?php echo number_format($sale['paid_amount']); ?> د.ع</td>
                            </tr>
                            <tr>
                                <th>بڕی ماوە</th>
                                <td><?php echo number_format($sale['remaining_amount']); ?> د.ع</td>
                            </tr>
                            <tr>
                                <th>کۆی گشتی</th>
                                <td><?php echo number_format($sale['paid_amount'] + $sale['remaining_amount']); ?> د.ع</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <h4>کاڵاکان</h4>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>کۆدی کاڵا</th>
                                    <th>ناوی کاڵا</th>
                                    <th>بڕ</th>
                                    <th>جۆری یەکە</th>
                                    <th>نرخی تاک</th>
                                    <th>کۆی گشتی</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sale_items as $index => $item): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>
                                        <?php
                                        switch($item['unit_type']) {
                                            case 'piece':
                                                echo 'دانە';
                                                break;
                                            case 'box':
                                                echo 'کارتۆن';
                                                break;
                                            case 'set':
                                                echo 'سێت';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($item['unit_price']); ?> د.ع</td>
                                    <td><?php echo number_format($item['total_price']); ?> د.ع</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                پسووڵەکە نەدۆزرایەوە
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-12">
                <a href="draftAndWithdrawal.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>گەڕانەوە
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 