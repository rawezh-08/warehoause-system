<?php
// Include database connection
require_once '../../includes/auth.php';
require_once '../../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Get transaction ID
$transactionId = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;

// If transaction ID is not provided, exit
if ($transactionId <= 0) {
    echo "Transaction ID not provided";
    exit;
}

// Get transaction details
$query = "SELECT dt.*, c.name as customer_name, c.phone1 as customer_phone, c.address as customer_address, 
                 u.username as created_by_name
          FROM debt_transactions dt
          JOIN customers c ON dt.customer_id = c.id
          LEFT JOIN admin_accounts u ON dt.created_by = u.id
          WHERE dt.id = :id AND dt.transaction_type = 'advance_payment'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $transactionId);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

// If transaction not found, exit
if (!$transaction) {
    echo "Transaction not found or not an advance payment transaction";
    exit;
}

// Parse notes for additional info
$notesData = json_decode($transaction['notes'], true);
$paymentMethod = isset($notesData['payment_method']) ? $notesData['payment_method'] : 'cash';
$notes = isset($notesData['notes']) ? $notesData['notes'] : $transaction['notes'];

?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پسووڵەی پارەی پێشەکی</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom style for printing -->
    <style>
        @media print {
            @page {
                size: 80mm 297mm; /* Receipt width (80mm standard) and auto height */
                margin: 0;
            }
            
            body {
                margin: 0;
                padding: 0;
                font-size: 12pt;
                line-height: 1.3;
                font-family: Arial, sans-serif;
            }
            
            .container {
                width: 100%;
                max-width: 76mm; /* Leaving some margin */
                padding: 5mm 2mm;
                margin: 0 auto;
            }
            
            .print-hide {
                display: none !important;
            }
            
            hr {
                border-top: 1px dashed #000;
                margin: 5px 0;
            }
            
            .table-receipt {
                width: 100%;
                margin-bottom: 10px;
                border-collapse: collapse;
            }
            
            .table-receipt th, .table-receipt td {
                padding: 3px;
                text-align: right;
                font-size: 11pt;
            }
            
            .table-receipt th {
                font-weight: bold;
                border-bottom: 1px dashed #000;
            }
            
            .text-center {
                text-align: center !important;
            }
            
            .receipt-header {
                text-align: center;
                margin-bottom: 10px;
            }
            
            .receipt-header h1 {
                font-size: 16pt;
                margin: 5px 0;
            }
            
            .receipt-header h2 {
                font-size: 14pt;
                margin: 5px 0;
            }
            
            .receipt-footer {
                text-align: center;
                margin-top: 10px;
                font-size: 10pt;
            }
            
            .amount-row {
                font-weight: bold;
                font-size: 14pt;
            }
            
            .btn {
                display: none;
            }
        }
        
        /* Screen styling */
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .btn-print {
            margin-bottom: 20px;
        }
        
        hr {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .table-receipt {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .table-receipt th, .table-receipt td {
            padding: 5px;
            text-align: right;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
        }
        
        .amount-row {
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Button (Hidden when printing) -->
        <button onclick="window.print()" class="btn btn-primary btn-print print-hide">
            <i class="fas fa-print"></i> چاپکردن
        </button>
        
        <!-- Receipt Header -->
        <div class="receipt-header">
            <h1>سیستەمی بەڕێوەبردنی کۆگا</h1>
            <h2>پسووڵەی پارەی پێشەکی</h2>
        </div>
        
        <hr>
        
        <!-- Transaction Info -->
        <table class="table-receipt">
            <tr>
                <th>ژمارەی پسووڵە:</th>
                <td>#<?php echo $transaction['id']; ?></td>
            </tr>
            <tr>
                <th>بەروار:</th>
                <td><?php echo date('Y/m/d', strtotime($transaction['created_at'])); ?></td>
            </tr>
            <tr>
                <th>کات:</th>
                <td><?php echo date('H:i:s', strtotime($transaction['created_at'])); ?></td>
            </tr>
        </table>
        
        <hr>
        
        <!-- Customer Info -->
        <table class="table-receipt">
            <tr>
                <th>ناوی کڕیار:</th>
                <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
            </tr>
            <tr>
                <th>ژمارەی مۆبایل:</th>
                <td><?php echo htmlspecialchars($transaction['customer_phone']); ?></td>
            </tr>
            <?php if (!empty($transaction['customer_address'])): ?>
            <tr>
                <th>ناونیشان:</th>
                <td><?php echo htmlspecialchars($transaction['customer_address']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
        
        <hr>
        
        <!-- Payment Details -->
        <table class="table-receipt">
            <tr class="amount-row">
                <th>بڕی پارەی پێشەکی:</th>
                <td><?php echo number_format($transaction['amount']); ?> دینار</td>
            </tr>
            <tr>
                <th>شێوازی پارەدان:</th>
                <td>
                    <?php
                    switch ($paymentMethod) {
                        case 'cash':
                            echo 'نەقد';
                            break;
                        case 'transfer':
                            echo 'FIB یان FastPay';
                            break;
                        default:
                            echo 'نادیار';
                    }
                    ?>
                </td>
            </tr>
            <?php if (!empty($notes)): ?>
            <tr>
                <th>تێبینی:</th>
                <td><?php echo htmlspecialchars($notes); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>تۆمارکەر:</th>
                <td><?php echo htmlspecialchars($transaction['created_by_name'] ?? 'نادیار'); ?></td>
            </tr>
        </table>
        
        <hr>
        
        <!-- Receipt Footer -->
        <div class="receipt-footer">
            <p>ئەم پسووڵەیە بەڵگەی وەرگرتنی پارەی پێشەکییە</p>
            <p>سوپاس بۆ هەڵبژاردنی ئێمە</p>
        </div>
    </div>
    
    <!-- Auto-print script -->
    <script>
        // Auto print if 'print' parameter is passed in URL
        window.onload = function() {
            if (window.location.search.includes('print=true')) {
                window.print();
            }
        };
    </script>
</body>
</html> 