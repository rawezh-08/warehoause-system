<?php
// Include database connection
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Check if transaction ID is provided
$transactionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get transaction details
$query = "SELECT dt.*, c.name as customer_name, c.phone1 as customer_phone, c.address as customer_address
          FROM debt_transactions dt
          JOIN customers c ON dt.customer_id = c.id
          WHERE dt.id = :id AND dt.transaction_type = 'collection'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $transactionId);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

// If transaction not found or not a collection, redirect
if (!$transaction) {
    header("Location: customers.php");
    exit;
}

// Parse notes data if in JSON format
$notesData = json_decode($transaction['notes'], true);
$paymentMethod = isset($notesData['payment_method']) ? $notesData['payment_method'] : 'cash';
$referenceNumber = isset($notesData['reference_number']) ? $notesData['reference_number'] : '';
$displayNotes = isset($notesData['notes']) ? $notesData['notes'] : $transaction['notes'];
$returnDate = isset($notesData['return_date']) ? $notesData['return_date'] : date('Y-m-d', strtotime($transaction['created_at']));

// Format payment method
$paymentMethodText = '';
switch($paymentMethod) {
    case 'cash':
        $paymentMethodText = 'نەقد';
        break;
    case 'transfer':
        $paymentMethodText = 'گواستنەوەی بانکی';
        break;
    case 'check':
        $paymentMethodText = 'چەک';
        break;
    default:
        $paymentMethodText = 'هی تر';
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی گەڕانەوەی قەرز - سیستەمی بەڕێوەبردنی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom styles for this page -->
    <style>
        @media print {
            body {
                font-size: 14px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .print-btn {
                display: none;
            }
            .receipt {
                border: 1px solid #ddd;
                padding: 20px;
                max-width: 800px;
                margin: 0 auto;
            }
            .logo {
                max-width: 80px;
                height: auto;
            }
            .watermark {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 100px;
                color: rgba(220, 53, 69, 0.1);
                z-index: -1;
                white-space: nowrap;
            }
            .receipt-number {
                font-size: 18px;
                font-weight: bold;
                color: #dc3545;
            }
            .divider {
                border-top: 1px dashed #ddd;
                margin: 15px 0;
            }
            .signature-area {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
            }
            .signature-line {
                border-top: 1px solid #000;
                width: 200px;
                margin-top: 40px;
                text-align: center;
            }
            .receipt-date {
                font-size: 14px;
                margin-bottom: 10px;
            }
            .table td, .table th {
                padding: 6px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <!-- Print Button -->
        <div class="text-center mb-4 print-btn">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i> چاپکردن
            </button>
            <a href="customerProfile.php?id=<?php echo $transaction['customer_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i> گەڕانەوە
            </a>
        </div>
        
        <!-- Receipt -->
        <div class="receipt shadow-sm mb-5 bg-white rounded">
            <!-- Watermark -->
            <div class="watermark">گەڕانەوەی قەرز</div>
            
            <!-- Receipt Header -->
            <div class="print-header row">
                <div class="col-4 text-start">
                    <p class="receipt-date"><?php echo date('Y/m/d', strtotime($returnDate)); ?></p>
                </div>
                <div class="col-4 text-center">
                    <h4>پسووڵەی گەڕانەوەی قەرز</h4>
                    <p class="receipt-number">ژمارەی پسووڵە: <?php echo str_pad($transaction['id'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="col-4 text-end">
                    <img src="assets/img/logo.png" alt="Logo" class="logo">
                </div>
            </div>
            
            <div class="divider"></div>
            
            <!-- Customer Info -->
            <div class="row mb-4">
                <div class="col-6">
                    <p><strong>ناوی کڕیار:</strong> <?php echo htmlspecialchars($transaction['customer_name']); ?></p>
                    <p><strong>ژمارەی مۆبایل:</strong> <?php echo htmlspecialchars($transaction['customer_phone']); ?></p>
                </div>
                <div class="col-6">
                    <p><strong>ناونیشان:</strong> <?php echo htmlspecialchars($transaction['customer_address'] ?? 'نادیار'); ?></p>
                    <p><strong>بەرواری گەڕانەوە:</strong> <?php echo date('Y/m/d', strtotime($returnDate)); ?></p>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <!-- Transaction Details -->
            <div class="row mb-4">
                <div class="col-12">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>بڕی پارەی گەڕاوە</th>
                                <th>شێوازی پارەدان</th>
                                <th>ژمارەی مەرجەع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center"><strong><?php echo number_format($transaction['amount']); ?> دینار</strong></td>
                                <td class="text-center"><?php echo $paymentMethodText; ?></td>
                                <td class="text-center"><?php echo !empty($referenceNumber) ? htmlspecialchars($referenceNumber) : '-'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Notes -->
            <?php if (!empty($displayNotes)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <p><strong>تێبینی:</strong></p>
                    <p><?php echo htmlspecialchars($displayNotes); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="divider"></div>
            
            <!-- Signature Area -->
            <div class="signature-area">
                <div>
                    <div class="signature-line">
                        کڕیار
                    </div>
                </div>
                <div>
                    <div class="signature-line">
                        وەرگر
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="text-center mt-4">
                <p class="small text-muted">ئەم پسووڵەیە بەڵگەی فەرمی گەڕانەوەی قەرزە</p>
                <p class="small text-muted">سیستەمی بەڕێوەبردنی کۆگا</p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Auto print on page load -->
    <script>
        window.onload = function() {
            // Automatically open print dialog after page loads
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html> 